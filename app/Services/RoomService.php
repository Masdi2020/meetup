<?php

namespace App\Services;

use App\Models\Room;

class RoomService
{
    public function list() {
        return Room::select('id', 'name')
            ->get();
    }
}
