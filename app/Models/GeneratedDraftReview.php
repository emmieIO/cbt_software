<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class GeneratedDraftReview extends Model
{
    use HasUlids;

    protected $fillable = ['actor_id', 'action', 'snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }
}
