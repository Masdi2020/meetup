<?php

namespace App\Http\Controllers;

use App\Services\BannerService;
use Inertia\Inertia;

class BannerController extends Controller
{
    public function index(BannerService $service)
    {
        return Inertia::render(
            'Banner',
            $service->current()
        );
    }
}
