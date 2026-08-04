<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    public function list(): Collection
    {
        return Room::select('id', 'name')
            ->get();
    }

    public function calendar(): Collection
    {
        return Room::with('facilities:id,name')
            ->select([
                'id', 'name', 'capacity', 'floor',
                'calendar_url',
            ])->get();
    }

    public function find(int $id): Room
    {
        return Room::findOrFail($id);
    }
}
