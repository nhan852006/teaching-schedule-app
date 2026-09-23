<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'name',
        'total_sessions',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(SubjectContent::class, 'subject_id')->orderBy('session_number');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }
}
