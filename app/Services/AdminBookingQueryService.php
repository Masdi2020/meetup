<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminBookingQueryService
{
    /** @var array<string, string> */
    public const EXPORT_COLUMNS = [
        'id' => 'ID',
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
    public const EXPORT_STATUSES = [
        'PENDING',
        'APPROVED',
        'REJECTED',
        'CANCELLED',
        'FINISHED',
    ];

    /** @return array<string, mixed> */
    public function indexData(string $search, string $status, ?int $roomId): array
    {
        $bookings = $this->filteredBookings($search, $status, $roomId)
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking) => [
                'id' => $booking->id,
                'room' => $booking->room->name,
                'borrower' => $booking->user->name ?? 'Pengguna dihapus',
                'activity' => $booking->title,
                'date' => $booking->date->format('d/m/Y'),
                'start' => $booking->start_time->format('H:i'),
                'end' => $booking->end_time->format('H:i'),
                'status' => strtolower($booking->status->code),
                'request' => $booking->notes,
                'processed_notes' => $booking->processed_notes,
            ]);

        $count = fn (string $code) => Booking::whereHas(
            'status',
            fn ($query) => $query->where('code', $code)
        )->count();

        return [
            'bookings' => $bookings,
            'rooms' => Room::select('id', 'name')->orderBy('name')->get(),
            'users' => User::query()
                ->select('id', 'name', 'role')
                ->whereIn('role', ['user', 'admin'])
                ->orderBy('name')
                ->get(),
            'statuses' => BookingStatus::query()
                ->select('code', 'label')
                ->orderBy('id')
                ->get()
                ->map(fn (BookingStatus $bookingStatus) => [
                    'code' => strtolower($bookingStatus->code),
                    'label' => ucfirst(strtolower($bookingStatus->label)),
                ]),
            'stats' => [
                'total' => Booking::count(),
                'pending' => $count('PENDING'),
                'approved' => $count('APPROVED'),
                'finished' => $count('FINISHED'),
            ],
            'filters' => [
                'search' => $search,
                'status' => strtolower($status),
                'room' => $roomId ? (string) $roomId : '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function exportData(
        array $data,
        string $search,
        string $status,
        ?int $roomId,
    ): array {
        /** @var array<int, string> $selectedColumns */
        $selectedColumns = $data['columns'];
        /** @var array<int, string>|null $selectedStatuses */
        $selectedStatuses = $data['statuses'] ?? null;
        $bookingsQuery = $this->filteredBookings(
            $search,
            $status,
            $roomId,
            $selectedStatuses,
        );
        $period = $data['period'] ?? 'all';

        if ($period === 'date') {
            $bookingsQuery->whereDate('date', (string) $data['date']);
        } elseif ($period === 'range') {
            $bookingsQuery->whereBetween('date', [
                (string) $data['start_date'],
                (string) $data['end_date'],
            ]);
        } elseif ($period === 'month') {
            [$year, $month] = explode('-', (string) $data['month']);
            $bookingsQuery->whereYear('date', $year)->whereMonth('date', $month);
        } elseif ($period === 'year') {
            $bookingsQuery->whereYear('date', (int) $data['year']);
        }

        $bookings = $bookingsQuery
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        return [
            'columns' => collect($selectedColumns)->map(fn (string $key) => [
                'key' => $key,
                'label' => self::EXPORT_COLUMNS[$key],
            ])->prepend(['key' => 'number', 'label' => 'No'])->values(),
            'rows' => $bookings->values()->map(fn (Booking $booking, int $index) => collect($selectedColumns)
                ->mapWithKeys(fn (string $column) => [$column => $this->exportValue($booking, $column)])
                ->prepend($index + 1, 'number')),
            'meta' => [
                'total' => $bookings->count(),
                'exported_at' => now()->format('d/m/Y H:i'),
            ],
        ];
    }

    /**
     * @param  array<int, string>|null  $statuses
     * @return Builder<Booking>
     */
    private function filteredBookings(
        string $search,
        string $status,
        ?int $roomId,
        ?array $statuses = null,
    ): Builder {
        $query = Booking::query()
            ->with(['room:id,name', 'user:id,name', 'status:id,code,label'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('room', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($roomId, fn ($query) => $query->where('room_id', $roomId));

        if ($statuses !== null) {
            return $query->whereHas('status', fn ($query) => $query->whereIn('code', $statuses));
        }

        return $query->when(
            $status !== '',
            fn ($query) => $query->whereHas('status', fn ($query) => $query->where('code', $status))
        );
    }

    private function exportValue(Booking $booking, string $column): string|int
    {
        return match ($column) {
            'id' => $booking->id,
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
