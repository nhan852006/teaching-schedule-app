<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_id',
        'subject_id',
        'session_number',
        'teaching_date',
        'session_shift',
        'google_event_id',
        'sync_status',
    ];

    protected $casts = [
        'teaching_date' => 'date:Y-m-d',
        'session_number' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Lấy nội dung môn học tương ứng với buổi học này
     */
    public function getSubjectContentAttribute()
    {
        if ($this->relationLoaded('subjectContent')) {
            return $this->getRelation('subjectContent');
        }

        return SubjectContent::where('subject_id', $this->subject_id)
            ->where('session_number', $this->session_number)
            ->first();
    }
}
