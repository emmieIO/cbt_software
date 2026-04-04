<?php

namespace App\Services;

use App\Enums\Term;
use App\Models\AcademicSession;
use Illuminate\Support\Collection;

class AcademicSessionService
{
    public function getIndexData(): array
    {
        return [
            'sessions' => AcademicSession::query()->latest()->get(),
            'terms' => $this->termOptions(),
        ];
    }

    public function createSession(array $data): AcademicSession
    {
        if (! empty($data['is_current'])) {
            AcademicSession::query()->update(['is_current' => false]);
        }

        return AcademicSession::create($data);
    }

    public function updateSession(AcademicSession $session, array $data): bool
    {
        if (! empty($data['is_current']) && ! $session->is_current) {
            AcademicSession::query()->update(['is_current' => false]);
        }

        return $session->update($data);
    }

    public function setCurrent(AcademicSession $session): void
    {
        AcademicSession::query()->update(['is_current' => false]);
        $session->update(['is_current' => true]);
    }

    public function deleteSession(AcademicSession $session): bool
    {
        if ($session->is_current) {
            return false;
        }

        return (bool) $session->delete();
    }

    private function termOptions(): Collection
    {
        return collect(Term::cases())->map(fn ($term) => [
            'value' => $term->value,
            'label' => $term->label(),
        ]);
    }
}
