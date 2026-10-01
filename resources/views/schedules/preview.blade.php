@extends('layouts.app')

@section('title', 'Kế hoạch giảng dạy: ' . $class->name . ' - ' . $subject->name)
@section('meta_description', 'Xem chi tiết kế hoạch giảng dạy, số tiết lý thuyết, thực hành, đồng bộ Google Calendar và xuất sổ tay giáo án cho lớp ' . $class->name . ', môn ' . $subject->name)

@section('header_actions')
    <a href="{{ route('schedules.index') }}" class="btn btn-sm btn-outline-secondary px-3" aria-label="Quay về bảng điều khiển">
        <i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i> Về Dashboard
    </a>
    <!-- Nút Đồng bộ Google Calendar -->
    <button id="btnSyncCalendar" class="btn btn-sm btn-academic-outline px-3 shadow-sm" onclick="syncCalendar()" aria-label="Đồng bộ lịch lên Google Calendar">
        <i class="fa-brands fa-google text-danger me-1" aria-hidden="true"></i> Đồng bộ Calendar
    </button>
    <!-- Nút Xuất Kế hoạch Mẫu 08 -->
    <a href="{{ route('schedules.export_word', ['class_id' => $class->id, 'subject_id' => $subject->id]) }}" 
       class="btn btn-sm btn-academic-primary px-3 shadow-sm" aria-label="Xuất Kế hoạch giảng dạy Mẫu 08 (.docx)">
        <i class="fa-solid fa-file-word me-1" aria-hidden="true"></i> Xuất Mẫu 08 (.docx)
    </a>
    <!-- Nút Tải lên Giáo án mẫu (.zip) -->
    <button type="button" class="btn btn-sm btn-outline-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadTemplatesZipModal" aria-label="Tải lên trọn gói giáo án mẫu Word">
        <i class="fa-solid fa-cloud-arrow-up me-1" aria-hidden="true"></i> Tải Mẫu Word (.zip)
        <span class="badge bg-success ms-1">{{ $subject->countUploadedTemplates() }}/{{ $subject->total_sessions ?: $schedules->count() }}</span>
    </button>
    <!-- Nút Xuất Sổ Giáo Án -->
    <a href="{{ route('schedules.export_lesson_plans', ['class_id' => $class->id, 'subject_id' => $subject->id]) }}" 
       class="btn btn-sm btn-academic-outline px-3 shadow-sm" aria-label="Xuất trọn bộ Sổ Giáo Án (.docx)">
        <i class="fa-solid fa-book-bookmark text-primary me-1" aria-hidden="true"></i> Xuất Sổ Giáo Án (.docx)
    </a>
@endsection

@section('content')
<div class="container-fluid px-lg-5 py-4">

    <!-- Breadcrumb Navigation -->
    <nav aria-label="Đường dẫn phân cấp học vụ" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="{{ route('schedules.index') }}" class="text-decoration-none text-muted">Trang chủ Lịch dạy</a></li>
            <li class="breadcrumb-item text-muted">Hồ sơ Lớp {{ $class->name }}</li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">{{ $subject->code }}</li>
        </ol>
    </nav>

    <!-- Page Title & Header Info -->
    <div class="card-academic p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-2 font-monospace">HỌC PHẦN CHUYÊN NGÀNH</span>
                <h1 class="h3 fw-bold text-dark mb-2">
                    {{ $subject->name }}
                </h1>
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                    <span><i class="fa-solid fa-users me-1 text-secondary" aria-hidden="true"></i>Lớp học: <strong class="text-dark">{{ $class->name }}</strong></span>
                    <span>&middot;</span>
                    <span><i class="fa-solid fa-barcode me-1 text-secondary" aria-hidden="true"></i>Mã học phần: <strong class="text-dark">{{ $subject->code }}</strong></span>
                    <span>&middot;</span>
                    <span><i class="fa-solid fa-calendar-check me-1 text-secondary" aria-hidden="true"></i>Tổng quy mô: <strong class="text-dark">{{ $schedules->count() }} buổi học</strong></span>
                    <span>&middot;</span>
                    <a href="{{ route('subjects.show', $subject->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" title="Xem & Chỉnh sửa nội dung chi tiết bài giảng của môn học">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Quản lý nội dung môn
                    </a>
                </div>
                <div class="mt-2 d-flex align-items-center gap-2">
                    <span class="text-muted small"><i class="fa-solid fa-file-signature me-1 text-secondary"></i>Mẫu giáo án:</span>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" id="subjectTypeBadge">
                        <i class="fa-solid fa-book-open me-1"></i> {{ $subject->type_label }}
                    </span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-xs btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem;">
                            Đổi mẫu
                        </button>
                        <ul class="dropdown-menu shadow-sm small">
                            <li><a class="dropdown-item {{ $subject->effective_type === 'integrated' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('integrated')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'integrated' ? '' : 'invisible' }}"></i> Tích hợp (Mẫu 9c)</a></li>
                            <li><a class="dropdown-item {{ $subject->effective_type === 'theory' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('theory')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'theory' ? '' : 'invisible' }}"></i> Lý thuyết (Mẫu 9a)</a></li>
                            <li><a class="dropdown-item {{ $subject->effective_type === 'practice' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('practice')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'practice' ? '' : 'invisible' }}"></i> Thực hành (Mẫu 9b)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="bg-light p-3 rounded-2 border text-start d-inline-block">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Quy định giảng dạy</div>
                    <div class="small text-dark mt-1">
                        <div>&bull; Ca sáng: <strong>07:30 - 11:30</strong> (5 tiết)</div>
                        <div>&bull; Ca chiều: <strong>13:00 - 17:00</strong> (5 tiết)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Table Card -->
    <div class="card-academic p-4 bg-white">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <div>
                <h2 class="h5 mb-0 fw-bold text-dark">
                    <i class="fa-solid fa-book-open-reader text-primary me-2" aria-hidden="true"></i>Chi tiết Tiến độ Giáo án & Lịch học
                </h2>
                <p class="text-muted small mb-0">Chỉnh sửa ngày và ca dạy trực tiếp; dữ liệu sẽ được lưu tự động trên hệ thống</p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <button type="button" class="btn btn-warning btn-sm fw-bold shadow-sm" onclick="openPostponeModal()">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Báo Nghỉ & Đôn Lịch
                </button>
                <span class="badge bg-light text-dark border p-2">
                    Tổng cộng: <strong>{{ $schedules->count() }} buổi</strong>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-academic border" aria-label="Bảng chi tiết các buổi học của học phần">
                <caption class="visually-hidden">Danh sách các buổi học chi tiết, ngày dạy, ca học và nội dung giáo án</caption>
                <thead>
                    <tr class="text-center align-middle">
                        <th scope="col" style="width: 75px;">Buổi</th>
                        <th scope="col" style="width: 175px;">Ngày giảng dạy</th>
                        <th scope="col" style="width: 135px;">Ca học</th>
                        <th scope="col" class="text-start">Nội dung bài học & Mục tiêu</th>
                        <th scope="col" style="width: 80px;">Tiết LT</th>
                        <th scope="col" style="width: 80px;">Tiết TH</th>
                        <th scope="col" style="width: 80px;">Tiết KT</th>
                        <th scope="col" style="width: 135px;">Đồng bộ Calendar</th>
                        <th scope="col" style="width: 105px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $item)
                        @php
                            $content = $item->subjectContent;
                        @endphp
                        <tr id="row-{{ $item->id }}">
                            <td class="text-center fw-bold text-secondary font-monospace">
                                #{{ $item->session_number }}
                            </td>

                            <!-- Input Ngày dạy -->
                            <td>
                                <input type="date" 
                                       class="form-control form-control-sm" 
                                       id="date-{{ $item->id }}" 
                                       value="{{ $item->teaching_date ? $item->teaching_date->format('Y-m-d') : '' }}"
                                       onchange="markRowAsDirty({{ $item->id }})"
                                       aria-label="Chọn ngày dạy buổi {{ $item->session_number }}">
                            </td>

                            <!-- Select Ca dạy -->
                            <td>
                                <select class="form-select form-select-sm" 
                                        id="shift-{{ $item->id }}"
                                        onchange="markRowAsDirty({{ $item->id }})"
                                        aria-label="Chọn ca dạy buổi {{ $item->session_number }}">
                                    <option value="Sáng" {{ $item->session_shift === 'Sáng' ? 'selected' : '' }}>Ca Sáng</option>
                                    <option value="Chiều" {{ $item->session_shift === 'Chiều' ? 'selected' : '' }}>Ca Chiều</option>
                                </select>
                            </td>

                            <!-- Nội dung bài học -->
                            <td class="text-start">
                                <div class="text-wrap" style="max-width: 520px; line-height: 1.5; font-size: 0.88rem;">
                                    {{ $content->content ?? 'Chưa cập nhật nội dung cho buổi này' }}
                                </div>
                                <!-- Tình trạng File mẫu Word của buổi này -->
                                <div class="mt-2 pt-1 border-top d-flex align-items-center flex-wrap gap-2" id="template-status-{{ $item->session_number }}">
                                    @if($subject->hasTemplateForSession($item->session_number))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-circle-check me-1"></i>Đã có mẫu Word
                                        </span>
                                        <a href="{{ route('schedules.export_single_lesson_plan', ['schedule_id' => $item->id]) }}" 
                                           class="btn btn-xs btn-success py-0 px-2 shadow-sm text-white fw-semibold" 
                                           title="Tải giáo án Word đã tự động điền Lớp và Ngày dạy theo lịch của lớp này" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-word me-1"></i>Tải giáo án (.docx)
                                        </a>
                                        <a href="{{ route('schedules.export_single_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                           class="btn btn-xs btn-danger py-0 px-2 shadow-sm text-white fw-semibold" 
                                           title="Tải giáo án PDF đã tự động điền Lớp và Ngày dạy (.pdf)" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-pdf me-1"></i>Tải PDF (.pdf)
                                        </a>
                                        <a href="{{ route('schedules.view_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                           target="_blank"
                                           class="btn btn-xs btn-outline-dark py-0 px-2 shadow-sm fw-semibold" 
                                           title="Xem và In PDF trực tiếp chuẩn A4" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-print me-1"></i>Xem & In PDF
                                        </a>
                                        <a href="{{ route('schedules.subject.download_template', ['subject_id' => $subject->id, 'session_number' => $item->session_number]) }}" 
                                           class="btn btn-xs btn-outline-secondary py-0 px-1" title="Tải file mẫu gốc chưa điền thông tin" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-download me-1"></i>File gốc
                                        </a>
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" 
                                                onclick="openSingleUploadModal({{ $item->session_number }})" 
                                                title="Tải đè / Cập nhật file mẫu mới" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i>Đổi mẫu
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" 
                                                onclick="deleteSingleTemplate({{ $subject->id }}, {{ $item->session_number }})" 
                                                title="Xoá file mẫu buổi này" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0" style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-lines me-1"></i>Dùng mẫu sinh tự động
                                        </span>
                                        <a href="{{ route('schedules.export_single_lesson_plan', ['schedule_id' => $item->id]) }}" 
                                           class="btn btn-xs btn-primary py-0 px-2 shadow-sm text-white fw-semibold" 
                                           title="Tải giáo án Word tự động sinh theo mẫu quy định" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-word me-1"></i>Tải giáo án (.docx)
                                        </a>
                                        <a href="{{ route('schedules.export_single_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                           class="btn btn-xs btn-danger py-0 px-2 shadow-sm text-white fw-semibold" 
                                           title="Tải giáo án PDF theo mẫu quy định (.pdf)" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-file-pdf me-1"></i>Tải PDF (.pdf)
                                        </a>
                                        <a href="{{ route('schedules.view_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                           target="_blank"
                                           class="btn btn-xs btn-outline-dark py-0 px-2 shadow-sm fw-semibold" 
                                           title="Xem và In PDF trực tiếp chuẩn A4" 
                                           style="font-size: 0.74rem;">
                                            <i class="fa-solid fa-print me-1"></i>Xem & In PDF
                                        </a>
                                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-1" 
                                                onclick="openSingleUploadModal({{ $item->session_number }})" 
                                                title="Tải lên file mẫu Word riêng cho buổi này" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-plus me-1"></i>Thêm mẫu .docx
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center font-monospace">{{ $content->theory_time ?? 0 }}</td>
                            <td class="text-center font-monospace">{{ $content->practice_time ?? 0 }}</td>
                            <td class="text-center font-monospace">
                                @if(($content->test_time ?? 0) > 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold">
                                        {{ $content->test_time }}
                                    </span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>

                            <!-- Trạng thái Đồng bộ -->
                            <td class="text-center" id="status-container-{{ $item->id }}">
                                @if($item->sync_status === 'synced')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-check me-1" aria-hidden="true"></i>synced</span>
                                @elseif($item->sync_status === 'modified')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="fa-solid fa-pen me-1" aria-hidden="true"></i>modified</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i>pending</span>
                                @endif
                            </td>

                            <!-- Button lưu trực tiếp & Báo nghỉ -->
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary" 
                                            id="btn-save-{{ $item->id }}"
                                            title="Lưu thay đổi ngày/ca"
                                            aria-label="Lưu thay đổi cho buổi {{ $item->session_number }}"
                                            onclick="saveRowData({{ $item->id }})">
                                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-warning text-dark" 
                                            title="Báo nghỉ buổi #{{ $item->session_number }} & Tự động đôn lịch"
                                            aria-label="Báo nghỉ buổi {{ $item->session_number }}"
                                            onclick="openPostponeModal({{ $item->id }}, '{{ $item->teaching_date ? $item->teaching_date->format('Y-m-d') : '' }}', '{{ $item->session_shift }}', {{ $item->session_number }})">
                                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                                    </button>
                                    <!-- Nút Xem/Soạn chi tiết Giáo án -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info" 
                                            title="Xem & Soạn chi tiết giáo án buổi #{{ $item->session_number }}"
                                            aria-label="Soạn giáo án buổi {{ $item->session_number }}"
                                            data-session="{{ $item->session_number }}"
                                            data-content-id="{{ $content ? $content->id : '' }}"
                                            data-title="{{ $content ? $content->title_clean : ('Buổi ' . $item->session_number) }}"
                                            data-knowledge="{{ $content?->objective_knowledge ?? '' }}"
                                            data-skills="{{ $content?->objective_skills ?? '' }}"
                                            data-autonomy="{{ $content?->objective_autonomy ?? '' }}"
                                            data-equipment="{{ $content?->teaching_equipment ?? '' }}"
                                            data-form="{{ $content?->teaching_form ?? '' }}"
                                            data-leadin="{{ $content?->activity_lead_in ?? '' }}"
                                            data-main="{{ $content?->activity_main ?? '' }}"
                                            data-reinforce="{{ $content?->activity_reinforce ?? '' }}"
                                            data-selfstudy="{{ $content?->activity_self_study ?? '' }}"
                                            data-reference="{{ $content?->reference_material ?? '' }}"
                                            data-experience="{{ $content?->experience_note ?? '' }}"
                                            onclick="openLessonPlanModal(this)">
                                        <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                                    </button>
                                    <!-- Nút Tải Giáo án Word buổi này -->
                                    <a href="{{ route('schedules.export_single_lesson_plan', ['schedule_id' => $item->id]) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Tải Giáo án Word buổi #{{ $item->session_number }} (.docx)" 
                                       aria-label="Tải Giáo án Word buổi {{ $item->session_number }}">
                                        <i class="fa-solid fa-file-word" aria-hidden="true"></i>
                                    </a>
                                    <!-- Nút Tải Giáo án PDF buổi này -->
                                    <a href="{{ route('schedules.export_single_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                       class="btn btn-sm btn-outline-danger" 
                                       title="Tải Giáo án PDF buổi #{{ $item->session_number }} (.pdf)" 
                                       aria-label="Tải Giáo án PDF buổi {{ $item->session_number }}">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                    </a>
                                    <!-- Nút Xem & In PDF chuẩn A4 -->
                                    <a href="{{ route('schedules.view_lesson_plan_pdf', ['schedule_id' => $item->id]) }}" 
                                       target="_blank"
                                       class="btn btn-sm btn-outline-dark" 
                                       title="Xem & In PDF chuẩn A4 buổi #{{ $item->session_number }}" 
                                       aria-label="In PDF buổi {{ $item->session_number }}">
                                        <i class="fa-solid fa-print" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <div class="saving-indicator text-muted mt-1" id="saving-{{ $item->id }}" style="display: none;" aria-live="polite">
                                    <i class="fa-solid fa-spinner fa-spin text-primary" aria-hidden="true"></i>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-2" aria-hidden="true"></i> Chưa có dữ liệu lịch dạy cho lớp và môn học này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Báo Nghỉ & Tự Động Đôn Lịch -->
<div class="modal fade" id="postponeShiftModal" tabindex="-1" aria-labelledby="postponeShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-warning text-dark border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="postponeShiftModalLabel">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i> Báo Nghỉ & Tự Động Đôn Lịch Giảng Dạy
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPostponeShift" onsubmit="submitPostponeShift(event)">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-info-subtle small py-2 px-3 mb-3">
                        <i class="fa-solid fa-circle-info me-1 text-info-emphasis"></i>
                        Hệ thống sẽ dời buổi học này sang ngày thay thế, đồng thời <strong>tự động đôn lại toàn bộ các buổi học tiếp theo</strong> theo đúng tiến trình bài giảng tăng dần theo dòng thời gian.
                    </div>

                    <!-- Chọn Buổi Báo Nghỉ -->
                    <div class="mb-3">
                        <label for="modalScheduleId" class="form-label small fw-bold text-dark">
                            Chọn Buổi học báo nghỉ đột xuất <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="modalScheduleId" required onchange="onSelectOffSchedule(this.value)">
                            <option value="">-- Chọn buổi nghỉ --</option>
                            @foreach($schedules as $s)
                                <option value="{{ $s->id }}" 
                                        data-date="{{ $s->teaching_date ? $s->teaching_date->format('Y-m-d') : '' }}"
                                        data-shift="{{ $s->session_shift }}"
                                        data-number="{{ $s->session_number }}">
                                    Buổi #{{ $s->session_number }}: Ngày {{ $s->teaching_date ? $s->teaching_date->format('d/m/Y') : 'Chưa có' }} (Ca {{ $s->session_shift }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Thông tin buổi học bị hoãn -->
                    <div class="p-2 mb-3 bg-light rounded border text-muted small" id="offSessionInfo" style="display: none;">
                        <div>&bull; Ngày nghỉ: <strong class="text-danger" id="infoOffDate">...</strong></div>
                        <div>&bull; Ca học: <strong class="text-dark" id="infoOffShift">...</strong></div>
                    </div>

                    <!-- Ngày thay thế & Ca thay thế -->
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="modalReplacementDate" class="form-label small fw-bold text-dark">
                                Ngày học thay thế (Dạy bù) <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm" id="modalReplacementDate" required>
                        </div>
                        <div class="col-5">
                            <label for="modalReplacementShift" class="form-label small fw-bold text-dark">
                                Ca dạy bù <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="modalReplacementShift" required>
                                <option value="Sáng">Ca Sáng</option>
                                <option value="Chiều">Ca Chiều</option>
                            </select>
                        </div>
                    </div>

                    <!-- Gợi ý chọn nhanh ngày bù -->
                    <div class="mb-3">
                        <div class="text-muted small mb-1" style="font-size: 0.78rem;">Gợi ý chọn nhanh ngày bù:</div>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickPickDate(3)">+3 ngày</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickPickDate(7)">+7 ngày</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickPickLastDate()">Sau buổi cuối</button>
                        </div>
                    </div>

                    <!-- Lý do nghỉ (Tùy chọn) -->
                    <div class="mb-2">
                        <label for="modalReason" class="form-label small fw-bold text-dark">Lý do nghỉ (Tùy chọn)</label>
                        <input type="text" class="form-control form-control-sm" id="modalReason" placeholder="Ví dụ: Bận việc đột xuất, Nghỉ lễ...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold px-3 shadow-sm" id="btnSubmitPostpone">
                        <i class="fa-solid fa-check me-1"></i> Xác Nhận Đôn Lịch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Soạn Chi Tiết Giáo Án -->
<div class="modal fade" id="lessonPlanModal" tabindex="-1" aria-labelledby="lessonPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-academic-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="lessonPlanModalLabel">
                    <i class="fa-solid fa-book-open me-2"></i> Soạn Chi Tiết Giáo Án - Buổi #<span id="lpSessionNumber"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formLessonPlan" onsubmit="submitLessonPlan(event)">
                <input type="hidden" id="lpContentId" value="">
                <div class="modal-body p-4">
                    <div class="alert alert-secondary small py-2 px-3 mb-3 border-0 bg-light">
                        <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                        <strong>Tiêu đề bài học:</strong> <span id="lpLessonTitle" class="text-dark fw-bold"></span>
                        <div class="text-muted mt-1 small">Nếu bạn để trống bất kỳ trường nào, khi xuất file Word hệ thống sẽ <strong>tự động tạo nội dung mẫu chuẩn sư phạm</strong> theo tên bài học của bạn.</div>
                    </div>

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs small fw-bold mb-3" id="lpTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-muc-tieu" data-bs-toggle="tab" data-bs-target="#tabMucTieu" type="button" role="tab">1. Mục tiêu & Đồ dùng</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-hoat-dong" data-bs-toggle="tab" data-bs-target="#tabHoatDong" type="button" role="tab">2. Hoạt động dạy học</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-tong-ket" data-bs-toggle="tab" data-bs-target="#tabTongKet" type="button" role="tab">3. Rút kinh nghiệm & Tài liệu</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="lpTabContent">
                        <!-- Tab 1: Mục tiêu & Đồ dùng -->
                        <div class="tab-pane fade show active" id="tabMucTieu" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Kiến thức đạt được</label>
                                <textarea class="form-control form-control-sm" id="lpObjectiveKnowledge" rows="2" placeholder="Ví dụ: Trình bày và phân tích được các khái niệm..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Kỹ năng đạt được</label>
                                <textarea class="form-control form-control-sm" id="lpObjectiveSkills" rows="2" placeholder="Ví dụ: Thực hiện thành thạo thao tác cấu hình..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Mức độ tự chủ và trách nhiệm</label>
                                <textarea class="form-control form-control-sm" id="lpObjectiveAutonomy" rows="2" placeholder="Ví dụ: Rèn luyện tác phong công nghiệp, an toàn lao động..."></textarea>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark">Đồ dùng và trang thiết bị dạy học</label>
                                    <input type="text" class="form-control form-control-sm" id="lpTeachingEquipment" placeholder="Phòng máy tính, máy chiếu, bảng, viết...">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-dark">Hình thức tổ chức dạy học</label>
                                    <input type="text" class="form-control form-control-sm" id="lpTeachingForm" placeholder="Tập trung toàn lớp, hướng dẫn nhóm...">
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Hoạt động dạy học -->
                        <div class="tab-pane fade" id="tabHoatDong" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">1. Dẫn nhập (Khởi động, tạo tâm thế)</label>
                                <textarea class="form-control form-control-sm" id="lpActivityLeadIn" rows="2" placeholder="Ổn định lớp, điểm danh, gợi mở vấn đề..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">2. Hoạt động chính (Giảng bài mới / Hướng dẫn / Giải quyết vấn đề)</label>
                                <textarea class="form-control form-control-sm" id="lpActivityMain" rows="3" placeholder="Giáo viên trình bày bài học, làm mẫu; Học sinh thực hành..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">3. Củng cố kiến thức / Kết thúc bài</label>
                                <textarea class="form-control form-control-sm" id="lpActivityReinforce" rows="2" placeholder="Tổng kết kiến thức trọng tâm, đánh giá kết quả luyện tập..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">4. Hướng dẫn tự học / Tự rèn luyện</label>
                                <textarea class="form-control form-control-sm" id="lpActivitySelfStudy" rows="2" placeholder="Ôn lại kiến thức, chuẩn bị bài tiếp theo..."></textarea>
                            </div>
                        </div>

                        <!-- Tab 3: Rút kinh nghiệm & Tài liệu -->
                        <div class="tab-pane fade" id="tabTongKet" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nguồn tài liệu tham khảo</label>
                                <textarea class="form-control form-control-sm" id="lpReferenceMaterial" rows="2" placeholder="Giáo trình, sách tham khảo, tài liệu mạng..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Rút kinh nghiệm tổ chức thực hiện</label>
                                <textarea class="form-control form-control-sm" id="lpExperienceNote" rows="3" placeholder="Ghi nhận tình hình học tập, lưu ý cần cải thiện cho buổi sau..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm" id="btnSubmitLessonPlan">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Giáo Án
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tải lên trọn gói Giáo án mẫu (.zip) -->
<div class="modal fade" id="uploadTemplatesZipModal" tabindex="-1" aria-labelledby="uploadTemplatesZipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="uploadTemplatesZipModalLabel">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Tải Lên Gói Giáo Án Mẫu Word (.zip)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('schedules.subject.upload_templates_zip', ['subject_id' => $subject->id]) }}" 
                  method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-success border-success-subtle small py-2 px-3 mb-3">
                        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-check me-1"></i> Quy tắc tự động ghép nối giáo án:</div>
                        <ul class="mb-0 ps-3" style="line-height: 1.6;">
                            <li>Nén toàn bộ các file giáo án của môn học thành 1 file <strong>.zip</strong>.</li>
                            <li>Tên file Word trong zip đặt theo mẫu: <code>buoi_01.docx</code>, <code>buoi_02.docx</code>, ..., <code>buoi_22.docx</code>.</li>
                            <li>Hệ thống bảo toàn 100% hình ảnh minh hoạ, bảng biểu, sơ đồ trong file Word gốc của Thầy.</li>
                            <li>Tự động điền <strong>Thực hiện ngày: dd/mm/yyyy - Lớp: TênLớp</strong> (Phương án A).</li>
                            <li>Tự động tính ngày ký duyệt <strong>trước ngày dạy 1 tuần</strong> và điền tên Giáo viên phân công.</li>
                            <li>Khi xuất <strong>Sổ Giáo Án</strong>, hệ thống tự động gộp tất cả các buổi thành <strong>1 file Word duy nhất</strong> kèm Trang Bìa.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="zip_file" class="form-label small fw-bold text-dark">
                            Chọn file nén (.zip) chứa 22 file giáo án mẫu <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" id="zip_file" name="zip_file" accept=".zip" required>
                        <div class="form-text small text-muted">Dung lượng tối đa 100MB. Các file bên trong có định dạng chuẩn .docx</div>
                    </div>

                    <div class="p-3 bg-light rounded-2 border small text-muted">
                        <div>&bull; Môn học áp dụng: <strong class="text-dark">{{ $subject->name }}</strong> ({{ $subject->code }})</div>
                        <div>&bull; Số file mẫu đã có trên hệ thống: <strong class="text-success">{{ $subject->countUploadedTemplates() }}/{{ $subject->total_sessions ?: $schedules->count() }} buổi</strong></div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 shadow-sm">
                        <i class="fa-solid fa-upload me-1"></i> Tải Lên & Khớp Buổi Dạy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tải lên lẻ 1 file giáo án mẫu (.docx) -->
<div class="modal fade" id="uploadSingleTemplateModal" tabindex="-1" aria-labelledby="uploadSingleTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title fw-bold" id="uploadSingleTemplateModalLabel">
                    <i class="fa-solid fa-file-arrow-up me-2"></i> File Mẫu Buổi #<span id="singleModalSessionNumber">1</span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formSingleTemplate" onsubmit="submitSingleTemplate(event)">
                <input type="hidden" id="singleSessionInput" value="1">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="single_template_file" class="form-label small fw-bold text-dark">
                            Chọn file Word (.docx) <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" id="single_template_file" accept=".docx,.doc" required>
                        <div class="form-text small" style="font-size: 0.72rem;">File sẽ được lưu làm khuôn mẫu cho buổi học này.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-top">
                    <button type="button" class="btn btn-xs btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-xs btn-primary px-3 shadow-sm" id="btnSubmitSingleTemplate">
                        <i class="fa-solid fa-upload me-1"></i> Lưu File Mẫu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function markRowAsDirty(scheduleId) {
        saveRowData(scheduleId);
    }

    function saveRowData(scheduleId) {
        const dateInput = document.getElementById(`date-${scheduleId}`);
        const shiftSelect = document.getElementById(`shift-${scheduleId}`);
        const btnSave = document.getElementById(`btn-save-${scheduleId}`);
        const savingIndicator = document.getElementById(`saving-${scheduleId}`);
        const statusContainer = document.getElementById(`status-container-${scheduleId}`);

        btnSave.classList.add('d-none');
        savingIndicator.style.display = 'block';

        fetch(`{{ url('schedules') }}/${scheduleId}/update-inline`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                teaching_date: dateInput.value,
                session_shift: shiftSelect.value
            })
        })
        .then(response => response.json())
        .then(data => {
            savingIndicator.style.display = 'none';
            btnSave.classList.remove('d-none');

            if (data.success) {
                let badgeHtml = '';
                if (data.sync_status === 'synced') {
                    badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-check me-1" aria-hidden="true"></i>synced</span>';
                } else if (data.sync_status === 'modified') {
                    badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="fa-solid fa-pen me-1" aria-hidden="true"></i>modified</span>';
                } else {
                    badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i>pending</span>';
                }
                statusContainer.innerHTML = badgeHtml;

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire('Lỗi!', data.message || 'Không thể cập nhật.', 'error');
            }
        })
        .catch(err => {
            savingIndicator.style.display = 'none';
            btnSave.classList.remove('d-none');
            Swal.fire('Lỗi kết nối', 'Không thể gửi dữ liệu đến máy chủ', 'error');
        });
    }

    function syncCalendar() {
        const btnSync = document.getElementById('btnSyncCalendar');
        const originalHtml = btnSync.innerHTML;

        btnSync.disabled = true;
        btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1" aria-hidden="true"></i> Đang đồng bộ...';

        fetch(`{{ route('schedules.sync_calendar', ['class_id' => $class->id, 'subject_id' => $subject->id]) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btnSync.disabled = false;
            btnSync.innerHTML = originalHtml;

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    confirmButtonText: 'Tải lại trang'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Thông báo đồng bộ', data.message, 'warning');
            }
        })
        .catch(err => {
            btnSync.disabled = false;
            btnSync.innerHTML = originalHtml;
            Swal.fire('Lỗi', 'Có lỗi xảy ra trong quá trình gọi Google Calendar API.', 'error');
        });
    }

    // Quản lý Modal Báo Nghỉ & Tự Động Đôn Lịch
    let postponeModal = null;
    const scheduleDates = [
        @foreach($schedules as $s)
            @if($s->teaching_date)
                "{{ $s->teaching_date->format('Y-m-d') }}",
            @endif
        @endforeach
    ];

    function openPostponeModal(scheduleId = null, date = null, shift = null, sessionNumber = null) {
        const modalEl = document.getElementById('postponeShiftModal');
        if (!postponeModal && modalEl) {
            postponeModal = new bootstrap.Modal(modalEl);
        }

        const selectEl = document.getElementById('modalScheduleId');
        if (scheduleId) {
            selectEl.value = scheduleId;
            onSelectOffSchedule(scheduleId);
        } else {
            selectEl.value = '';
            document.getElementById('offSessionInfo').style.display = 'none';
            document.getElementById('modalReplacementDate').value = '';
        }

        if (postponeModal) {
            postponeModal.show();
        }
    }

    function onSelectOffSchedule(scheduleId) {
        const selectEl = document.getElementById('modalScheduleId');
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        const infoBox = document.getElementById('offSessionInfo');

        if (!scheduleId || !selectedOpt || !selectedOpt.value) {
            infoBox.style.display = 'none';
            return;
        }

        const date = selectedOpt.getAttribute('data-date');
        const shift = selectedOpt.getAttribute('data-shift');

        document.getElementById('infoOffDate').innerText = date ? formatDateVn(date) : 'Chưa xếp ngày';
        document.getElementById('infoOffShift').innerText = shift || 'Sáng';
        document.getElementById('modalReplacementShift').value = shift || 'Sáng';
        infoBox.style.display = 'block';

        quickPickLastDate();
    }

    function quickPickDate(daysToAdd) {
        const selectEl = document.getElementById('modalScheduleId');
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        let baseDateStr = (selectedOpt && selectedOpt.value) ? selectedOpt.getAttribute('data-date') : null;
        if (!baseDateStr) {
            baseDateStr = new Date().toISOString().slice(0, 10);
        }
        let d = new Date(baseDateStr);
        d.setDate(d.getDate() + daysToAdd);
        if (d.getDay() === 0) {
            d.setDate(d.getDate() + 1);
        }
        document.getElementById('modalReplacementDate').value = d.toISOString().slice(0, 10);
    }

    function quickPickLastDate() {
        if (scheduleDates.length > 0) {
            let lastDateStr = scheduleDates[scheduleDates.length - 1];
            let d = new Date(lastDateStr);
            d.setDate(d.getDate() + 3);
            if (d.getDay() === 0) {
                d.setDate(d.getDate() + 1);
            }
            document.getElementById('modalReplacementDate').value = d.toISOString().slice(0, 10);
        }
    }

    function formatDateVn(isoDate) {
        if (!isoDate) return '';
        const parts = isoDate.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return isoDate;
    }

    function submitPostponeShift(e) {
        e.preventDefault();

        const scheduleId = document.getElementById('modalScheduleId').value;
        const replacementDate = document.getElementById('modalReplacementDate').value;
        const replacementShift = document.getElementById('modalReplacementShift').value;
        const reason = document.getElementById('modalReason').value;

        if (!scheduleId || !replacementDate || !replacementShift) {
            Swal.fire('Lưu ý', 'Vui lòng chọn buổi học báo nghỉ và ngày dạy bù!', 'warning');
            return;
        }

        const btnSubmit = document.getElementById('btnSubmitPostpone');
        const originalHtml = btnSubmit.innerHTML;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang xử lý đôn lịch...';

        fetch("{{ route('schedules.postpone_and_shift') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                schedule_id: scheduleId,
                replacement_date: replacementDate,
                replacement_shift: replacementShift,
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHtml;

            if (data.success) {
                if (postponeModal) {
                    postponeModal.hide();
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Đôn lịch thành công!',
                    text: data.message,
                    confirmButtonText: 'Tải lại trang'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Không thể đôn lịch', data.message, 'error');
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHtml;
            Swal.fire('Lỗi kết nối', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    // --- QUẢN LÝ SOẠN & XUẤT GIÁO ÁN ---
    let lessonPlanModal = null;

    function openLessonPlanModal(btn) {
        if (!lessonPlanModal) {
            lessonPlanModal = new bootstrap.Modal(document.getElementById('lessonPlanModal'));
        }

        const session = btn.getAttribute('data-session');
        const contentId = btn.getAttribute('data-content-id');
        const title = btn.getAttribute('data-title');

        if (!contentId) {
            Swal.fire('Thông báo', 'Buổi học này chưa có nội dung bài giảng để soạn giáo án.', 'info');
            return;
        }

        document.getElementById('lpSessionNumber').innerText = session;
        document.getElementById('lpContentId').value = contentId;
        document.getElementById('lpLessonTitle').innerText = title;

        document.getElementById('lpObjectiveKnowledge').value = btn.getAttribute('data-knowledge') || '';
        document.getElementById('lpObjectiveSkills').value = btn.getAttribute('data-skills') || '';
        document.getElementById('lpObjectiveAutonomy').value = btn.getAttribute('data-autonomy') || '';
        document.getElementById('lpTeachingEquipment').value = btn.getAttribute('data-equipment') || '';
        document.getElementById('lpTeachingForm').value = btn.getAttribute('data-form') || '';
        document.getElementById('lpActivityLeadIn').value = btn.getAttribute('data-leadin') || '';
        document.getElementById('lpActivityMain').value = btn.getAttribute('data-main') || '';
        document.getElementById('lpActivityReinforce').value = btn.getAttribute('data-reinforce') || '';
        document.getElementById('lpActivitySelfStudy').value = btn.getAttribute('data-selfstudy') || '';
        document.getElementById('lpReferenceMaterial').value = btn.getAttribute('data-reference') || '';
        document.getElementById('lpExperienceNote').value = btn.getAttribute('data-experience') || '';

        const firstTab = new bootstrap.Tab(document.getElementById('tab-muc-tieu'));
        firstTab.show();

        lessonPlanModal.show();
    }

    function submitLessonPlan(e) {
        e.preventDefault();
        const contentId = document.getElementById('lpContentId').value;
        const btnSubmit = document.getElementById('btnSubmitLessonPlan');
        const originalHtml = btnSubmit.innerHTML;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

        const payload = {
            objective_knowledge: document.getElementById('lpObjectiveKnowledge').value,
            objective_skills: document.getElementById('lpObjectiveSkills').value,
            objective_autonomy: document.getElementById('lpObjectiveAutonomy').value,
            teaching_equipment: document.getElementById('lpTeachingEquipment').value,
            teaching_form: document.getElementById('lpTeachingForm').value,
            activity_lead_in: document.getElementById('lpActivityLeadIn').value,
            activity_main: document.getElementById('lpActivityMain').value,
            activity_reinforce: document.getElementById('lpActivityReinforce').value,
            activity_self_study: document.getElementById('lpActivitySelfStudy').value,
            reference_material: document.getElementById('lpReferenceMaterial').value,
            experience_note: document.getElementById('lpExperienceNote').value,
        };

        const updateUrl = "{{ url('schedules/subject-content') }}/" + contentId + "/update-lesson-plan";

        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHtml;

            if (data.success) {
                lessonPlanModal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Đã lưu giáo án!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Lỗi', data.message || 'Không thể lưu giáo án.', 'error');
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHtml;
            Swal.fire('Lỗi kết nối', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    function changeSubjectType(newType) {
        const subjectId = {{ $subject->id }};
        const updateTypeUrl = "{{ url('schedules/subject') }}/" + subjectId + "/update-type";

        Swal.fire({
            title: 'Đổi mẫu giáo án?',
            text: 'Bạn có chắc muốn chuyển đổi loại giáo án cho môn học này?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý đổi',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(updateTypeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ subject_type: newType })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Lỗi', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
                });
            }
        });
    }

    function openSingleUploadModal(sessionNumber) {
        document.getElementById('singleModalSessionNumber').textContent = sessionNumber;
        document.getElementById('singleSessionInput').value = sessionNumber;
        document.getElementById('single_template_file').value = '';
        const modal = new bootstrap.Modal(document.getElementById('uploadSingleTemplateModal'));
        modal.show();
    }

    function submitSingleTemplate(event) {
        event.preventDefault();
        const sessionNumber = document.getElementById('singleSessionInput').value;
        const fileInput = document.getElementById('single_template_file');
        if (!fileInput.files.length) {
            Swal.fire('Chú ý', 'Vui lòng chọn 1 file .docx', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('template_file', fileInput.files[0]);

        const subjectId = {{ $subject->id }};
        const uploadUrl = "{{ url('schedules/subject') }}/" + subjectId + "/session/" + sessionNumber + "/upload-template";

        const btn = document.getElementById('btnSubmitSingleTemplate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang tải lên...';

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Lưu File Mẫu';
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadSingleTemplateModal'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Lỗi', data.message || 'Không thể tải lên file mẫu.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Lưu File Mẫu';
            Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    function deleteSingleTemplate(subjectId, sessionNumber) {
        const deleteUrl = "{{ url('schedules/subject') }}/" + subjectId + "/session/" + sessionNumber + "/delete-template";

        Swal.fire({
            title: 'Xoá file mẫu Buổi #' + sessionNumber + '?',
            text: 'Buổi học này sẽ quay lại sử dụng mẫu giáo án sinh tự động.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Đồng ý xoá',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xoá!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Lỗi', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
                });
            }
        });
    }
</script>
@endpush
