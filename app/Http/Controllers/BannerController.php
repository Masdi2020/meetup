<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Services\BannerService;

class BannerController extends Controller
{
    public function index(BannerService $service) {
        return Inertia::render(
            'Banner',
            $service->current()
        );
    }
}
