<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'academic_title',
        'phone',
        'department',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Họ tên kèm học vị (VD: ThS. Hoàng Văn Nhân)
     */
    public function getFullNameWithTitleAttribute(): string
    {
        $title = trim($this->academic_title ?? '');
        $name = trim($this->name);

        if ($title !== '') {
            // Tránh lặp nếu tên đã có sẵn học vị
            if (preg_match('/^(?:ThS|TS|PGS|GS|KS|CN)\.?\s+/iu', $name)) {
                return $name;
            }
            return "{$title}. {$name}";
        }

        return $name;
    }

    /**
     * Nhãn hiển thị trạng thái
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'inactive' => 'Tạm nghỉ / Đã khóa',
            default    => 'Đang giảng dạy',
        };
    }

    /**
     * Class CSS badge trạng thái
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            default    => 'bg-success-subtle text-success border border-success-subtle',
        };
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }
}
