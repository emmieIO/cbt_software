<?php

test('deployment paths never automatically seed sample question bank content', function () {
    $root = dirname(__DIR__, 2);
    foreach (['deploy.sh', '.github/workflows/deploy.yml'] as $path) {
        expect(file_get_contents($root.'/'.$path))->not->toContain('php artisan db:seed');
    }
});

test('development workers listen to AI and document queues with a safe timeout', function () {
    $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach (['dev', 'dev:ssr'] as $script) {
        $command = implode(' ', $composer['scripts'][$script]);
        expect($command)->toContain('--queue=ai,default')->toContain('--tries=3')->toContain('--timeout=1800');
    }
});

test('production deployment gates include application frontend and type checks', function () {
    $workflow = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/deploy.yml');
    expect($workflow)->toContain('needs: quality-assurance')
        ->toContain('php artisan test --compact')
        ->toContain('node --test tests/Frontend/*.test.ts')
        ->toContain('npx vue-tsc --noEmit');
});
