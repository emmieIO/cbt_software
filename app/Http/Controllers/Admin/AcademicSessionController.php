<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSessionController extends Controller
{
    /**
     * List all academic sessions.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Sessions', [
            'sessions' => AcademicSession::latest()->get(),
        ]);
    }

    /**
     * Store a new academic session.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:academic_sessions,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ]);

        if ($request->is_current) {
            AcademicSession::query()->update(['is_current' => false]);
        }

        AcademicSession::create($request->all());

        return back()->with('success', 'Academic session created successfully.');
    }

    /**
     * Update an academic session.
     */
    public function update(Request $request, AcademicSession $session): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:academic_sessions,name,'.$session->id.',id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_current' => ['boolean'],
        ]);

        if ($request->is_current && ! $session->is_current) {
            AcademicSession::query()->update(['is_current' => false]);
        }

        $session->update($request->all());

        return back()->with('success', 'Academic session updated successfully.');
    }

    /**
     * Set a session as current.
     */
    public function setCurrent(AcademicSession $session): RedirectResponse
    {
        AcademicSession::query()->update(['is_current' => false]);
        $session->update(['is_current' => true]);

        return back()->with('success', "Academic session set to {$session->name}.");
    }

    /**
     * Delete a session.
     */
    public function destroy(AcademicSession $session): RedirectResponse
    {
        if ($session->is_current) {
            return back()->with('error', 'Cannot delete the current active session.');
        }

        $session->delete();

        return back()->with('success', 'Academic session deleted successfully.');
    }
}
