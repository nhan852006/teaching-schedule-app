<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tự động kiểm tra và bổ sung các trường thông tin giáo viên cho bảng users nếu chưa có
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_title')) {
                        $table->string('academic_title')->nullable()->default('ThS')->after('name');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')) {
                        $table->string('phone')->nullable()->after('email');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'department')) {
                        $table->string('department')->nullable()->default('Khoa Công Nghệ Thông Tin')->after('phone');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
                        $table->string('status')->default('active')->after('department');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'notes')) {
                        $table->text('notes')->nullable()->after('status');
                    }
                });
            }
        } catch (\Throwable $e) {
            // Bỏ qua nếu CSDL chưa kết nối
        }
    }
}
