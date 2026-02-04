<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function login(): Response
    {
        return Inertia::render('Student/Login');
    }

    public function dashboard(): Response
    {
        return Inertia::render('Student/Dashboard');
    }
}