<?php

namespace App\Services;

use App\Models\Room;

class RoomService
{
    public function list() {
        return Room::select('id', 'name')
            ->get();
    }

    public function calendar() {
        return Room::with('facilities:id,name')
        ->select([
            'id', 'name', 'capacity', 'floor',
            'calendar_url'
        ])->get();
    }

    public function find(int $id) {
        return Room::findOrFail($id);
    }
}
