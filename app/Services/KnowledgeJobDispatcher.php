<?php

namespace App\Services;

use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Throwable;

class KnowledgeJobDispatcher
{
    public function dispatch(ShouldQueue $job): void
    {
        $lock = $job instanceof ShouldBeUnique ? new UniqueLock(app(Repository::class)) : null;
        if ($lock !== null && ! $lock->acquire($job)) {
            return;
        }

        try {
            Bus::dispatch($job);
        } catch (Throwable $exception) {
            $lock?->release($job);

            throw $exception;
        }
    }
}
