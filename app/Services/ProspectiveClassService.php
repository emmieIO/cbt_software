<?php

namespace App\Services;

use App\Models\ProspectiveClass;
use Illuminate\Support\Str;

class ProspectiveClassService
{
    public function createClass(array $data): ProspectiveClass
    {
        return ProspectiveClass::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateClass(ProspectiveClass $prospectiveClass, array $data): bool
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        return $prospectiveClass->update($data);
    }

    public function deleteClass(ProspectiveClass $prospectiveClass): bool
    {
        // Check if candidates or exams are assigned
        if ($prospectiveClass->candidates()->exists() || $prospectiveClass->exams()->exists()) {
            return false;
        }

        return $prospectiveClass->delete();
    }
}
