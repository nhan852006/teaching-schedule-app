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
        'subject_type',
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

    /**
     * Lấy loại giáo án của môn học (Tự động suy luận nếu chưa cài đặt)
     */
    public function getEffectiveTypeAttribute(): string
    {
        if (!empty($this->attributes['subject_type'])) {
            return $this->attributes['subject_type'];
        }

        $hasTheory = $this->contents()->where('theory_time', '>', 0)->exists();
        $hasPractice = $this->contents()->where('practice_time', '>', 0)->exists();

        if ($hasTheory && $hasPractice) {
            return 'integrated';
        } elseif ($hasPractice && !$hasTheory) {
            return 'practice';
        } elseif ($hasTheory && !$hasPractice) {
            return 'theory';
        }

        return 'integrated';
    }

    /**
     * Nhãn hiển thị loại môn học & mẫu giáo án tương ứng
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->effective_type) {
            'theory' => 'Lý thuyết (Mẫu 9a)',
            'practice' => 'Thực hành (Mẫu 9b)',
            default => 'Tích hợp (Mẫu 9c)',
        };
    }

    /**
     * Mã số mẫu giáo án (9a, 9b, 9c)
     */
    public function getFormCodeAttribute(): string
    {
        return match ($this->effective_type) {
            'theory' => '9a',
            'practice' => '9b',
            default => '9c',
        };
    }
}
