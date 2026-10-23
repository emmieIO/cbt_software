<?php

namespace App\Console\Commands;

use App\Models\KnowledgeDocument;
use App\Models\User;
use App\Services\CurriculumReconciliationService;
use Illuminate\Console\Command;

class ReconcileCurriculumSuggestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curriculum:reconcile {document} {--user=} {--apply : Apply the reported safe changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview or apply conservative reconciliation of pending curriculum suggestions';

    /**
     * Execute the console command.
     */
    public function handle(CurriculumReconciliationService $service): int
    {
        $user = User::query()->find($this->option('user'));
        if (! $user?->canReviewCurriculum()) {
            $this->error('Supply --user with an existing authorized curriculum reviewer ID.');

            return self::FAILURE;
        }
        $document = KnowledgeDocument::query()->findOrFail($this->argument('document'));
        $this->info($this->option('apply') ? 'Applying safe reconciliation' : 'Dry run: no records changed');
        $this->table(['Mapping', 'Result', 'Detail'], $service->reconcile($document, $user, (bool) $this->option('apply')));

        return self::SUCCESS;
    }
}
