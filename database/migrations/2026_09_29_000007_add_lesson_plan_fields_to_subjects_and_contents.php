<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'subject_type')) {
                $table->string('subject_type', 30)->default('integrated')->after('name')->comment('integrated=Tích hợp (Mẫu 9c), theory=Lý thuyết (Mẫu 9a), practice=Thực hành (Mẫu 9b)');
            }
        });

        Schema::table('subject_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_contents', 'objective_knowledge')) {
                $table->text('objective_knowledge')->nullable()->after('test_time');
                $table->text('objective_skills')->nullable()->after('objective_knowledge');
                $table->text('objective_autonomy')->nullable()->after('objective_skills');
                $table->text('teaching_equipment')->nullable()->after('objective_autonomy');
                $table->string('teaching_form', 100)->nullable()->after('teaching_equipment');
                $table->text('activity_lead_in')->nullable()->after('teaching_form');
                $table->text('activity_main')->nullable()->after('activity_lead_in');
                $table->text('activity_reinforce')->nullable()->after('activity_main');
                $table->text('activity_self_study')->nullable()->after('activity_reinforce');
                $table->text('reference_material')->nullable()->after('activity_self_study');
                $table->text('experience_note')->nullable()->after('reference_material');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
        });

        Schema::table('subject_contents', function (Blueprint $table) {
            $cols = [
                'objective_knowledge',
                'objective_skills',
                'objective_autonomy',
                'teaching_equipment',
                'teaching_form',
                'activity_lead_in',
                'activity_main',
                'activity_reinforce',
                'activity_self_study',
                'reference_material',
                'experience_note',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('subject_contents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
