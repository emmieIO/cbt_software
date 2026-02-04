<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('Staff/Login');
    }

    public function dashboard(): Response
    {
        return Inertia::render('Staff/Dashboard');
    }
}
