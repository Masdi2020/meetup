<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    /**
     * Summary of list
     *
     * @return Collection<int, Room>
     */
    public function list(): Collection
    {
        return Room::select('id', 'name')
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
}
