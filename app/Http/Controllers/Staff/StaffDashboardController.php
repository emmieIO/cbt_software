<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffDashboardService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    public function __construct(
        protected StaffDashboardService $dashboardService
    ) {}

    public function __invoke(): Response
    {
        $dashboardData = $this->dashboardService->getDashboardData(Auth::user());

        return Inertia::render('Staff/Dashboard', (array) $dashboardData);
    }
}
