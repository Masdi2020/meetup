<?php

namespace App\Http\Controllers;

use App\Events\BannerUpdated;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminBookingController extends Controller
{
    /** @var array<string, string> */
    private const EXPORT_COLUMNS = [
        'code' => 'Kode',
        'borrower' => 'Peminjam',
        'room' => 'Ruangan',
        'activity' => 'Kegiatan',
        'date' => 'Tanggal',
        'start' => 'Waktu Mulai',
        'end' => 'Waktu Selesai',
        'participants' => 'Jumlah Peserta',
        'status' => 'Status',
        'request' => 'Catatan/Request',
        'processed_notes' => 'Catatan Proses',
        'submitted_at' => 'Dibuat Pada',
        'processed_at' => 'Diproses Pada',
    ];

    /** @var array<int, string> */
    private const EXPORT_STATUSES = [
        'PENDING',
        'APPROVED',
        'REJECTED',
        'CANCELLED',
        'FINISHED',
    ];

    public function __construct(private BookingWorkflowService $bookingWorkflow) {}

    public function index(Request $request): Response
    {
        $bookings = $this->filteredBookings($request)->latest()->paginate(10)
            ->through(fn ($booking) => [
                'id' => $booking->id, 'code' => $booking->id, 'room' => $booking->room->name,
                'borrower' => $booking->user->name ?? 'Pengguna dihapus', 'activity' => $booking->title,
                'date' => $booking->date->format('d/m/Y'), 'start' => $booking->start_time->format('H:i'),
                'end' => $booking->end_time->format('H:i'), 'status' => strtolower($booking->status->code),
                'request' => $booking->notes, 'processed_notes' => $booking->processed_notes,
            ]);

        $count = fn (string $code) => Booking::whereHas('status', fn ($query) => $query->where('code', $code))->count();

        return Inertia::render('Admin/Booking', [
            'bookings' => $bookings, 'rooms' => Room::select('id', 'name')->orderBy('name')->get(),
            'users' => User::query()->select('id', 'name', 'role')->whereIn('role', ['user', 'admin'])->orderBy('name')->get(),
            'statuses' => BookingStatus::query()->select('code', 'label')->orderBy('id')->get()
                ->map(fn (BookingStatus $status) => [
                    'code' => strtolower($status->code),
                    'label' => ucfirst(strtolower($status->label)),
                ]),
            'stats' => ['total' => Booking::count(), 'pending' => $count('PENDING'), 'approved' => $count('APPROVED'), 'finished' => $count('FINISHED')],
            'filters' => $request->only(['search', 'status', 'room']),
        ]);
    }

    public function exportData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'distinct', Rule::in(array_keys(self::EXPORT_COLUMNS))],
            'statuses' => ['sometimes', 'array', 'min:1'],
            'statuses.*' => ['required', 'string', 'distinct', Rule::in(self::EXPORT_STATUSES)],
            'period' => ['sometimes', 'string', Rule::in(['all', 'date', 'range', 'month', 'year'])],
            'date' => ['required_if:period,date', 'date_format:Y-m-d'],
            'start_date' => ['required_if:period,range', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,range', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'month' => ['required_if:period,month', 'date_format:Y-m'],
            'year' => ['required_if:period,year', 'integer', 'min:2000', 'max:2100'],
        ]);

        /** @var array<int, string> $selectedColumns */
        $selectedColumns = $validated['columns'];
        /** @var array<int, string>|null $selectedStatuses */
        $selectedStatuses = $validated['statuses'] ?? null;
        $bookingsQuery = $this->filteredBookings($request, $selectedStatuses);
        $period = $validated['period'] ?? 'all';

        if ($period === 'date') {
            $bookingsQuery->whereDate('date', (string) $validated['date']);
        } elseif ($period === 'range') {
            $bookingsQuery->whereBetween('date', [
                (string) $validated['start_date'],
                (string) $validated['end_date'],
            ]);
        } elseif ($period === 'month') {
            [$year, $month] = explode('-', (string) $validated['month']);
            $bookingsQuery->whereYear('date', $year)->whereMonth('date', $month);
        } elseif ($period === 'year') {
            $bookingsQuery->whereYear('date', (int) $validated['year']);
        }

        $bookings = $bookingsQuery
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json([
            'columns' => collect($selectedColumns)->map(fn (string $key) => [
                'key' => $key,
                'label' => self::EXPORT_COLUMNS[$key],
            ])->values(),
            'rows' => $bookings->map(fn (Booking $booking) => collect($selectedColumns)
                ->mapWithKeys(fn (string $column) => [$column => $this->exportValue($booking, $column)])),
            'meta' => [
                'total' => $bookings->count(),
                'exported_at' => now()->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'APPROVED', $request->user()->id, 'Disetujui oleh admin');

        return back()->with('success', 'Booking approved successfully.');
    }

    public function reject(RejectBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'REJECTED', $request->user()->id, $request->validated('reason'));

        return back()->with('success', 'Booking rejected successfully.');
    }

    public function finish(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus($booking, 'FINISHED', $request->user()->id, 'Diakhiri oleh admin');

        return back()->with('success', 'Booking berhasil diakhiri.');
    }

    public function update(UpdateBookingRequest $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->update(
            $booking,
            $request->bookingData(),
            $request->user()->id,
            'booking diperbarui oleh admin',
        );

        return back()->with('success', 'Booking berhasil diperbarui.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->bookingWorkflow->changeStatus(
            $booking,
            'CANCELLED',
            $request->user()->id,
            'Dibatalkan oleh admin',
        );

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        broadcast(new BannerUpdated);

        return back()->with('success', 'Booking berhasil dihapus.');
    }

    /**
     * @param  array<int, string>|null  $statuses
     * @return Builder<Booking>
     */
    private function filteredBookings(Request $request, ?array $statuses = null): Builder
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->upper()->toString();
        $room = $request->integer('room') ?: null;

        $query = Booking::query()
            ->with(['room:id,name', 'user:id,name', 'status:id,code,label'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('room', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($room, fn ($query) => $query->where('room_id', $room));

        if ($statuses !== null) {
            return $query->whereHas('status', fn ($query) => $query->whereIn('code', $statuses));
        }

        return $query->when($status, fn ($query) => $query->whereHas('status', fn ($query) => $query->where('code', $status)));
    }

    private function exportValue(Booking $booking, string $column): string|int
    {
        return match ((string) $column) {
            'code' => $booking->id,
            'borrower' => $booking->user->name ?? 'Pengguna dihapus',
            'room' => $booking->room->name,
            'activity' => $booking->title,
            'date' => $booking->date->format('d/m/Y'),
            'start' => $booking->start_time->format('H:i'),
            'end' => $booking->end_time->format('H:i'),
            'participants' => $booking->participants_count,
            'status' => $booking->status->label,
            'request' => $booking->notes ?? '',
            'processed_notes' => $booking->processed_notes ?? '',
            'submitted_at' => $booking->created_at?->format('d/m/Y H:i') ?? '',
            'processed_at' => $booking->processed_at?->format('d/m/Y H:i') ?? '',
            default => 'Data tidak tersedia',
        };
    }
}
