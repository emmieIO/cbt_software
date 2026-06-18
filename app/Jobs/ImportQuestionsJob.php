<?php

namespace App\Jobs;

use App\Services\QuestionImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public array $rows,
        public string $defaultLevel,
        public string $createdBy,
    ) {}

    public function handle(QuestionImportService $questionImportService): void
    {
        $questionImportService->importRows($this->rows, $this->defaultLevel, $this->createdBy);
    }
}
