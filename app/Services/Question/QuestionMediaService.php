<?php

namespace App\Services\Question;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class QuestionMediaService
{
    public function store(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('questions', 'public');
    }

    public function replace(?UploadedFile $newFile, ?string $existingPath, bool $removeCurrent = false): ?string
    {
        if ($newFile) {
            $this->delete($existingPath);

            return $this->store($newFile);
        }

        if ($removeCurrent) {
            $this->delete($existingPath);

            return null;
        }

        return $existingPath;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
