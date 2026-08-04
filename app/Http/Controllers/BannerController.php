<?php

namespace App\Http\Controllers;

use App\Services\BannerService;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function index(BannerService $service): Response
    {
        return Inertia::render(
            'Banner',
            $service->current()
        );
    }
}
