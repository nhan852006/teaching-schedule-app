<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasFactory;

    // Chỉ định rõ tên bảng do tên Model là Classes
    protected $table = 'classes';

    protected $fillable = [
        'name',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }
}
