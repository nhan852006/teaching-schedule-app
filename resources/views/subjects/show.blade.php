@extends('layouts.app')

@section('title', 'Nội dung các buổi học: ' . $subject->name . ' (' . $subject->code . ')')
@section('meta_description', 'Xem và chỉnh sửa chi tiết nội dung các buổi học, số tiết lý thuyết, thực hành, kiểm tra và giáo án mẫu môn ' . $subject->name)

@section('header_actions')
    <a href="{{ route('subjects.index') }}" class="btn btn-sm btn-outline-secondary px-3" aria-label="Quay lại danh mục môn học">
        <i class="fa-solid fa-arrow-left me-1"></i> Danh mục Môn
    </a>
    <!-- Nút Tải lên Giáo án mẫu (.zip) -->
    <button type="button" class="btn btn-sm btn-outline-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadTemplatesZipModal" aria-label="Tải lên trọn gói giáo án mẫu Word">
        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Tải Mẫu Word (.zip)
        <span class="badge bg-success ms-1">{{ $templateCount }}/{{ $subject->contents->count() }}</span>
    </button>
    <!-- Nút Thêm buổi học -->
    <button type="button" class="btn btn-sm btn-academic-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSession" aria-label="Thêm buổi học mới">
        <i class="fa-solid fa-plus me-1"></i> Thêm Buổi Học
    </button>
@endsection

@section('content')
<div class="container-fluid px-lg-5 py-4">

    <!-- Breadcrumb Navigation -->
    <nav aria-label="Đường dẫn phân cấp học vụ" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="{{ route('schedules.index') }}" class="text-decoration-none text-muted">Trang chủ Lịch dạy</a></li>
            <li class="breadcrumb-item"><a href="{{ route('subjects.index') }}" class="text-decoration-none text-muted">Danh mục Môn học</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">{{ $subject->code }}</li>
        </ol>
    </nav>

    <!-- Page Title & Header Info Card -->
    <div class="card-academic p-4 mb-4 bg-white">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-2 font-monospace">
                    HỌC PHẦN CHUYÊN NGÀNH &middot; {{ $subject->code }}
                </span>
                <h1 class="h3 fw-bold text-dark mb-2">
                    {{ $subject->name }}
                </h1>
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                    <span><i class="fa-solid fa-barcode me-1 text-secondary"></i>Mã học phần: <strong class="text-dark">{{ $subject->code }}</strong></span>
                    <span>&middot;</span>
                    <span><i class="fa-solid fa-list-ol me-1 text-secondary"></i>Quy mô: <strong class="text-dark" id="headerTotalSessions">{{ $subject->contents->count() }} buổi học</strong></span>
                    <span>&middot;</span>
                    <span><i class="fa-solid fa-chalkboard-user me-1 text-secondary"></i>Giảng viên: <strong class="text-dark">{{ $subject->teacher?->name ?? 'Hoàng Văn Nhân' }}</strong></span>
                </div>

                <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-muted small"><i class="fa-solid fa-file-signature me-1 text-secondary"></i>Loại giáo án:</span>
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" id="subjectTypeBadge">
                        <i class="fa-solid fa-book-open me-1"></i> {{ $subject->type_label }}
                    </span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-xs btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem;">
                            Đổi loại
                        </button>
                        <ul class="dropdown-menu shadow-sm small">
                            <li><a class="dropdown-item {{ $subject->effective_type === 'integrated' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('integrated')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'integrated' ? '' : 'invisible' }}"></i> Tích hợp (Mẫu 9c)</a></li>
                            <li><a class="dropdown-item {{ $subject->effective_type === 'theory' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('theory')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'theory' ? '' : 'invisible' }}"></i> Lý thuyết (Mẫu 9a)</a></li>
                            <li><a class="dropdown-item {{ $subject->effective_type === 'practice' ? 'active' : '' }}" href="javascript:void(0)" onclick="changeSubjectType('practice')"><i class="fa-solid fa-check me-1 {{ $subject->effective_type === 'practice' ? '' : 'invisible' }}"></i> Thực hành (Mẫu 9b)</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tổng kết thời lượng đào tạo -->
            <div class="col-lg-4 text-lg-end">
                <div class="bg-light p-3 rounded-2 border text-start d-inline-block w-100" style="max-width: 320px;">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Thời lượng chương trình</div>
                    <div class="d-flex justify-content-between small text-dark mt-2">
                        <span>Lý thuyết (LT):</span>
                        <strong class="font-monospace text-primary" id="totalLT">{{ $totalTheory }} tiết</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-dark mt-1">
                        <span>Thực hành (TH):</span>
                        <strong class="font-monospace text-success" id="totalTH">{{ $totalPractice }} tiết</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-dark mt-1">
                        <span>Kiểm tra (KT):</span>
                        <strong class="font-monospace text-danger" id="totalKT">{{ $totalTest }} tiết</strong>
                    </div>
                    <div class="border-top mt-2 pt-2 d-flex justify-content-between small fw-bold">
                        <span>Tổng số tiết:</span>
                        <span class="font-monospace text-dark" id="totalAllHours">{{ $totalHours }} tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông báo Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-2 mb-3 py-2" role="alert">
            <div class="d-flex align-items-center small">
                <i class="fa-solid fa-circle-check fs-6 me-2 text-success" aria-hidden="true"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng thông báo"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-2 mb-3 py-2" role="alert">
            <div class="d-flex align-items-center small">
                <i class="fa-solid fa-triangle-exclamation fs-6 me-2 text-danger" aria-hidden="true"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng thông báo"></button>
        </div>
    @endif

    <!-- Main Content Table Card -->
    <div class="card-academic p-4 bg-white">
        <form action="{{ route('subjects.sessions.batch_update', ['subject_id' => $subject->id]) }}" method="POST" id="formBatchUpdate">
            @csrf
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h2 class="h5 mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-primary me-2"></i>Nội dung chi tiết từng Buổi học
                    </h2>
                    <p class="text-muted small mb-0">Thầy có thể chỉnh sửa trực tiếp nội dung bài giảng và số tiết; dữ liệu sẽ tự động đồng bộ sang tất cả các lớp đang học môn này</p>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Tất Cả Thay Đổi
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSession">
                        <i class="fa-solid fa-plus me-1"></i> Thêm Buổi Mới
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-academic border" id="sessionsTable">
                    <thead>
                        <tr class="text-center align-middle">
                            <th style="width: 75px;">Buổi</th>
                            <th class="text-start">Nội dung bài giảng / Bài học</th>
                            <th style="width: 90px;">Tiết LT</th>
                            <th style="width: 90px;">Tiết TH</th>
                            <th style="width: 90px;">Tiết KT</th>
                            <th style="width: 85px;">Tổng tiết</th>
                            <th style="width: 220px;">File Mẫu Word (.docx)</th>
                            <th style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsTableBody">
                        @forelse($subject->contents as $item)
                            <tr id="session-row-{{ $item->id }}">
                                <td class="text-center fw-bold text-secondary font-monospace">
                                    #{{ $item->session_number }}
                                </td>

                                <!-- Input Nội dung bài học -->
                                <td class="text-start">
                                    <textarea name="sessions[{{ $item->id }}][content]" 
                                              id="content-{{ $item->id }}" 
                                              class="form-control form-control-sm" 
                                              rows="2" 
                                              style="font-size: 0.88rem; line-height: 1.45;" 
                                              placeholder="Nhập tên bài học hoặc nội dung giảng dạy..." 
                                              required>{{ $item->content }}</textarea>
                                </td>

                                <!-- Input Tiết LT -->
                                <td class="text-center">
                                    <input type="number" 
                                           name="sessions[{{ $item->id }}][theory_time]" 
                                           id="theory-{{ $item->id }}" 
                                           class="form-control form-control-sm text-center font-monospace" 
                                           value="{{ $item->theory_time ?? 0 }}" 
                                           min="0" step="0.5" 
                                           onchange="recalcSessionTotal({{ $item->id }})">
                                </td>

                                <!-- Input Tiết TH -->
                                <td class="text-center">
                                    <input type="number" 
                                           name="sessions[{{ $item->id }}][practice_time]" 
                                           id="practice-{{ $item->id }}" 
                                           class="form-control form-control-sm text-center font-monospace" 
                                           value="{{ $item->practice_time ?? 0 }}" 
                                           min="0" step="0.5" 
                                           onchange="recalcSessionTotal({{ $item->id }})">
                                </td>

                                <!-- Input Tiết KT -->
                                <td class="text-center">
                                    <input type="number" 
                                           name="sessions[{{ $item->id }}][test_time]" 
                                           id="test-{{ $item->id }}" 
                                           class="form-control form-control-sm text-center font-monospace" 
                                           value="{{ $item->test_time ?? 0 }}" 
                                           min="0" step="0.5" 
                                           onchange="recalcSessionTotal({{ $item->id }})">
                                </td>

                                <!-- Tổng số tiết của buổi -->
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace px-2 py-1" id="total-badge-{{ $item->id }}">
                                        {{ ($item->theory_time ?? 0) + ($item->practice_time ?? 0) + ($item->test_time ?? 0) }}
                                    </span>
                                </td>

                                <!-- Tình trạng File mẫu Word của buổi này -->
                                <td class="text-start">
                                    <div class="d-flex align-items-center flex-wrap gap-1" id="template-status-{{ $item->session_number }}">
                                        @if($subject->hasTemplateForSession($item->session_number))
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.74rem;">
                                                <i class="fa-solid fa-file-circle-check me-1"></i>Có mẫu Word riêng
                                            </span>
                                            <a href="{{ route('schedules.subject.download_template', ['subject_id' => $subject->id, 'session_number' => $item->session_number]) }}" 
                                               class="btn btn-xs btn-outline-secondary py-0 px-1" title="Tải file mẫu gốc" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" 
                                                    onclick="openSingleUploadModal({{ $item->session_number }})" 
                                                    title="Đổi file mẫu" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-arrow-up-from-bracket"></i> Đổi
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1" 
                                                    onclick="deleteSingleTemplate({{ $subject->id }}, {{ $item->session_number }})" 
                                                    title="Xoá file mẫu" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @else
                                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.74rem;">
                                                <i class="fa-solid fa-file-lines me-1"></i>Mẫu tự động
                                            </span>
                                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1" 
                                                    onclick="openSingleUploadModal({{ $item->session_number }})" 
                                                    title="Tải lên file mẫu Word riêng" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-plus me-1"></i>Thêm .docx
                                            </button>
                                        @endif
                                    </div>
                                </td>

                                <!-- Thao tác từng buổi -->
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Nút Lưu nhanh buổi này (AJAX) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary" 
                                                title="Lưu nhanh buổi này" 
                                                onclick="saveSingleSession({{ $subject->id }}, {{ $item->id }})">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                        <!-- Nút Soạn chi tiết giáo án (Mục tiêu, dẫn nhập, ...) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-info" 
                                                title="Soạn chi tiết mục tiêu giáo án buổi #{{ $item->session_number }}" 
                                                data-session="{{ $item->session_number }}"
                                                data-content-id="{{ $item->id }}"
                                                data-title="{{ $item->title_clean }}"
                                                data-knowledge="{{ $item->objective_knowledge ?? '' }}"
                                                data-skills="{{ $item->objective_skills ?? '' }}"
                                                data-autonomy="{{ $item->objective_autonomy ?? '' }}"
                                                data-equipment="{{ $item->teaching_equipment ?? '' }}"
                                                data-form="{{ $item->teaching_form ?? '' }}"
                                                data-leadin="{{ $item->activity_lead_in ?? '' }}"
                                                data-main="{{ $item->activity_main ?? '' }}"
                                                data-reinforce="{{ $item->activity_reinforce ?? '' }}"
                                                data-selfstudy="{{ $item->activity_self_study ?? '' }}"
                                                data-reference="{{ $item->reference_material ?? '' }}"
                                                data-experience="{{ $item->experience_note ?? '' }}"
                                                onclick="openLessonPlanModal(this)">
                                            <i class="fa-solid fa-book-open"></i>
                                        </button>
                                        <!-- Nút Xoá buổi học -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Xoá buổi học này" 
                                                onclick="deleteSessionRow({{ $subject->id }}, {{ $item->id }}, {{ $item->session_number }})">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="saving-indicator text-muted mt-1" id="saving-{{ $item->id }}" style="display: none;">
                                        <i class="fa-solid fa-spinner fa-spin text-primary"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-2 text-secondary opacity-50 mb-2 d-block"></i>
                                    <span>Môn học này chưa có buổi học nào. Thầy hãy bấm nút "Thêm Buổi Học" ở trên để bắt đầu!</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<!-- Modal 1: Thêm Buổi Học Mới -->
<div class="modal fade" id="modalAddSession" tabindex="-1" aria-labelledby="modalAddSessionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 8px;">
            <div class="modal-header bg-academic-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="modalAddSessionTitle">
                    <i class="fa-solid fa-plus-circle me-2"></i>Thêm Buổi Học Mới Vào Môn Học
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAddSession" onsubmit="submitAddSession(event)">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="new_session_content" class="form-label small fw-bold text-dark">
                            Nội dung bài học / Tên bài giảng <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="new_session_content" rows="3" placeholder="Nhập tên bài học và các nội dung trọng tâm của buổi..." required></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label for="new_session_theory" class="form-label small fw-bold text-dark">Tiết LT</label>
                            <input type="number" class="form-control form-control-sm text-center" id="new_session_theory" value="2" min="0" step="0.5">
                        </div>
                        <div class="col-4">
                            <label for="new_session_practice" class="form-label small fw-bold text-dark">Tiết TH</label>
                            <input type="number" class="form-control form-control-sm text-center" id="new_session_practice" value="2" min="0" step="0.5">
                        </div>
                        <div class="col-4">
                            <label for="new_session_test" class="form-label small fw-bold text-dark">Tiết KT</label>
                            <input type="number" class="form-control form-control-sm text-center" id="new_session_test" value="0" min="0" step="0.5">
                        </div>
                    </div>

                    <div class="p-2 bg-light rounded border small text-muted">
                        <div>&bull; Môn học: <strong>{{ $subject->name }}</strong> ({{ $subject->code }})</div>
                        <div>&bull; Buổi mới sẽ được thêm tự động vào cuối danh sách (Buổi #{{ $subject->contents->count() + 1 }}).</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm" id="btnSubmitAddSession">
                        <i class="fa-solid fa-check me-1"></i> Thêm Buổi Học
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Soạn Chi Tiết Giáo Án -->
<div class="modal fade" id="lessonPlanModal" tabindex="-1" aria-labelledby="lessonPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-academic-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="lessonPlanModalLabel">
                    <i class="fa-solid fa-book-open me-2"></i> Soạn Chi Tiết Giáo Án: Buổi #<span id="lpModalSessionNumber">1</span> - <span id="lpModalTitle">...</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUpdateLessonPlan" onsubmit="submitLessonPlanDetail(event)">
                <input type="hidden" id="lpContentId" value="">
                <div class="modal-body p-4">
                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs mb-3" id="lessonPlanTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small" id="tab-muc-tieu" data-bs-toggle="tab" data-bs-target="#tabMucTieu" type="button" role="tab">
                                <i class="fa-solid fa-bullseye me-1 text-primary"></i> 1. Mục Tiêu & Phương Tiện
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small" id="tab-hoat-dong" data-bs-toggle="tab" data-bs-target="#tabHoatDong" type="button" role="tab">
                                <i class="fa-solid fa-person-chalkboard me-1 text-success"></i> 2. Tiến Trình Lên Lớp
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small" id="tab-tong-ket" data-bs-toggle="tab" data-bs-target="#tabTongKet" type="button" role="tab">
                                <i class="fa-solid fa-clipboard-check me-1 text-warning"></i> 3. Tài Liệu & Rút Kinh Nghiệm
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="lessonPlanTabsContent">
                        <!-- Tab 1: Mục tiêu -->
                        <div class="tab-pane fade show active" id="tabMucTieu" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Mục tiêu Kiến thức</label>
                                <textarea class="form-control form-control-sm" id="lpKnowledge" rows="2" placeholder="Trình bày, giải thích được các khái niệm..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Mục tiêu Kỹ năng</label>
                                <textarea class="form-control form-control-sm" id="lpSkills" rows="2" placeholder="Cài đặt, cấu hình, xử lý và vận hành được..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Năng lực tự chủ và trách nhiệm</label>
                                <textarea class="form-control form-control-sm" id="lpAutonomy" rows="2" placeholder="Ý thức, chuyên cần, đảm bảo an toàn thiết bị..."></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Đồ dùng & Thiết bị dạy học</label>
                                    <input type="text" class="form-control form-control-sm" id="lpEquipment" placeholder="Máy tính, máy chiếu, bảng, tài liệu...">
                                </div>
                                <div class="col-md-6">
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
                                <textarea class="form-control form-control-sm" id="lpReferenceMaterial" rows="2" placeholder="Giáo trình môn học, tài liệu bài giảng nội bộ..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Rút kinh nghiệm tổ chức thực hiện</label>
                                <textarea class="form-control form-control-sm" id="lpExperienceNote" rows="3" placeholder="Ghi nhận tình hình học tập, lưu ý cần cải thiện cho các lớp sau..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm" id="btnSubmitLessonPlan">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Mục Tiêu Giáo Án
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Tải Lên Gói Giáo Án Mẫu (.zip) -->
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
                        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-check me-1"></i> Quy tắc tự động khớp nối:</div>
                        <ul class="mb-0 ps-3" style="line-height: 1.6;">
                            <li>Nén toàn bộ các file giáo án của môn học thành 1 file <strong>.zip</strong>.</li>
                            <li>Tên file Word trong zip đặt theo mẫu: <code>buoi_01.docx</code>, <code>buoi_02.docx</code>, ..., <code>buoi_22.docx</code>.</li>
                            <li>Hệ thống bảo toàn 100% hình vẽ, sơ đồ mạng, bảng biểu trong file Word của Thầy.</li>
                            <li>Khi xuất <strong>Sổ Giáo Án</strong>, hệ thống tự động điền Lớp, Ngày dạy thực tế, Ngày ký (trước ngày dạy 1 tuần), Tên giáo viên và gộp thành <strong>1 file Word duy nhất</strong>.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="zip_file" class="form-label small fw-bold text-dark">
                            Chọn file nén (.zip) chứa các file giáo án mẫu <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control form-control-sm" id="zip_file" name="zip_file" accept=".zip" required>
                        <div class="form-text small text-muted">Dung lượng tối đa 100MB. Các file bên trong có định dạng chuẩn .docx</div>
                    </div>

                    <div class="p-3 bg-light rounded-2 border small text-muted">
                        <div>&bull; Môn học áp dụng: <strong class="text-dark">{{ $subject->name }}</strong> ({{ $subject->code }})</div>
                        <div>&bull; Số file mẫu đã có trên hệ thống: <strong class="text-success">{{ $templateCount }}/{{ $subject->contents->count() }} buổi</strong></div>
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

<!-- Modal 4: Tải Lên Lẻ 1 File Giáo Án Mẫu (.docx) -->
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
    const subjectId = {{ $subject->id }};

    // Tính toán lại tổng số tiết khi người dùng đổi số LT, TH, KT
    function recalcSessionTotal(contentId) {
        const lt = parseFloat(document.getElementById('theory-' + contentId).value) || 0;
        const th = parseFloat(document.getElementById('practice-' + contentId).value) || 0;
        const kt = parseFloat(document.getElementById('test-' + contentId).value) || 0;
        const total = lt + th + kt;

        document.getElementById('total-badge-' + contentId).textContent = total;
        updateHeaderSummary();
    }

    function updateHeaderSummary() {
        let totalLT = 0, totalTH = 0, totalKT = 0;
        document.querySelectorAll('[id^="theory-"]').forEach(el => totalLT += parseFloat(el.value) || 0);
        document.querySelectorAll('[id^="practice-"]').forEach(el => totalTH += parseFloat(el.value) || 0);
        document.querySelectorAll('[id^="test-"]').forEach(el => totalKT += parseFloat(el.value) || 0);

        document.getElementById('totalLT').textContent = totalLT + ' tiết';
        document.getElementById('totalTH').textContent = totalTH + ' tiết';
        document.getElementById('totalKT').textContent = totalKT + ' tiết';
        document.getElementById('totalAllHours').textContent = (totalLT + totalTH + totalKT) + ' tiết';
    }

    // Lưu nhanh nội dung và số tiết của 1 buổi (AJAX)
    function saveSingleSession(subjId, contentId) {
        const contentVal = document.getElementById('content-' + contentId).value;
        const ltVal = document.getElementById('theory-' + contentId).value;
        const thVal = document.getElementById('practice-' + contentId).value;
        const ktVal = document.getElementById('test-' + contentId).value;

        const indicator = document.getElementById('saving-' + contentId);
        indicator.style.display = 'block';

        const updateUrl = "{{ url('subjects') }}/" + subjId + "/sessions/" + contentId;

        fetch(updateUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: contentVal,
                theory_time: ltVal,
                practice_time: thVal,
                test_time: ktVal
            })
        })
        .then(res => res.json())
        .then(data => {
            indicator.style.display = 'none';
            if (data.success) {
                // Flash success toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: data.message
                });
            } else {
                Swal.fire('Lỗi', data.message || 'Không thể lưu thay đổi.', 'error');
            }
        })
        .catch(err => {
            indicator.style.display = 'none';
            Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    // Thêm buổi học mới (AJAX)
    function submitAddSession(event) {
        event.preventDefault();
        const contentVal = document.getElementById('new_session_content').value;
        const ltVal = document.getElementById('new_session_theory').value;
        const thVal = document.getElementById('new_session_practice').value;
        const ktVal = document.getElementById('new_session_test').value;

        const btn = document.getElementById('btnSubmitAddSession');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang thêm...';

        const storeUrl = "{{ url('subjects') }}/" + subjectId + "/sessions";

        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: contentVal,
                theory_time: ltVal,
                practice_time: thVal,
                test_time: ktVal
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Thêm Buổi Học';
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAddSession'));
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
                Swal.fire('Lỗi', data.message || 'Không thể thêm buổi học.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Thêm Buổi Học';
            Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    // Xoá một buổi học và tự động đánh số lại
    function deleteSessionRow(subjId, contentId, sessionNum) {
        Swal.fire({
            title: 'Xoá Buổi #' + sessionNum + '?',
            text: 'Hệ thống sẽ xoá buổi này và tự động sắp xếp lại số thứ tự các buổi tiếp theo. Dữ liệu lịch học liên quan của các lớp cũng sẽ được đồng bộ.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý xoá buổi này',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteUrl = "{{ url('subjects') }}/" + subjId + "/sessions/" + contentId;

                fetch(deleteUrl, {
                    method: 'DELETE',
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
                        Swal.fire('Lỗi', data.message || 'Không thể xoá buổi học.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
                });
            }
        });
    }

    // Mở modal soạn chi tiết giáo án
    function openLessonPlanModal(btn) {
        const sessionNum = btn.dataset.session;
        const contentId = btn.dataset.contentId;
        const title = btn.dataset.title;

        document.getElementById('lpModalSessionNumber').textContent = sessionNum;
        document.getElementById('lpModalTitle').textContent = title;
        document.getElementById('lpContentId').value = contentId;

        document.getElementById('lpKnowledge').value = btn.dataset.knowledge || '';
        document.getElementById('lpSkills').value = btn.dataset.skills || '';
        document.getElementById('lpAutonomy').value = btn.dataset.autonomy || '';
        document.getElementById('lpEquipment').value = btn.dataset.equipment || '';
        document.getElementById('lpTeachingForm').value = btn.dataset.form || '';
        document.getElementById('lpActivityLeadIn').value = btn.dataset.leadin || '';
        document.getElementById('lpActivityMain').value = btn.dataset.main || '';
        document.getElementById('lpActivityReinforce').value = btn.dataset.reinforce || '';
        document.getElementById('lpActivitySelfStudy').value = btn.dataset.selfstudy || '';
        document.getElementById('lpReferenceMaterial').value = btn.dataset.reference || '';
        document.getElementById('lpExperienceNote').value = btn.dataset.experience || '';

        const modal = new bootstrap.Modal(document.getElementById('lessonPlanModal'));
        modal.show();
    }

    // Lưu chi tiết giáo án từ modal
    function submitLessonPlanDetail(event) {
        event.preventDefault();
        const contentId = document.getElementById('lpContentId').value;
        const updateUrl = "{{ url('schedules/subject-content') }}/" + contentId + "/update-lesson-plan";

        const btn = document.getElementById('btnSubmitLessonPlan');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang lưu...';

        const payload = {
            objective_knowledge: document.getElementById('lpKnowledge').value,
            objective_skills: document.getElementById('lpSkills').value,
            objective_autonomy: document.getElementById('lpAutonomy').value,
            teaching_equipment: document.getElementById('lpEquipment').value,
            teaching_form: document.getElementById('lpTeachingForm').value,
            activity_lead_in: document.getElementById('lpActivityLeadIn').value,
            activity_main: document.getElementById('lpActivityMain').value,
            activity_reinforce: document.getElementById('lpActivityReinforce').value,
            activity_self_study: document.getElementById('lpActivitySelfStudy').value,
            reference_material: document.getElementById('lpReferenceMaterial').value,
            experience_note: document.getElementById('lpExperienceNote').value,
        };

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
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Mục Tiêu Giáo Án';
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('lessonPlanModal'));
                if (modal) modal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Lỗi', data.message || 'Không thể lưu giáo án.', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Lưu Mục Tiêu Giáo Án';
            Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }

    // Đổi loại môn học nhanh
    function changeSubjectType(newType) {
        const updateTypeUrl = "{{ url('schedules/subject') }}/" + subjectId + "/update-type";

        Swal.fire({
            title: 'Đổi loại môn học?',
            text: 'Bạn có chắc muốn chuyển đổi loại môn học này? Mẫu giáo án tương ứng sẽ được áp dụng (9a, 9b, 9c).',
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

    // Mở modal upload lẻ file Word
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

    function deleteSingleTemplate(subjId, sessionNumber) {
        const deleteUrl = "{{ url('schedules/subject') }}/" + subjId + "/session/" + sessionNumber + "/delete-template";

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
