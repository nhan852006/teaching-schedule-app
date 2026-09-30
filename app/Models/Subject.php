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

    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'schedules', 'subject_id', 'class_id')->distinct();
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

    /**
     * Thư mục lưu trữ các file giáo án mẫu Word (.docx) của môn học này
     */
    public function getTemplateDirectory(): string
    {
        $dir = storage_path("app/lesson_plan_templates/{$this->id}");
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Lấy đường dẫn file giáo án mẫu của 1 buổi học (nếu có)
     */
    public function getTemplatePathForSession(int $sessionNumber): ?string
    {
        $dir = $this->getTemplateDirectory();
        $candidates = [
            $dir . DIRECTORY_SEPARATOR . 'buoi_' . sprintf('%02d', $sessionNumber) . '.docx',
            $dir . DIRECTORY_SEPARATOR . "buoi_{$sessionNumber}.docx",
            $dir . DIRECTORY_SEPARATOR . 'buoi_' . sprintf('%02d', $sessionNumber) . '.doc',
            $dir . DIRECTORY_SEPARATOR . "buoi_{$sessionNumber}.doc",
        ];

        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                return $cand;
            }
        }

        // Tìm thêm theo regex phòng trường hợp đặt tên hoa/thường hoặc có khoảng trắng
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                if (preg_match('/^(?:buoi|bai|session)[_\-\s]*0*' . $sessionNumber . '\.docx?$/i', $file)) {
                    return $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }

        return null;
    }

    /**
     * Kiểm tra buổi học đã có file mẫu Word chưa
     */
    public function hasTemplateForSession(int $sessionNumber): bool
    {
        return !empty($this->getTemplatePathForSession($sessionNumber));
    }

    /**
     * Đếm tổng số buổi đã tải lên file mẫu Word
     */
    public function countUploadedTemplates(): int
    {
        $total = $this->total_sessions ?: 30;
        $count = 0;
        for ($i = 1; $i <= $total; $i++) {
            if ($this->hasTemplateForSession($i)) {
                $count++;
            }
        }
        return $count;
    }
}

