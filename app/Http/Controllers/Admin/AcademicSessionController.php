<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicSessionRequest;
use App\Models\AcademicSession;
use App\Services\AcademicSessionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSessionController extends Controller
{
    public function __construct(protected AcademicSessionService $academicSessionService) {}

    /**
     * List all academic sessions.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Sessions', $this->academicSessionService->getIndexData());
    }

    /**
     * Store a new academic session.
     */
    public function store(AcademicSessionRequest $request): RedirectResponse
    {
        $this->academicSessionService->createSession($request->validated());

        return back()->with('success', 'Academic session created successfully.');
    }

    /**
     * Update an academic session.
     */
    public function update(AcademicSessionRequest $request, AcademicSession $session): RedirectResponse
    {
        $this->academicSessionService->updateSession($session, $request->validated());

        return back()->with('success', 'Academic session updated successfully.');
    }

    /**
     * Set a session as current.
     */
    public function setCurrent(AcademicSession $session): RedirectResponse
    {
        $this->academicSessionService->setCurrent($session);

        return back()->with('success', "Academic session set to {$session->name}.");
    }

    /**
     * Delete a session.
     */
    public function destroy(AcademicSession $session): RedirectResponse
    {
        if (! $this->academicSessionService->deleteSession($session)) {
            return back()->with('error', 'Cannot delete the current active session.');
        }

        return back()->with('success', 'Academic session deleted successfully.');
    }
}
