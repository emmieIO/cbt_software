<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Rbac\PermissionOverviewService;
use Inertia\Inertia;
use Inertia\Response;

class PermissionOverviewController extends Controller
{
    public function __construct(protected PermissionOverviewService $permissionOverviewService) {}

    /**
     * Display the system permission architecture for board review.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/RBAC/PermissionsOverview', [
            'groups' => $this->permissionOverviewService->groups(),
        ]);
    }
}
