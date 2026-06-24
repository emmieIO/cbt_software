<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicSessions\SaveAcademicSessionRequest;
use App\Models\AcademicSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSessionController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        return Inertia::render('AcademicSessions/Index', [
            'academicSessions' => AcademicSession::query()
                ->withCount('exams')
                ->orderByDesc('starts_at')
                ->paginate(20)
                ->through(fn (AcademicSession $session): array => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'starts_at' => $session->starts_at->format('Y-m-d'),
                    'ends_at' => $session->ends_at->format('Y-m-d'),
                    'is_active' => $session->is_active,
                    'exams_count' => $session->exams_count,
                ]),
        ]);
    }

    public function store(SaveAcademicSessionRequest $request): RedirectResponse
    {
        $this->saveSession(new AcademicSession, $request->payload());

        return back()->with('success', 'Academic session created successfully.');
    }

    public function update(SaveAcademicSessionRequest $request, AcademicSession $academicSession): RedirectResponse
    {
        $this->saveSession($academicSession, $request->payload());

        return back()->with('success', 'Academic session updated successfully.');
    }

    public function destroy(AcademicSession $academicSession): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        if ($academicSession->exams()->exists()) {
            return back()->with('error', 'This academic session is already used by an exam and cannot be deleted.');
        }

        $academicSession->delete();

        return back()->with('success', 'Academic session deleted successfully.');
    }

    /**
     * @param  array{name: string, starts_at: string, ends_at: string, is_active: bool}  $payload
     */
    private function saveSession(AcademicSession $academicSession, array $payload): void
    {
        DB::transaction(function () use ($academicSession, $payload): void {
            if ($payload['is_active']) {
                AcademicSession::query()->where('is_active', true)->update(['is_active' => false]);
            }

            $academicSession->fill($payload)->save();
        });
    }
}
