<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;

// Trang chủ tự động chuyển đến Dashboard
Route::get('/', function () {
    return redirect()->route('schedules.index');
});

// Module Quản lý Giáo viên bộ môn
Route::prefix('teachers')->name('teachers.')->group(function () {
    Route::get('/', [TeacherController::class, 'index'])->name('index');
    Route::post('/', [TeacherController::class, 'store'])->name('store');
    Route::put('/{id}', [TeacherController::class, 'update'])->name('update');
    Route::delete('/{id}', [TeacherController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [TeacherController::class, 'toggleStatus'])->name('toggle_status');
    Route::get('/{id}/switch', [TeacherController::class, 'switchWorkspace'])->name('switch');
    Route::post('/{id}/assign-plan', [TeacherController::class, 'assignPlan'])->name('assign_plan');
});

// Module Quản lý Môn học & Nội dung Buổi học
Route::prefix('subjects')->name('subjects.')->group(function () {
    Route::get('/', [SubjectController::class, 'index'])->name('index');
    Route::post('/', [SubjectController::class, 'store'])->name('store');
    Route::get('/{id}', [SubjectController::class, 'show'])->name('show');
    Route::put('/{id}', [SubjectController::class, 'update'])->name('update');
    Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('destroy');

    // Quản lý buổi học chi tiết của môn
    Route::post('/{subject_id}/sessions', [SubjectController::class, 'storeSession'])->name('sessions.store');
    Route::put('/{subject_id}/sessions/{content_id}', [SubjectController::class, 'updateSession'])->name('sessions.update');
    Route::delete('/{subject_id}/sessions/{content_id}', [SubjectController::class, 'destroySession'])->name('sessions.destroy');
    Route::post('/{subject_id}/sessions/batch-update', [SubjectController::class, 'batchUpdateSessions'])->name('sessions.batch_update');
});

Route::prefix('schedules')->name('schedules.')->group(function () {
    // 1. Dashboard chính của Giảng viên
    Route::get('/', [ScheduleController::class, 'index'])->name('index');

    // 2. Chuyển đổi Giảng viên đang làm việc (Teacher Switcher)
    Route::post('/teacher/switch', [ScheduleController::class, 'switchTeacher'])->name('teacher.switch');

    // 3. Tạo nhanh Môn học & Mô-đun các buổi học
    Route::post('/subjects/store-custom', [ScheduleController::class, 'storeSubjectWithModules'])->name('subjects.store_custom');

    // 4. Lập kế hoạch giảng dạy cá nhân tự động (Thứ 2 - Thứ 7)
    Route::post('/generate-plan', [ScheduleController::class, 'generateSchedulePlan'])->name('generate_plan');

    // 5. Thêm nhanh Lớp học
    Route::post('/classes/store-quick', [ScheduleController::class, 'storeClassQuick'])->name('classes.store_quick');

    // 6. Import CSV
    Route::post('/import-subject-contents', [ScheduleController::class, 'importSubjectContents'])->name('import.subject_contents');
    Route::post('/import-schedules', [ScheduleController::class, 'importSchedules'])->name('import.schedules');

    // 7. Trang chi tiết / Preview theo Lớp & Môn học
    Route::get('/class/{class_id}/subject/{subject_id}', [ScheduleController::class, 'preview'])->name('preview');

    // 8. Cập nhật Inline (AJAX) ngày dạy và ca dạy
    Route::post('/{id}/update-inline', [ScheduleController::class, 'updateInline'])->name('update_inline');

    // 9. Đồng bộ Google Calendar
    Route::post('/class/{class_id}/subject/{subject_id}/sync-calendar', [ScheduleController::class, 'syncGoogleCalendar'])->name('sync_calendar');

    // 10. Xuất Kế hoạch giảng dạy Mẫu số 8 ra file Word .docx
    Route::get('/class/{class_id}/subject/{subject_id}/export-word', [ScheduleController::class, 'exportWord'])->name('export_word');

    // 11. Báo nghỉ đột xuất & Tự động đôn lịch
    Route::post('/postpone-and-shift', [ScheduleController::class, 'postponeAndShift'])->name('postpone_and_shift');

    // 12. Xuất trọn bộ Sổ Giáo Án ra file Word .docx (Mẫu 9a, 9b, 9c)
    Route::get('/class/{class_id}/subject/{subject_id}/export-lesson-plans', [ScheduleController::class, 'exportLessonPlansBooklet'])->name('export_lesson_plans');

    // 13. Xuất lẻ giáo án của 1 buổi học cụ thể
    Route::get('/schedule/{schedule_id}/export-lesson-plan', [ScheduleController::class, 'exportSingleLessonPlan'])->name('export_single_lesson_plan');

    // 14. Cập nhật chi tiết giáo án của buổi học
    Route::post('/subject-content/{id}/update-lesson-plan', [ScheduleController::class, 'updateLessonPlan'])->name('update_lesson_plan');

    // 15. Cập nhật loại môn học (Tích hợp, Lý thuyết, Thực hành)
    Route::post('/subject/{id}/update-type', [ScheduleController::class, 'updateSubjectType'])->name('update_subject_type');

    // 16. Tải lên trọn gói giáo án mẫu file ZIP (.zip)
    Route::post('/subject/{subject_id}/upload-templates-zip', [ScheduleController::class, 'uploadLessonPlanTemplatesZip'])->name('subject.upload_templates_zip');

    // 17. Tải lên lẻ file giáo án mẫu cho 1 buổi học (.docx)
    Route::post('/subject/{subject_id}/session/{session_number}/upload-template', [ScheduleController::class, 'uploadSingleTemplate'])->name('subject.upload_single_template');

    // 18. Xoá file giáo án mẫu của 1 buổi học
    Route::post('/subject/{subject_id}/session/{session_number}/delete-template', [ScheduleController::class, 'deleteTemplate'])->name('subject.delete_template');

    // 19. Tải về file giáo án mẫu gốc của 1 buổi học
    Route::get('/subject/{subject_id}/session/{session_number}/download-template', [ScheduleController::class, 'downloadTemplate'])->name('subject.download_template');

    // 20. Tải file Giáo Án PDF của 1 buổi học cụ thể (.pdf)
    Route::get('/schedule/{schedule_id}/export-lesson-plan-pdf', [ScheduleController::class, 'exportSingleLessonPlanPdf'])->name('export_single_lesson_plan_pdf');

    // 21. Xem trước & In Giáo Án dạng PDF chuẩn A4
    Route::get('/schedule/{schedule_id}/view-lesson-plan-pdf', [ScheduleController::class, 'viewLessonPlanPdf'])->name('view_lesson_plan_pdf');
});

