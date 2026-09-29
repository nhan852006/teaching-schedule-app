@extends('layouts.app')

@section('title', 'Hồ sơ Kế hoạch & Lịch Giảng dạy - ' . $currentTeacher->name)
@section('meta_description', 'Bảng điều khiển quản lý kế hoạch giảng dạy, tra cứu lịch học theo tháng và xuất sổ tay giáo án cho giảng viên ' . $currentTeacher->name)

@push('styles')
<style>
    /* Academic KPI Stat Cards */
    .stat-academic-card {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid var(--edu-border);
        border-left: 4px solid var(--edu-primary);
        padding: 1.15rem 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 39, 74, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-academic-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 39, 74, 0.08);
    }
    .stat-academic-card.card-accent-gold {
        border-left-color: var(--edu-gold);
    }
    .stat-academic-card.card-accent-teal {
        border-left-color: var(--edu-teal);
    }
    .stat-academic-card.card-accent-burgundy {
        border-left-color: var(--edu-burgundy);
    }

    .stat-number {
        font-family: var(--font-heading);
        font-size: 1.85rem;
        font-weight: 700;
        color: var(--edu-navy-900);
        line-height: 1.1;
    }

    /* Academic Calendar Styles */
    .calendar-table {
        table-layout: fixed;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 4px;
        width: 100%;
    }
    .calendar-table th {
        text-align: center;
        font-family: var(--font-heading);
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.65rem 0.25rem;
        color: var(--edu-navy-900);
        background: #f1f5f9;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }
    .calendar-table th.sunday-col {
        color: var(--edu-burgundy);
        background: #ffe4e6;
        border-color: #fecdd3;
    }
    .cal-day-cell {
        height: 90px;
        vertical-align: top;
        padding: 6px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        position: relative;
    }
    .cal-day-cell:hover {
        background: #f8fafc;
        border-color: var(--edu-primary);
        box-shadow: 0 2px 8px rgba(27, 77, 137, 0.15);
        z-index: 2;
    }
    .cal-day-cell.dimmed {
        background: #f8fafc;
        opacity: 0.4;
        cursor: default;
        border-color: #e2e8f0;
    }
    .cal-day-cell.dimmed:hover {
        box-shadow: none;
        border-color: #e2e8f0;
    }
    .cal-day-cell.sunday {
        background: #fff5f5;
        border-color: #fed7d7;
    }
    .cal-day-cell.has-events {
        background: #f0f7ff;
        border-color: #93c5fd;
    }
    .cal-day-cell.active-selected {
        border: 2px solid var(--edu-primary) !important;
        box-shadow: 0 0 0 3px rgba(27, 77, 137, 0.25);
        background: #e0f2fe !important;
    }
    .cal-day-number {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--edu-navy-900);
        display: inline-block;
        width: 24px;
        height: 24px;
        line-height: 24px;
        text-align: center;
        border-radius: 4px;
    }
    .cal-day-cell.today .cal-day-number {
        background: var(--edu-primary);
        color: #ffffff;
    }

    /* Shift Badges */
    .cal-badge {
        font-size: 0.7rem;
        padding: 2px 5px;
        border-radius: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        margin-top: 3px;
        font-weight: 600;
    }
    .cal-badge-sang {
        background: #fef3c7;
        color: #78350f;
        border-left: 3px solid #d97706;
    }
    .cal-badge-chieu {
        background: #e0e7ff;
        color: #1e3a8a;
        border-left: 3px solid #3b82f6;
    }

    /* Side Panel */
    .side-panel {
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid var(--edu-border);
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .side-panel-header {
        padding: 1rem 1.25rem;
        border-bottom: 2px solid var(--edu-border-light);
        background: #f8fafc;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .side-panel-body {
        padding: 1.25rem;
        flex: 1;
        overflow-y: auto;
        max-height: 560px;
    }
    .session-card {
        border: 1px solid var(--edu-border);
        border-left: 3px solid var(--edu-primary);
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.9rem;
        background: #ffffff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .session-card:hover {
        border-color: #94a3b8;
        box-shadow: 0 4px 10px rgba(15, 39, 74, 0.05);
    }
</style>
@endpush

@section('header_actions')
    <!-- Giảng viên Switcher -->
    <form action="{{ route('schedules.teacher.switch') }}" method="POST" class="d-flex align-items-center gap-2 m-0 me-2" aria-label="Chọn giảng viên làm việc">
        @csrf
        <label for="teacherSelect" class="text-muted small d-none d-md-inline fw-semibold text-nowrap">
            <i class="fa-solid fa-chalkboard-user text-secondary me-1" aria-hidden="true"></i>Giảng viên:
        </label>
        <select id="teacherSelect" name="teacher_id" class="form-select form-select-sm fw-bold border-secondary-subtle" style="min-width: 210px;" onchange="this.form.submit()">
            @foreach($allTeachers as $t)
                <option value="{{ $t->id }}" {{ $t->id === $currentTeacher->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
    </form>

    <div class="d-none d-lg-flex gap-2">
        <button type="button" class="btn btn-sm btn-academic-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSubject" aria-label="Thêm môn học mới">
            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i> Thêm Môn
        </button>
        <button type="button" class="btn btn-sm btn-success fw-semibold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGeneratePlan" aria-label="Lập kế hoạch giảng dạy">
            <i class="fa-solid fa-calendar-check me-1" aria-hidden="true"></i> Lập Lịch
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary px-2" data-bs-toggle="modal" data-bs-target="#modalImportCSV" title="Import dữ liệu CSV" aria-label="Import dữ liệu CSV">
            <i class="fa-solid fa-file-import" aria-hidden="true"></i>
        </button>
    </div>
@endsection

@section('content')
<div class="container-fluid px-lg-5 py-4">

    <!-- Semantic Page Heading (H1 for SEO & A11y) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">
                Kế hoạch Giảng dạy Học kỳ I (2026 - 2027)
            </h1>
            <p class="text-muted small mb-0">
                Hồ sơ học vụ của Giảng viên: <strong class="text-primary">{{ $currentTeacher->name }}</strong>
                &middot; Đơn vị: <strong>Bộ môn Công nghệ Thông tin</strong>
            </p>
        </div>
        <div class="text-muted small mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-2 py-1"><i class="fa-regular fa-clock me-1 text-secondary"></i>Cập nhật: {{ date('d/m/Y') }}</span>
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

    <!-- Macro KPI Cards -->
    <section aria-labelledby="kpi-heading" class="mb-4">
        <h2 id="kpi-heading" class="visually-hidden">Chỉ số khối lượng giảng dạy</h2>
        <div class="row g-3">
            <!-- Card 1 -->
            <div class="col-6 col-xl-3">
                <div class="stat-academic-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Lớp giảng dạy</div>
                        <div class="stat-number mt-1">{{ $totalClassesCount }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
                        <div class="text-success small fw-medium mt-1" style="font-size: 0.75rem;">Phân công hiện hành</div>
                    </div>
                    <div class="fs-2 text-primary opacity-50" aria-hidden="true">
                        <i class="fa-solid fa-users-rectangle"></i>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-6 col-xl-3">
                <div class="stat-academic-card card-accent-teal d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Môn phụ trách</div>
                        <div class="stat-number mt-1">{{ $totalSubjectsCount }} <span class="fs-6 fw-normal text-muted">học phần</span></div>
                        <div class="text-primary small fw-medium mt-1" style="font-size: 0.75rem;">{{ $mySubjects->count() }} môn cá nhân</div>
                    </div>
                    <div class="fs-2 text-teal opacity-50" style="color: var(--edu-teal);" aria-hidden="true">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-6 col-xl-3">
                <div class="stat-academic-card card-accent-gold d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Tổng số buổi học</div>
                        <div class="stat-number mt-1">{{ $totalSessionsCount }} <span class="fs-6 fw-normal text-muted">buổi</span></div>
                        <div class="text-muted small fw-medium mt-1" style="font-size: 0.75rem;">Thứ 2 - Thứ 7</div>
                    </div>
                    <div class="fs-2 text-warning opacity-50" aria-hidden="true">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-6 col-xl-3">
                <div class="stat-academic-card card-accent-burgundy d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Google Calendar</div>
                        <div class="stat-number mt-1">{{ $syncPercentage }}%</div>
                        <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $syncSynced }} synced</span>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $syncPending }} pending</span>
                        </div>
                    </div>
                    <div class="fs-2 text-danger opacity-50" aria-hidden="true">
                        <i class="fa-brands fa-google"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Monthly Calendar & Side Detail Panel -->
    <div class="row g-4 mb-4">
        <!-- Cột Trái (8 phần): Lịch Tháng Trực quan -->
        <div class="col-lg-8">
            <section class="card-academic p-3 p-md-4 h-100" aria-labelledby="calendar-heading">
                <!-- Calendar Toolbar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <!-- Month Navigation -->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-2" onclick="changeMonth(-1)" title="Tháng trước" aria-label="Chuyển đến tháng trước">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <h2 class="h5 fw-bold text-dark mb-0 mx-2" id="currentMonthYearLabel">
                            Tháng 10 / 2026
                        </h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-2 px-2" onclick="changeMonth(1)" title="Tháng sau" aria-label="Chuyển đến tháng sau">
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                        <!-- Month Picker -->
                        <input type="month" id="monthPicker" class="form-control form-control-sm ms-2" style="width: 145px;" onchange="onMonthPickerChange(this.value)" aria-label="Chọn tháng và năm">
                    </div>

                    <!-- Scope Switcher: Lịch của tôi vs Toàn trường -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-semibold d-none d-sm-inline">Phạm vi:</span>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Phạm vi hiển thị lịch">
                            <button type="button" class="btn btn-academic-primary" id="btnScopeMy" onclick="setScope('my')">
                                <i class="fa-solid fa-user me-1" aria-hidden="true"></i> Của tôi
                            </button>
                            <button type="button" class="btn btn-academic-outline" id="btnScopeAll" onclick="setScope('all')">
                                <i class="fa-solid fa-school me-1" aria-hidden="true"></i> Toàn trường
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Calendar Legend / Ghi chú màu -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 text-muted small px-1">
                    <div class="d-flex align-items-center gap-3">
                        <span><span class="d-inline-block rounded-1 me-1" style="width: 12px; height: 12px; background: #d97706;" aria-hidden="true"></span> Ca Sáng (07:30 - 11:30)</span>
                        <span><span class="d-inline-block rounded-1 me-1" style="width: 12px; height: 12px; background: #3b82f6;" aria-hidden="true"></span> Ca Chiều (13:00 - 17:00)</span>
                        <span class="text-danger"><i class="fa-regular fa-calendar-xmark me-1" aria-hidden="true"></i> Chủ Nhật (Nghỉ)</span>
                    </div>
                    <div class="text-secondary fst-italic" style="font-size: 0.78rem;">
                        <i class="fa-regular fa-hand-pointer me-1" aria-hidden="true"></i> Nhấp chuột vào ngày để xem chi tiết
                    </div>
                </div>

                <!-- Calendar Matrix Table -->
                <div class="table-responsive">
                    <table class="calendar-table" aria-label="Ma trận lịch giảng dạy tháng">
                        <caption class="visually-hidden">Lịch giảng dạy trong tháng theo tuần và ngày</caption>
                        <thead>
                            <tr>
                                <th scope="col">Thứ 2</th>
                                <th scope="col">Thứ 3</th>
                                <th scope="col">Thứ 4</th>
                                <th scope="col">Thứ 5</th>
                                <th scope="col">Thứ 6</th>
                                <th scope="col">Thứ 7</th>
                                <th scope="col" class="sunday-col">Chủ Nhật</th>
                            </tr>
                        </thead>
                        <tbody id="calendarGridBody">
                            <!-- Dynamic Day Cells Rendered via JS -->
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Cột Phải (4 phần): Side Panel Chi tiết Ngày được chọn -->
        <div class="col-lg-4">
            <aside class="side-panel" aria-labelledby="selectedDateTitle">
                <div class="side-panel-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">HỒ SƠ BUỔI DẠY</div>
                        <h2 class="h6 fw-bold text-primary mb-0" id="selectedDateTitle">
                            Thứ 2, ngày 05/10/2026
                        </h2>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold" id="selectedDateBadge">
                        0 ca dạy
                    </span>
                </div>

                <div class="side-panel-body" id="sidePanelContent">
                    <!-- Session Cards rendered dynamically -->
                </div>
            </aside>
        </div>
    </div>

    <!-- Bảng Quản lý Tiến độ Lớp - Môn (Xuất Sổ tay Word) -->
    <section class="card-academic p-4 mb-4" aria-labelledby="subject-list-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 id="subject-list-heading" class="h5 fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-ol text-primary me-2" aria-hidden="true"></i>Danh mục Học phần & Sổ tay Giáo án
                </h2>
                <p class="text-muted small mb-0">Quản lý nội dung từng buổi học, đồng bộ lịch và tải về Sổ tay giảng dạy định dạng Word (.docx)</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="position-relative" style="min-width: 240px;">
                    <input type="text" id="tableSearch" class="form-control form-control-sm ps-4" placeholder="Lọc theo lớp hoặc môn học..." aria-label="Tìm kiếm theo tên lớp hoặc tên môn học">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-2 text-muted small" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-academic border mb-0" id="scheduleTable">
                <thead>
                    <tr>
                        <th scope="col" style="width: 200px;">Lớp sinh hoạt</th>
                        <th scope="col">Tên Học phần</th>
                        <th scope="col" style="width: 140px;">Mã môn</th>
                        <th scope="col" class="text-center" style="width: 110px;">Quy mô</th>
                        <th scope="col" class="text-center" style="width: 230px;">Thao tác chuyên vụ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pairs as $p)
                        <tr>
                            <td>
                                <span class="fw-bold text-dark"><i class="fa-solid fa-users me-1 text-secondary" aria-hidden="true"></i>{{ $p->class?->name }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $p->subject?->name }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-1 font-monospace">
                                    {{ $p->subject?->code }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                    {{ $p->total_schedules }} buổi
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('schedules.preview', ['class_id' => $p->class_id, 'subject_id' => $p->subject_id]) }}" 
                                       class="btn btn-outline-primary" title="Xem danh sách chi tiết các buổi và sửa inline" aria-label="Xem chi tiết lớp {{ $p->class?->name }}">
                                        <i class="fa-solid fa-pen-to-square me-1" aria-hidden="true"></i> Chi tiết
                                    </a>
                                    <a href="{{ route('schedules.export_word', ['class_id' => $p->class_id, 'subject_id' => $p->subject_id]) }}" 
                                       class="btn btn-outline-success" title="Xuất Kế hoạch giảng dạy Mẫu 08 (.docx)" aria-label="Xuất Mẫu 08 lớp {{ $p->class?->name }}">
                                        <i class="fa-solid fa-file-word me-1" aria-hidden="true"></i> Xuất Mẫu 08
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-circle-info me-1" aria-hidden="true"></i> Chưa có lớp nào được phân công cho Giảng viên này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- ======================= MODALS ======================= -->

<!-- Modal 1: Thêm Môn học & Mô-đun Buổi học -->
<div class="modal fade" id="modalAddSubject" tabindex="-1" aria-labelledby="modalAddSubjectTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 8px;">
            <div class="modal-header bg-light border-bottom">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalAddSubjectTitle">
                    <i class="fa-solid fa-folder-plus text-primary me-2" aria-hidden="true"></i>Thêm Môn học & Mô-đun Bài giảng
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('schedules.subjects.store_custom') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mã Môn <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="VD: IT4505" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Tên Môn học <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="VD: Chuyên đề Phát triển Ứng dụng Doanh nghiệp" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                        <h3 class="h6 fw-bold mb-0 text-secondary">
                            <i class="fa-solid fa-list-ol me-1" aria-hidden="true"></i>Danh sách Mô-đun từng buổi:
                        </h3>
                        <button type="button" class="btn btn-sm btn-academic-outline" onclick="addModuleRow()">
                            <i class="fa-solid fa-plus me-1" aria-hidden="true"></i> Thêm buổi
                        </button>
                    </div>

                    <div class="table-responsive border rounded-2 p-2 bg-light">
                        <table class="table table-sm table-borderless align-middle mb-0" id="moduleTable">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width: 50px;">Buổi</th>
                                    <th>Nội dung bài học</th>
                                    <th style="width: 75px;">Tiết LT</th>
                                    <th style="width: 75px;">Tiết TH</th>
                                    <th style="width: 75px;">Tiết KT</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="moduleTableBody">
                                <tr>
                                    <td class="text-center fw-bold text-muted">1</td>
                                    <td><input type="text" name="modules[0][content]" class="form-control form-control-sm" placeholder="Nội dung bài giảng..." required></td>
                                    <td><input type="number" name="modules[0][theory_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
                                    <td><input type="number" name="modules[0][practice_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
                                    <td><input type="number" name="modules[0][test_time]" class="form-control form-control-sm text-center" value="0" min="0"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-academic-primary btn-sm px-4 fw-bold">Lưu Môn học</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Lập Kế hoạch Giảng dạy Cá nhân -->
<div class="modal fade" id="modalGeneratePlan" tabindex="-1" aria-labelledby="modalGeneratePlanTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 8px;">
            <div class="modal-header bg-light border-bottom">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalGeneratePlanTitle">
                    <i class="fa-solid fa-calendar-check text-success me-2" aria-hidden="true"></i>Lập Kế hoạch Giảng dạy Cá nhân
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('schedules.generate_plan') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chọn Lớp học <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Chọn lớp học --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Chọn Môn học <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Chọn môn học --</option>
                            @foreach($allSubjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }} - {{ $s->total_sessions }} buổi)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ngày bắt đầu học <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Các ngày học trong tuần (Thứ 2 - Thứ 7):</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="1" id="d1" checked>
                                <label class="form-check-label small" for="d1">Thứ 2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="2" id="d2">
                                <label class="form-check-label small" for="d2">Thứ 3</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="3" id="d3" checked>
                                <label class="form-check-label small" for="d3">Thứ 4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="4" id="d4">
                                <label class="form-check-label small" for="d4">Thứ 5</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="5" id="d5" checked>
                                <label class="form-check-label small" for="d5">Thứ 6</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="6" id="d6">
                                <label class="form-check-label small" for="d6">Thứ 7</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Ca dạy</label>
                        <select name="shift_mode" class="form-select">
                            <option value="Sáng">Sáng (07:30 - 11:30)</option>
                            <option value="Chiều">Chiều (13:00 - 17:00)</option>
                            <option value="Xen kẽ">Xen kẽ Sáng / Chiều</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">Tạo Kế hoạch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Import CSV -->
<div class="modal fade" id="modalImportCSV" tabindex="-1" aria-labelledby="modalImportCSVTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 8px;">
            <div class="modal-header bg-light border-bottom">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalImportCSVTitle">
                    <i class="fa-solid fa-file-import text-primary me-2" aria-hidden="true"></i>Import Tệp CSV Học vụ
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills mb-3 nav-justified" id="csvTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active small fw-bold" id="subject-tab" data-bs-toggle="pill" data-bs-target="#tab-subject" type="button">
                            1. Môn & Buổi học
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link small fw-bold" id="schedule-tab" data-bs-toggle="pill" data-bs-target="#tab-schedule" type="button">
                            2. Lịch Giảng dạy
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="csvTabContent">
                    <div class="tab-pane fade show active" id="tab-subject">
                        <p class="text-muted small">Cấu trúc cột: <code>Mã Môn, Tên Môn, Buổi số, Nội dung giảng dạy, Số tiết LT, Số tiết TH, Số tiết KT</code> <em>(Số tiết KT có thể để trống hoặc 0)</em></p>
                        <form action="{{ route('schedules.import.subject_contents') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control form-control-sm mb-3" type="file" name="csv_file" accept=".csv,.txt" required>
                            <button type="submit" class="btn btn-academic-primary btn-sm w-100 fw-bold">Tải lên Môn học</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab-schedule">
                        <p class="text-muted small">Cấu trúc cột: <code>Ngày, Buổi, Tên Lớp, Mã Môn</code> <em>(Buổi: <b>0</b> = Sáng, <b>1</b> = Chiều hoặc chữ Sáng/Chiều; Môn: Mã Môn hoặc Tên Môn)</em></p>
                        <form action="{{ route('schedules.import.schedules') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control form-control-sm mb-3" type="file" name="csv_file" accept=".csv,.txt" required>
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Tải lên Lịch dạy</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Báo Nghỉ & Đôn Lịch Từ Dashboard -->
<div class="modal fade" id="dashboardPostponeModal" tabindex="-1" aria-labelledby="dashboardPostponeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-warning text-dark border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="dashboardPostponeModalLabel">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i> Báo Nghỉ & Tự Động Đôn Lịch Giảng Dạy
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDashboardPostpone" onsubmit="submitDashboardPostpone(event)">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-info-subtle small py-2 px-3 mb-3">
                        <i class="fa-solid fa-circle-info me-1 text-info-emphasis"></i>
                        Hệ thống sẽ dời buổi học này sang ngày thay thế và <strong>tự động đôn lại các buổi học tiếp theo</strong> theo đúng tiến trình bài giảng tăng dần theo dòng thời gian.
                    </div>

                    <input type="hidden" id="dashScheduleId" required>

                    <!-- Thông tin buổi học bị hoãn -->
                    <div class="p-3 mb-3 bg-light rounded border text-muted small">
                        <div>&bull; Lớp học: <strong class="text-dark" id="dashClassName">...</strong></div>
                        <div>&bull; Môn học: <strong class="text-primary" id="dashSubjectName">...</strong></div>
                        <div>&bull; Buổi học: <strong class="text-dark" id="dashSessionNum">...</strong></div>
                        <div>&bull; Ngày báo nghỉ: <strong class="text-danger" id="dashOffDate">...</strong></div>
                        <div>&bull; Ca học: <strong class="text-dark" id="dashOffShift">...</strong></div>
                    </div>

                    <!-- Ngày thay thế & Ca thay thế -->
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="dashReplacementDate" class="form-label small fw-bold text-dark">
                                Ngày học thay thế (Dạy bù) <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm" id="dashReplacementDate" required>
                        </div>
                        <div class="col-5">
                            <label for="dashReplacementShift" class="form-label small fw-bold text-dark">
                                Ca dạy bù <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="dashReplacementShift" required>
                                <option value="Sáng">Ca Sáng</option>
                                <option value="Chiều">Ca Chiều</option>
                            </select>
                        </div>
                    </div>

                    <!-- Gợi ý chọn nhanh ngày bù -->
                    <div class="mb-3">
                        <div class="text-muted small mb-1" style="font-size: 0.78rem;">Gợi ý chọn nhanh ngày bù:</div>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickPickDashDate(3)">+3 ngày</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickPickDashDate(7)">+7 ngày</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="quickPickDashDate(14)">+14 ngày</button>
                        </div>
                    </div>

                    <!-- Lý do nghỉ (Tùy chọn) -->
                    <div class="mb-2">
                        <label for="dashReason" class="form-label small fw-bold text-dark">Lý do nghỉ (Tùy chọn)</label>
                        <input type="text" class="form-control form-control-sm" id="dashReason" placeholder="Ví dụ: Bận việc đột xuất, Nghỉ lễ...">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold px-3 shadow-sm" id="btnSubmitDashPostpone">
                        <i class="fa-solid fa-check me-1"></i> Xác Nhận Đôn Lịch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dữ liệu sự kiện được truyền từ Laravel Controller
    const myEvents = @json($calendarEvents);
    const allEvents = @json($allCalendarEvents);

    let currentScope = 'my'; // 'my' hoặc 'all'
    let activeEvents = myEvents;

    // Tháng và Năm đang xem
    const defaultMonthStr = "{{ $defaultMonth }}"; // 'YYYY-MM'
    let [currYear, currMonth] = defaultMonthStr.split('-').map(Number); // currMonth: 1-12

    let selectedDateStr = null;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('monthPicker').value = `${currYear}-${String(currMonth).padStart(2, '0')}`;
        renderCalendar();
        
        // Tự động chọn ngày đầu tiên có lịch trong tháng
        selectFirstAvailableDateInMonth();
    });

    function setScope(scope) {
        currentScope = scope;
        if (scope === 'my') {
            activeEvents = myEvents;
            document.getElementById('btnScopeMy').className = 'btn btn-academic-primary';
            document.getElementById('btnScopeAll').className = 'btn btn-academic-outline';
        } else {
            activeEvents = allEvents;
            document.getElementById('btnScopeMy').className = 'btn btn-academic-outline';
            document.getElementById('btnScopeAll').className = 'btn btn-academic-primary';
        }
        renderCalendar();
        if (selectedDateStr) {
            renderSidePanel(selectedDateStr);
        } else {
            selectFirstAvailableDateInMonth();
        }
    }

    function changeMonth(delta) {
        currMonth += delta;
        if (currMonth > 12) {
            currMonth = 1;
            currYear++;
        } else if (currMonth < 1) {
            currMonth = 12;
            currYear--;
        }
        document.getElementById('monthPicker').value = `${currYear}-${String(currMonth).padStart(2, '0')}`;
        renderCalendar();
        selectFirstAvailableDateInMonth();
    }

    function onMonthPickerChange(val) {
        if (!val) return;
        const [y, m] = val.split('-').map(Number);
        currYear = y;
        currMonth = m;
        renderCalendar();
        selectFirstAvailableDateInMonth();
    }

    function renderCalendar() {
        const gridBody = document.getElementById('calendarGridBody');
        gridBody.innerHTML = '';

        // Cập nhật nhãn tháng năm
        document.getElementById('currentMonthYearLabel').innerText = `Tháng ${String(currMonth).padStart(2, '0')} / ${currYear}`;

        // Gom nhóm sự kiện theo ngày 'YYYY-MM-DD'
        const eventsByDate = {};
        activeEvents.forEach(ev => {
            if (!eventsByDate[ev.date]) {
                eventsByDate[ev.date] = [];
            }
            eventsByDate[ev.date].push(ev);
        });

        // Tính ngày đầu tiên và số ngày của tháng
        const firstDayObj = new Date(currYear, currMonth - 1, 1);
        const lastDayObj = new Date(currYear, currMonth, 0);
        const totalDays = lastDayObj.getDate();

        // Thứ của ngày 1 (JavaScript: 0=Sun, 1=Mon, ..., 6=Sat)
        // Chúng ta xếp: Thứ 2 = 0, Thứ 3 = 1, ..., CN = 6
        let firstDayIndex = firstDayObj.getDay() - 1;
        if (firstDayIndex === -1) firstDayIndex = 6; // Chủ nhật là cột cuối

        // Số ngày của tháng trước
        const prevMonthLastDay = new Date(currYear, currMonth - 1, 0).getDate();

        const todayStr = new Date().toISOString().split('T')[0];

        let tr = document.createElement('tr');
        let dayCounter = 1;
        let nextMonthDayCounter = 1;

        // Ô đệm từ tháng trước
        for (let i = 0; i < firstDayIndex; i++) {
            const td = document.createElement('td');
            td.className = 'cal-day-cell dimmed';
            const prevDayNum = prevMonthLastDay - firstDayIndex + i + 1;
            td.innerHTML = `<span class="cal-day-number text-muted">${prevDayNum}</span>`;
            tr.appendChild(td);
        }

        // Các ngày trong tháng
        for (let d = 1; d <= totalDays; d++) {
            const dayOfWeek = (firstDayIndex + d - 1) % 7; // 0..6 (6 is Sunday)
            const dateStr = `${currYear}-${String(currMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const dayEvents = eventsByDate[dateStr] || [];

            const td = document.createElement('td');
            td.className = 'cal-day-cell';
            if (dayOfWeek === 6) td.classList.add('sunday');
            if (dateStr === todayStr) td.classList.add('today');
            if (dayEvents.length > 0) td.classList.add('has-events');
            if (selectedDateStr === dateStr) td.classList.add('active-selected');

            td.onclick = () => onSelectDate(dateStr, td);

            let badgesHtml = '';
            // Nhóm theo ca (Sáng / Chiều)
            const sangSessions = dayEvents.filter(e => e.shift === 'Sáng');
            const chieuSessions = dayEvents.filter(e => e.shift === 'Chiều');

            if (sangSessions.length > 0) {
                const label = sangSessions.map(s => s.class_name).join(', ');
                badgesHtml += `<span class="cal-badge cal-badge-sang" title="Ca Sáng: ${label}">🌅 ${label}</span>`;
            }
            if (chieuSessions.length > 0) {
                const label = chieuSessions.map(s => s.class_name).join(', ');
                badgesHtml += `<span class="cal-badge cal-badge-chieu" title="Ca Chiều: ${label}">🌇 ${label}</span>`;
            }

            td.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="cal-day-number">${d}</span>
                    ${dayEvents.length > 0 ? `<span class="badge rounded-1 bg-primary text-white" style="font-size: 0.65rem; padding: 2px 6px;">${dayEvents.length}</span>` : ''}
                </div>
                ${badgesHtml}
            `;

            tr.appendChild(td);

            // Xuống hàng khi hết Chủ Nhật
            if ((firstDayIndex + d) % 7 === 0) {
                gridBody.appendChild(tr);
                tr = document.createElement('tr');
            }
        }

        // Ô đệm cho tháng sau
        const remainingCols = (7 - ((firstDayIndex + totalDays) % 7)) % 7;
        for (let i = 0; i < remainingCols; i++) {
            const td = document.createElement('td');
            td.className = 'cal-day-cell dimmed';
            td.innerHTML = `<span class="cal-day-number text-muted">${nextMonthDayCounter++}</span>`;
            tr.appendChild(td);
        }

        if (tr.children.length > 0) {
            gridBody.appendChild(tr);
        }
    }

    function selectFirstAvailableDateInMonth() {
        const monthPrefix = `${currYear}-${String(currMonth).padStart(2, '0')}`;
        // Tìm ngày đầu tiên có lịch
        const match = activeEvents.find(e => e.date.startsWith(monthPrefix));
        if (match) {
            onSelectDate(match.date);
        } else {
            // Mặc định chọn ngày 1 của tháng
            const defaultDate = `${monthPrefix}-01`;
            onSelectDate(defaultDate);
        }
    }

    function onSelectDate(dateStr) {
        selectedDateStr = dateStr;

        // Cập nhật class active trên các ô
        document.querySelectorAll('.cal-day-cell').forEach(cell => {
            cell.classList.remove('active-selected');
        });

        // Render chi tiết sang cột bên cạnh
        renderSidePanel(dateStr);
    }

    function renderSidePanel(dateStr) {
        const sideContent = document.getElementById('sidePanelContent');
        const titleEl = document.getElementById('selectedDateTitle');
        const badgeEl = document.getElementById('selectedDateBadge');

        // Định dạng Thứ, ngày DD/MM/YYYY
        const dObj = new Date(dateStr + 'T00:00:00');
        const dayNames = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        const dayOfWeekStr = dayNames[dObj.getDay()];
        const formattedDate = `${String(dObj.getDate()).padStart(2, '0')}/${String(dObj.getMonth() + 1).padStart(2, '0')}/${dObj.getFullYear()}`;

        titleEl.innerText = `${dayOfWeekStr}, ngày ${formattedDate}`;

        const eventsOnDay = activeEvents.filter(e => e.date === dateStr);
        badgeEl.innerText = `${eventsOnDay.length} ca dạy`;

        if (eventsOnDay.length === 0) {
            sideContent.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-xmark fa-3x mb-3 text-secondary d-block" aria-hidden="true"></i>
                    <h6 class="fw-bold text-dark">Không có ca giảng dạy</h6>
                    <p class="small text-muted mb-0">Không có lịch học hoặc hoạt động giảng dạy trong ngày này.</p>
                </div>
            `;
            return;
        }

        // Sắp xếp ca Sáng trước, Chiều sau
        eventsOnDay.sort((a, b) => a.shift === 'Sáng' ? -1 : 1);

        let html = '';
        eventsOnDay.forEach(ev => {
            const isMorning = ev.shift === 'Sáng';
            const shiftBadgeClass = isMorning ? 'cal-badge-sang' : 'cal-badge-chieu';
            const shiftTime = isMorning ? '07:30 - 11:30' : '13:00 - 17:00';
            const icon = isMorning ? 'fa-sun' : 'fa-moon';

            let syncBadge = '';
            if (ev.sync_status === 'synced') {
                syncBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1" aria-hidden="true"></i>synced</span>';
            } else if (ev.sync_status === 'modified') {
                syncBadge = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="fa-solid fa-pen me-1" aria-hidden="true"></i>modified</span>';
            } else {
                syncBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fa-regular fa-clock me-1" aria-hidden="true"></i>pending</span>';
            }

            html += `
                <div class="session-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge ${shiftBadgeClass} px-2 py-1 fw-bold">
                            <i class="fa-solid ${icon} me-1" aria-hidden="true"></i>Ca ${ev.shift} (${shiftTime})
                        </span>
                        ${syncBadge}
                    </div>

                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <span class="badge bg-light text-dark border">Lớp</span>
                        <h6 class="fw-bold text-dark mb-0">${ev.class_name}</h6>
                    </div>

                    <div class="fw-semibold text-primary small mb-1">
                        <i class="fa-solid fa-book-open me-1" aria-hidden="true"></i>${ev.subject_name}
                        <span class="text-secondary fw-normal">&middot; Buổi #${ev.session}</span>
                    </div>

                    ${ev.teacher_name ? `<div class="text-muted small mb-2"><i class="fa-solid fa-chalkboard-user me-1" aria-hidden="true"></i>Giảng viên: <strong>${ev.teacher_name}</strong></div>` : ''}

                    <div class="bg-light rounded p-2 small text-secondary mb-2 border border-light-subtle" style="font-size: 0.8rem; line-height: 1.45;">
                        <strong class="text-dark">Nội dung:</strong> ${ev.content}
                    </div>

                    <div class="d-flex align-items-center gap-1 mb-3 flex-wrap">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">LT: ${ev.theory_time}t</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">TH: ${ev.practice_time}t</span>
                        ${ev.test_time > 0 ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size: 0.72rem;"><i class="fa-solid fa-file-pen me-1"></i>KT: ${ev.test_time}t</span>` : ''}
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ url('schedules/class') }}/${ev.class_id}/subject/${ev.subject_id}" class="btn btn-sm btn-academic-outline flex-grow-1 fw-semibold">
                            Chi tiết <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold" title="Báo nghỉ buổi này và đôn lịch" onclick="openDashboardPostpone(${ev.id}, '${ev.date}', '${ev.shift}', ${ev.session}, '${ev.class_name}', '${ev.subject_name}')">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Báo nghỉ
                        </button>
                    </div>
                </div>
            `;
        });

        sideContent.innerHTML = html;
    }

    // Dynamic Module Adding in Modal 1
    let moduleIndex = 1;
    function addModuleRow() {
        const tbody = document.getElementById('moduleTableBody');
        const nextNum = tbody.children.length + 1;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="text-center fw-bold text-muted">${nextNum}</td>
            <td><input type="text" name="modules[${moduleIndex}][content]" class="form-control form-control-sm" placeholder="Nội dung bài giảng..." required></td>
            <td><input type="number" name="modules[${moduleIndex}][theory_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
            <td><input type="number" name="modules[${moduleIndex}][practice_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
            <td><input type="number" name="modules[${moduleIndex}][test_time]" class="form-control form-control-sm text-center" value="0" min="0"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()" aria-label="Xóa buổi này">
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        moduleIndex++;
    }

    // Search table
    document.getElementById('tableSearch')?.addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#scheduleTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
        });
    });

    // Quản lý Báo Nghỉ & Đôn Lịch từ Dashboard
    let dashPostponeModal = null;

    function openDashboardPostpone(scheduleId, date, shift, session, className, subjectName) {
        const modalEl = document.getElementById('dashboardPostponeModal');
        if (!dashPostponeModal && modalEl) {
            dashPostponeModal = new bootstrap.Modal(modalEl);
        }

        document.getElementById('dashScheduleId').value = scheduleId;
        document.getElementById('dashClassName').innerText = className;
        document.getElementById('dashSubjectName').innerText = subjectName;
        document.getElementById('dashSessionNum').innerText = `Buổi #${session}`;
        document.getElementById('dashOffDate').innerText = formatVnDate(date);
        document.getElementById('dashOffShift').innerText = shift;
        document.getElementById('dashReplacementShift').value = shift;

        // Gợi ý ngày bù (+3 ngày)
        quickPickDashDate(3, date);

        if (dashPostponeModal) {
            dashPostponeModal.show();
        }
    }

    function quickPickDashDate(daysToAdd, baseDate = null) {
        let base = baseDate;
        if (!base) {
            const rawOffText = document.getElementById('dashOffDate').innerText;
            // Parse dd/mm/yyyy back to yyyy-mm-dd
            const parts = rawOffText.split('/');
            if (parts.length === 3) {
                base = `${parts[2]}-${parts[1]}-${parts[0]}`;
            } else {
                base = new Date().toISOString().slice(0, 10);
            }
        }
        let d = new Date(base);
        d.setDate(d.getDate() + daysToAdd);
        if (d.getDay() === 0) {
            d.setDate(d.getDate() + 1);
        }
        document.getElementById('dashReplacementDate').value = d.toISOString().slice(0, 10);
    }

    function formatVnDate(iso) {
        if (!iso) return '';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    function submitDashboardPostpone(e) {
        e.preventDefault();

        const scheduleId = document.getElementById('dashScheduleId').value;
        const repDate = document.getElementById('dashReplacementDate').value;
        const repShift = document.getElementById('dashReplacementShift').value;
        const reason = document.getElementById('dashReason').value;

        if (!scheduleId || !repDate || !repShift) {
            Swal.fire('Lưu ý', 'Vui lòng chọn ngày học thay thế!', 'warning');
            return;
        }

        const btn = document.getElementById('btnSubmitDashPostpone');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang đôn lịch...';

        fetch("{{ route('schedules.postpone_and_shift') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                schedule_id: scheduleId,
                replacement_date: repDate,
                replacement_shift: repShift,
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = origHtml;

            if (data.success) {
                if (dashPostponeModal) dashPostponeModal.hide();
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
            btn.disabled = false;
            btn.innerHTML = origHtml;
            Swal.fire('Lỗi kết nối', 'Không thể kết nối đến máy chủ.', 'error');
        });
    }
</script>
@endpush
