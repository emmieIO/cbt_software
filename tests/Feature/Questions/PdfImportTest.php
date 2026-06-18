<?php

use App\Ai\Agents\QuestionParserAgent;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;

test('it can preview questions from a pdf file', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    // Fake the AI Agent response
    QuestionParserAgent::fake([
        [
            'questions' => [
                [
                    'subject_name' => 'Mathematics',
                    'topic_name' => 'Algebra',
                    'type' => 'multiple_choice',
                    'content' => 'What is x if 2x = 4?',
                    'options' => ['1', '2', '3', '4'],
                    'correct_answer' => 'B',
                    'level' => 'js',
                    'class_level' => '7',
                    'marking_scheme' => [],
                ],
            ],
        ],
    ]);

    $file = pdfUpload('test_questions.pdf', '<h1>Mathematics</h1><p>What is x if 2x = 4?</p>');

    $response = $this->actingAs($user)
        ->post(route('questions.import.preview'), [
            'file' => $file,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('import_preview');

    $preview = session('import_preview');
    expect($preview['rows'])->toHaveCount(1);
    expect($preview['rows'][0]['content'])->toBe('What is x if \(2x = 4\)?');
    expect($preview['rows'][0]['valid'])->toBeTrue();
});

function pdfUpload(string $name, string $html): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pdf-import-').'.pdf';
    file_put_contents($path, DomPdf::loadHTML($html)->output());

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

test('it can confirm import of pdf questions', function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $user = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $previewData = [
        'rows' => [
            [
                'index' => 1,
                'valid' => true,
                'errors' => [],
                'subject_name' => 'Mathematics',
                'topic_name' => 'Algebra',
                'type' => 'multiple_choice',
                'content' => 'What is x if 2x = 4?',
                'options' => ['1', '2', '3', '4'],
                'correct_answer' => 'B',
                'level' => 'js',
                'class_level' => '7',
                'marking_scheme' => [],
            ],
        ],
        'new_subjects' => ['Mathematics (JS)'],
        'new_topics' => ['Algebra (Level 7)'],
    ];

    session(['import_preview' => $previewData]);

    $response = $this->actingAs($user)
        ->post(route('questions.import.confirm'), [
            'level' => 'js',
        ]);

    $response->assertRedirect(route('questions.index'));
    $response->assertSessionHas('success');
});
