<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class RoomService
{
    public function __construct(private AuditService $audits) {}

    /**
     * Summary of list
     *
     * @return Collection<int, Room>
     */
    public function list(): Collection
    {
        return Room::with('facilities:id,name')
            ->select('id', 'name', 'capacity', 'location', 'image_path', 'has_display')
            ->where('is_available', true)
            ->get();
    }

    /**
     * Summary of calendar
     *
     * @return Collection<int, Room>
     */
    public function calendar(): Collection
    {
        return Room::with('facilities:id,name')
            ->select([
                'id', 'name', 'capacity', 'location',
                'calendar_url',
            ])->get();
    }

    public function find(int $id): Room
    {
        return Room::findOrFail($id);
    }

    /** @return Collection<int, Room> */
    public function displayList(): Collection
    {
        return Room::query()
            ->select(['id', 'name', 'location', 'capacity', 'is_available', 'has_display'])
            ->get();
    }

    /**
     * @return SupportCollection<int, array{
     *     id: int,
     *     name: string,
     *     location: string,
     *     capacity: int,
     *     is_available: bool,
     *     facilities: array<int, mixed>,
     *     facility_ids: array<int, mixed>
     * }>
     */
    public function adminList(string $search): SupportCollection
    {
        return Room::query()
            ->with('facilities:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Room $room) => [
                'id' => $room->id,
                'name' => $room->name,
                'location' => $room->location,
                'capacity' => $room->capacity,
                'is_available' => (bool) $room->is_available,
                'facilities' => $room->facilities->pluck('name')->values()->all(),
                'facility_ids' => $room->facilities->pluck('id')->values()->all(),
            ]);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $actorId): Room
    {
        return DB::transaction(function () use ($data, $actorId) {
            $room = Room::create([
                'name' => $data['name'],
                'capacity' => $data['capacity'],
                'location' => $data['location'],
                'is_available' => $data['is_available'] ?? true,
            ]);

            if (isset($data['facilities'])) {
                $room->facilities()->sync($data['facilities']);
            }

            $this->audits->record(
                'Room',
                $room->id,
                'created',
                null,
                $this->auditValues($room),
                $actorId,
                'ruangan dibuat oleh admin',
            );

            return $room;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Room $room, array $data, int $actorId): void
    {
        DB::transaction(function () use ($data, $actorId, $room) {
            $oldValues = $this->auditValues($room);

            $room->update([
                'name' => $data['name'],
                'capacity' => $data['capacity'],
                'location' => $data['location'],
                'is_available' => $data['is_available'] ?? $room->is_available,
            ]);

            if (isset($data['facilities'])) {
                $room->facilities()->sync($data['facilities']);
            }

            $this->audits->record(
                'Room',
                $room->id,
                'updated',
                $oldValues,
                $this->auditValues($room),
                $actorId,
                'ruangan diperbarui oleh admin',
            );
        });
    }

    public function toggleAvailability(Room $room, int $actorId): void
    {
        DB::transaction(function () use ($actorId, $room) {
            $oldAvailability = (bool) $room->is_available;

            $room->update(['is_available' => ! $oldAvailability]);

            $this->audits->record(
                'Room',
                $room->id,
                'availability_changed',
                ['is_available' => $oldAvailability],
                ['is_available' => (bool) $room->is_available],
                $actorId,
                $room->is_available ? 'ruangan diaktifkan oleh admin' : 'ruangan dinonaktifkan oleh admin',
            );
        });
    }

    /** @return array<string, mixed> */
    private function auditValues(Room $room): array
    {
        $room->load('facilities:id');

        return [
            'name' => $room->name,
            'capacity' => $room->capacity,
            'location' => $room->location,
            'is_available' => (bool) $room->is_available,
            'facility_ids' => $room->facilities->pluck('id')->sort()->values()->all(),
        ];
    }
}
