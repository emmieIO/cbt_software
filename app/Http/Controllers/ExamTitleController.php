<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamTitles\SaveExamTitleRequest;
use App\Models\ExamTitle;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamTitleController extends Controller
{
    public function index(): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        return Inertia::render('ExamTitles/Index', [
            'examTitles' => ExamTitle::query()
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function store(SaveExamTitleRequest $request): RedirectResponse
    {
        ExamTitle::query()->create($request->payload());

        return back()->with('success', 'Exam title created successfully.');
    }

    public function update(SaveExamTitleRequest $request, ExamTitle $examTitle): RedirectResponse
    {
        $examTitle->update($request->payload());

        return back()->with('success', 'Exam title updated successfully.');
    }

    public function destroy(ExamTitle $examTitle): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        $examTitle->delete();

        return back()->with('success', 'Exam title deleted successfully.');
    }
}
