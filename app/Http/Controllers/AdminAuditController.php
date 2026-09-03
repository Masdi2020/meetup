<?php

namespace App\Http\Controllers;

use App\Services\AuditQueryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminAuditController extends Controller
{
    public function __construct(private AuditQueryService $auditQueries) {}

    public function index(Request $request): InertiaResponse
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->trim()->toString();

        return Inertia::render(
            'Admin/Audit',
            $this->auditQueries->pageData($search, $role),
        );
    }
}
