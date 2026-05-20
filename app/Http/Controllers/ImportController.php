<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Reader\XLSX\Reader;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Questions/ImportExcel');
    }

    public function batchCreate(): Response
    {
        $subjects = Subject::query()
            ->with(['topics:id,subject_id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return Inertia::render('Questions/BatchCreate', [
            'levels' => [
                ['value' => 'lp', 'label' => 'Lower Primary'],
                ['value' => 'hp', 'label' => 'Higher Primary'],
                ['value' => 'js', 'label' => 'Junior Secondary'],
                ['value' => 'ss', 'label' => 'Senior Secondary'],
            ],
            'subjects' => $subjects,
        ]);
    }

    public function downloadTemplate()
    {
        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $writer->openToBrowser('import-template.xlsx');

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'subject', 'topic', 'type', 'content', 'option_a', 'option_b',
            'option_c', 'option_d', 'correct_answer', 'explanation',
            'marking_points', 'marking_weights',
        ]));

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'Mathematics', 'Algebra', 'mcq', 'What is 2 + 2?', '3', '4', '5', '6', 'b', '',
            '', '',
        ]));

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
            'English', 'Composition', 'theory', 'Write a paragraph about your school.', '', '', '', '', '', '',
            'Proper structure|Good grammar|Relevant content', '3|2|2',
        ]));

        $writer->close();
        exit;
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $reader = new Reader;
        $reader->open($file->getRealPath());

        $rows = [];
        $subjectNames = [];
        $topicNames = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $isFirst = true;
            foreach ($sheet->getRowIterator() as $row) {
                if ($isFirst) {
                    $isFirst = false;

                    continue;
                }

                $cells = $row->toArray();
                if (empty(trim($cells[3] ?? ''))) {
                    continue;
                }

                $subjectName = trim($cells[0] ?? '');
                $topicName = trim($cells[1] ?? '');
                $idx = count($rows);

                $errors = [];

                if (empty($subjectName)) {
                    $errors[] = 'Subject is required.';
                }
                if (empty($topicName)) {
                    $errors[] = 'Topic is required.';
                }

                $type = trim($cells[2] ?? '') === 'theory' ? 'theory' : 'multiple_choice';
                $content = trim($cells[3] ?? '');
                if (empty($content)) {
                    $errors[] = 'Question content is required.';
                }

                $options = [];
                if ($type === 'multiple_choice') {
                    $letters = ['a', 'b', 'c', 'd'];
                    for ($i = 0; $i < 4; $i++) {
                        $opt = trim($cells[4 + $i] ?? '');
                        if (empty($opt)) {
                            $errors[] = 'Option '.strtoupper($letters[$i]).' is required.';
                        }
                        $options[] = $opt;
                    }
                    $correct = strtolower(trim($cells[8] ?? ''));
                    if (! in_array($correct, $letters)) {
                        $errors[] = 'Correct answer must be A, B, C, or D.';
                    }
                }

                $scheme = [];
                if ($type === 'theory') {
                    $points = ! empty($cells[10]) ? explode('|', $cells[10]) : [];
                    $weights = ! empty($cells[11]) ? explode('|', $cells[11]) : [];
                    foreach ($points as $i => $p) {
                        $pt = trim($p);
                        if (empty($pt)) {
                            continue;
                        }
                        $scheme[] = ['point' => $pt, 'weight' => (int) ($weights[$i] ?? 1)];
                    }
                }

                if ($subjectName) {
                    $subjectNames[$subjectName] = true;
                }
                if ($topicName) {
                    $topicNames[$topicName] = true;
                }

                $level = strtolower(trim($cells[12] ?? ''));
                if ($level && ! in_array($level, ['lp', 'hp', 'js', 'ss'])) {
                    $errors[] = 'Invalid level.';
                }
                if ($level) {
                    $rows[$idx]['level'] = $level;
                }

                $rows[] = [
                    'index' => $idx + 2,
                    'valid' => empty($errors),
                    'errors' => $errors,
                    'subject_name' => $subjectName,
                    'topic_name' => $topicName,
                    'type' => $type,
                    'content' => mb_substr($content, 0, 100),
                    'options' => $options,
                    'correct_answer' => $type === 'multiple_choice' ? strtoupper(trim($cells[8] ?? '')) : null,
                    'marking_scheme' => $scheme,
                ];
            }
        }

        $reader->close();

        $existingSubjects = Subject::query()->whereIn('name', array_keys($subjectNames))->pluck('name')->toArray();
        $newSubjects = array_diff(array_keys($subjectNames), $existingSubjects);

        $existingTopics = Topic::query()->whereIn('name', array_keys($topicNames))->pluck('name')->toArray();
        $newTopics = array_diff(array_keys($topicNames), $existingTopics);

        session(['import_preview' => [
            'rows' => $rows,
            'new_subjects' => array_values($newSubjects),
            'new_topics' => array_values($newTopics),
        ]]);

        return back()->with('preview', [
            'rows' => $rows,
            'total' => count($rows),
            'valid' => count(array_filter($rows, fn ($r) => $r['valid'])),
            'errors' => count(array_filter($rows, fn ($r) => ! $r['valid'])),
            'new_subjects' => array_values($newSubjects),
            'new_topics' => array_values($newTopics),
        ]);
    }

    public function confirm(Request $request)
    {
        $preview = session('import_preview');
        if (! $preview) {
            return back()->with('error', 'No preview data found. Please upload the file again.');
        }

        $rows = $preview['rows'];
        $hasErrors = count(array_filter($rows, fn ($r) => ! $r['valid'])) > 0;
        if ($hasErrors) {
            return back()->with('error', 'Cannot import — some rows have errors. Fix and re-upload.');
        }

        return $this->importRows($rows, $request);
    }

    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'rows' => 'required|array|min:1|max:100',
            'rows.*.subject_name' => 'required|string',
            'rows.*.topic_name' => 'required|string',
            'rows.*.type' => 'required|in:multiple_choice,theory',
            'rows.*.content' => 'required|string',
            'rows.*.level' => 'required|in:lp,hp,js,ss',
            'rows.*.options' => 'nullable|array|size:4',
            'rows.*.options.*' => 'required_with:rows.*.options|string',
            'rows.*.correct_answer' => 'nullable|in:A,B,C,D,a,b,c,d',
            'rows.*.marking_scheme' => 'nullable|array',
        ]);

        $rows = array_map(fn ($r) => [
            'subject_name' => $r['subject_name'],
            'topic_name' => $r['topic_name'],
            'type' => $r['type'],
            'content' => $r['content'],
            'level' => $r['level'],
            'options' => $r['options'] ?? [],
            'correct_answer' => $r['correct_answer'] ?? '',
            'marking_scheme' => $r['marking_scheme'] ?? [],
        ], $validated['rows']);

        return $this->importRows($rows, $request);
    }

    private function importRows(array $rows, Request $request)
    {
        $level = strtolower(trim($request->input('level', 'js')));
        if (! in_array($level, ['lp', 'hp', 'js', 'ss'])) {
            $level = 'js';
        }

        DB::beginTransaction();
        try {
            $created = 0;
            $subjectCache = [];
            $topicCache = [];

            foreach ($rows as $row) {
                if (! isset($subjectCache[$row['subject_name']])) {
                    $subjectCache[$row['subject_name']] = Subject::firstOrCreate(
                        ['name' => $row['subject_name']],
                        ['slug' => str($row['subject_name'])->slug(), 'level' => $level]
                    )->id;
                }

                $topicKey = $row['subject_name'].'::'.$row['topic_name'];
                if (! isset($topicCache[$topicKey])) {
                    $topicCache[$topicKey] = Topic::firstOrCreate(
                        ['name' => $row['topic_name'], 'subject_id' => $subjectCache[$row['subject_name']]],
                        ['slug' => str($row['topic_name'])->slug()]
                    )->id;
                }

                $question = Question::query()->create([
                    'topic_id' => $topicCache[$topicKey],
                    'content' => $row['content'],
                    'type' => $row['type'],
                    'level' => $row['level'] ?? $level,
                    'marking_scheme' => $row['type'] === 'theory' ? ($row['marking_scheme'] ?? []) : null,
                    'created_by' => $request->user()->id,
                ]);

                if ($row['type'] === 'multiple_choice') {
                    $letters = ['a', 'b', 'c', 'd'];
                    foreach ($row['options'] as $i => $opt) {
                        $question->options()->create([
                            'content' => $opt,
                            'is_correct' => $letters[$i] === strtolower($row['correct_answer'] ?? ''),
                        ]);
                    }
                }

                $created++;
            }

            DB::commit();
            session()->forget('import_preview');

            $msg = "{$created} questions created successfully.";

            return to_route('questions.index')->with('success', $msg);

        } catch (Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }
}
