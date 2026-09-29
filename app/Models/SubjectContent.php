<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'session_number',
        'content',
        'theory_time',
        'practice_time',
        'test_time',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
