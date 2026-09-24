<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Kế hoạch Giảng dạy & Sổ tay Giáo án</title>
    
    <!-- Bootstrap 5 CSS & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --card-border: #e2e8f0;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }
        .navbar-custom {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            padding: 1.15rem 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(0,0,0,0.05);
        }
        .stat-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }

        /* Interactive Calendar Styles */
        .calendar-table {
            table-layout: fixed;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 4px;
        }
        .calendar-table th {
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0.6rem 0.2rem;
            color: #64748b;
            background: transparent;
            border: none;
        }
        .calendar-table th.sunday-col {
            color: #ef4444;
        }
        .cal-day-cell {
            height: 86px;
            vertical-align: top;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            position: relative;
        }
        .cal-day-cell:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: scale(1.02);
            z-index: 2;
        }
        .cal-day-cell.dimmed {
            background: #f8fafc;
            opacity: 0.35;
            cursor: default;
        }
        .cal-day-cell.dimmed:hover {
            transform: none;
            background: #f8fafc;
        }
        .cal-day-cell.sunday {
            background: #fef2f2;
            border-color: #fee2e2;
        }
        .cal-day-cell.has-events {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
        .cal-day-cell.active-selected {
            border: 2px solid #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background: #e0f2fe !important;
        }
        .cal-day-number {
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            border-radius: 50%;
        }
        .cal-day-cell.today .cal-day-number {
            background: #2563eb;
            color: #ffffff;
        }
        .cal-badge {
            font-size: 0.68rem;
            padding: 2px 4px;
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
            color: #92400e;
            border-left: 2px solid #f59e0b;
        }
        .cal-badge-chieu {
            background: #e0e7ff;
            color: #3730a3;
            border-left: 2px solid #6366f1;
        }

        /* Detail Side Panel */
        .side-panel {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .side-panel-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background: #f8fafc;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }
        .side-panel-body {
            padding: 1.25rem;
            flex: 1;
            overflow-y: auto;
            max-height: 540px;
        }
        .session-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.85rem;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .session-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            border-color: #cbd5e1;
        }
    </style>
</head>
<body>

<!-- 1. Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-2">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('schedules.index') }}">
            <div class="stat-icon-box bg-primary text-white" style="width: 36px; height: 36px; font-size: 1.05rem;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span>TeachPlan <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">PRO</span></span>
        </a>

        <!-- Right Side: Teacher Switcher & Action Buttons -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <!-- Form Teacher Switcher -->
            <form action="{{ route('schedules.teacher.switch') }}" method="POST" class="d-flex align-items-center gap-2 m-0 me-2">
                @csrf
                <span class="text-muted small d-none d-md-inline fw-semibold"><i class="fa-solid fa-user-tie text-primary me-1"></i> Giảng viên:</span>
                <select name="teacher_id" class="form-select form-select-sm fw-bold border-primary shadow-none" style="min-width: 210px; border-radius: 8px;" onchange="this.form.submit()">
                    @foreach($allTeachers as $t)
                        <option value="{{ $t->id }}" {{ $t->id === $currentTeacher->id ? 'selected' : '' }}>
                            👨‍🏫 {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="d-none d-lg-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary fw-semibold px-3 shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalAddSubject">
                    <i class="fa-solid fa-folder-plus me-1"></i> + Môn mới
                </button>
                <button type="button" class="btn btn-sm btn-success fw-semibold px-3 shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#modalGeneratePlan">
                    <i class="fa-solid fa-calendar-plus me-1"></i> + Lập Lịch
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold px-2 rounded-3" data-bs-toggle="modal" data-bs-target="#modalImportCSV" title="Import CSV">
                    <i class="fa-solid fa-file-csv"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container-fluid px-lg-5 py-3">

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 py-2" role="alert">
            <div class="d-flex align-items-center small">
                <i class="fa-solid fa-circle-check fs-6 me-2 text-success"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3 py-2" role="alert">
            <div class="d-flex align-items-center small">
                <i class="fa-solid fa-triangle-exclamation fs-6 me-2 text-danger"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. Macro KPI Cards (Tổng quan vĩ mô) -->
    <div class="row g-3 mb-4">
        <!-- Card 1 -->
        <div class="col-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Lớp đang dạy</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $totalClassesCount }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
                    <div class="text-success small fw-medium mt-1" style="font-size: 0.75rem;"><i class="fa-solid fa-check me-1"></i>Phân công hiện tại</div>
                </div>
                <div class="stat-icon-box bg-primary-subtle text-primary">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="col-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Môn phụ trách</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $totalSubjectsCount }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                    <div class="text-primary small fw-medium mt-1" style="font-size: 0.75rem;"><i class="fa-solid fa-book me-1"></i>{{ $mySubjects->count() }} môn tạo bởi tôi</div>
                </div>
                <div class="stat-icon-box bg-success-subtle text-success">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="col-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Tổng số buổi dạy</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $totalSessionsCount }} <span class="fs-6 fw-normal text-muted">buổi</span></div>
                    <div class="text-muted small fw-medium mt-1" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i>Thứ 2 - Thứ 7</div>
                </div>
                <div class="stat-icon-box bg-warning-subtle text-warning">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="col-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Google Calendar</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ $syncPercentage }}%</div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                        <span class="badge bg-success-subtle text-success">{{ $syncSynced }} synced</span>
                        <span class="badge bg-warning-subtle text-warning">{{ $syncPending }} pending</span>
                    </div>
                </div>
                <div class="stat-icon-box bg-info-subtle text-info">
                    <i class="fa-brands fa-google"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Interactive Monthly Calendar & Side Detail Panel (Trọng tâm trải nghiệm) -->
    <div class="row g-4 mb-4">
        <!-- Cột Trái (8 phần): Lịch Tháng Trực quan -->
        <div class="col-lg-8">
            <div class="card card-custom p-3 p-md-4 h-100">
                <!-- Calendar Toolbar -->
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <!-- Month Navigation -->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="changeMonth(-1)" title="Tháng trước">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <h5 class="fw-bold text-dark mb-0 mx-2" id="currentMonthYearLabel">
                            Tháng 10 / 2026
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="changeMonth(1)" title="Tháng sau">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <!-- Month Picker input -->
                        <input type="month" id="monthPicker" class="form-control form-control-sm ms-2" style="width: 145px; border-radius: 8px;" onchange="onMonthPickerChange(this.value)">
                    </div>

                    <!-- Scope Switcher: Lịch của tôi vs Toàn trường -->
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-semibold d-none d-sm-inline">Phạm vi:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary" id="btnScopeMy" onclick="setScope('my')">
                                <i class="fa-solid fa-user me-1"></i> Của tôi
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnScopeAll" onclick="setScope('all')">
                                <i class="fa-solid fa-school me-1"></i> Toàn trường
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Calendar Legend / Ghi chú màu -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 text-muted small px-1">
                    <div class="d-flex align-items-center gap-3">
                        <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #f59e0b;"></span> Ca Sáng (07:30 - 11:30)</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background: #6366f1;"></span> Ca Chiều (13:00 - 17:00)</span>
                        <span class="text-danger"><i class="fa-regular fa-calendar-xmark me-1"></i> Chủ Nhật (Nghỉ)</span>
                    </div>
                    <div class="text-secondary fst-italic" style="font-size: 0.75rem;">
                        <i class="fa-regular fa-hand-pointer me-1"></i> Bấm vào ngày để xem chi tiết
                    </div>
                </div>

                <!-- Calendar Matrix Table -->
                <div class="table-responsive">
                    <table class="table calendar-table">
                        <thead>
                            <tr>
                                <th>Thứ 2</th>
                                <th>Thứ 3</th>
                                <th>Thứ 4</th>
                                <th>Thứ 5</th>
                                <th>Thứ 6</th>
                                <th>Thứ 7</th>
                                <th class="sunday-col">Chủ Nhật</th>
                            </tr>
                        </thead>
                        <tbody id="calendarGridBody">
                            <!-- Dynamic Day Cells Rendered via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột Phải (4 phần): Side Panel Chi tiết Ngày được chọn (Không dùng popup) -->
        <div class="col-lg-4">
            <div class="side-panel">
                <div class="side-panel-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">CHI TIẾT LỊCH DẠY</div>
                        <h6 class="fw-bold text-primary mb-0" id="selectedDateTitle">
                            Thứ 2, ngày 05/10/2026
                        </h6>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-bold" id="selectedDateBadge">
                        0 ca dạy
                    </span>
                </div>

                <div class="side-panel-body" id="sidePanelContent">
                    <!-- Session Cards rendered dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Collapsible Section: Bảng Quản lý Tiến độ Lớp - Môn (Thu gọn mặc định) -->
    <div class="card card-custom p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-table-list text-primary me-2"></i>Danh sách Lớp & Môn học (Xuất Sổ tay Word)
                </h5>
                <p class="text-muted small mb-0">Xem tiến độ từng học phần và tải về Sổ tay giảng dạy file .docx</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Search -->
                <div class="position-relative" style="min-width: 220px;">
                    <input type="text" id="tableSearch" class="form-control form-control-sm ps-4 rounded-pill" placeholder="Lọc theo lớp hoặc môn...">
                    <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-2 text-muted small"></i>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border mb-0" id="scheduleTable">
                <thead class="table-light">
                    <tr>
                        <th>Lớp học</th>
                        <th>Môn học</th>
                        <th>Mã môn</th>
                        <th class="text-center">Số buổi</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pairs as $p)
                        <tr>
                            <td>
                                <span class="fw-bold text-primary"><i class="fa-solid fa-users me-1 text-secondary"></i> {{ $p->class?->name }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $p->subject?->name }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                    {{ $p->subject?->code }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 fw-bold">
                                    {{ $p->total_schedules }} buổi
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('schedules.preview', ['class_id' => $p->class_id, 'subject_id' => $p->subject_id]) }}" 
                                       class="btn btn-outline-primary" title="Xem danh sách chi tiết các buổi và sửa inline">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Xem chi tiết
                                    </a>
                                    <a href="{{ route('schedules.export_word', ['class_id' => $p->class_id, 'subject_id' => $p->subject_id]) }}" 
                                       class="btn btn-outline-success" title="Xuất file Word sổ tay giáo án">
                                        <i class="fa-solid fa-file-word me-1"></i> Xuất Word
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Chưa có lớp nào được phân công cho Giảng viên này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================= MODALS ======================= -->

<!-- Modal 1: Thêm Môn học & Mô-đun Buổi học -->
<div class="modal fade" id="modalAddSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-folder-plus text-primary me-2"></i>Thêm Môn học & Mô-đun Bài giảng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                        <h6 class="fw-bold mb-0 text-secondary">
                            <i class="fa-solid fa-list-ol me-1"></i>Danh sách Mô-đun từng buổi:
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addModuleRow()">
                            <i class="fa-solid fa-plus me-1"></i> Thêm buổi
                        </button>
                    </div>

                    <div class="table-responsive border rounded-3 p-2 bg-light">
                        <table class="table table-sm table-borderless align-middle mb-0" id="moduleTable">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width: 50px;">Buổi</th>
                                    <th>Nội dung bài học</th>
                                    <th style="width: 90px;">Tiết LT</th>
                                    <th style="width: 90px;">Tiết TH</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="moduleTableBody">
                                <tr>
                                    <td class="text-center fw-bold text-muted">1</td>
                                    <td><input type="text" name="modules[0][content]" class="form-control form-control-sm" placeholder="Nội dung bài giảng..." required></td>
                                    <td><input type="number" name="modules[0][theory_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
                                    <td><input type="number" name="modules[0][practice_time]" class="form-control form-control-sm text-center" value="3" min="0" required></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold">Lưu Môn học</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Lập Kế hoạch Giảng dạy Cá nhân -->
<div class="modal fade" id="modalGeneratePlan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-calendar-plus text-success me-2"></i>Lập Kế hoạch Giảng dạy Cá nhân
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">Tạo Kế hoạch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Import CSV -->
<div class="modal fade" id="modalImportCSV" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-file-csv text-primary me-2"></i>Import File CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                        <p class="text-muted small">Headers: <code>Mã Môn, Tên Môn, Buổi số, Nội dung giảng dạy, Số tiết LT, Số tiết TH</code></p>
                        <form action="{{ route('schedules.import.subject_contents') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control form-control-sm mb-3" type="file" name="csv_file" accept=".csv,.txt" required>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Tải lên Môn học</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="tab-schedule">
                        <p class="text-muted small">Headers: <code>Ngày, Buổi, Tên Lớp, Tên Môn</code></p>
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

<!-- ======================= JAVASCRIPT ======================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
            document.getElementById('btnScopeMy').className = 'btn btn-primary';
            document.getElementById('btnScopeAll').className = 'btn btn-outline-primary';
        } else {
            activeEvents = allEvents;
            document.getElementById('btnScopeMy').className = 'btn btn-outline-primary';
            document.getElementById('btnScopeAll').className = 'btn btn-primary';
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
                    ${dayEvents.length > 0 ? `<span class="badge rounded-pill bg-primary" style="font-size: 0.6rem; padding: 2px 5px;">${dayEvents.length}</span>` : ''}
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
                    <i class="fa-regular fa-calendar-xmark fa-3x mb-3 text-secondary d-block"></i>
                    <h6 class="fw-bold text-dark">Không có ca dạy nào</h6>
                    <p class="small text-muted mb-0">Không có lịch học hoặc lịch giảng dạy trong ngày này.</p>
                </div>
            `;
            return;
        }

        // Sắp xếp ca Sáng trước, Chiều sau
        eventsOnDay.sort((a, b) => a.shift === 'Sáng' ? -1 : 1);

        let html = '';
        eventsOnDay.forEach(ev => {
            const isMorning = ev.shift === 'Sáng';
            const shiftBadgeClass = isMorning ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle';
            const shiftTime = isMorning ? '07:30 - 11:30' : '13:00 - 17:00';
            const icon = isMorning ? 'fa-sun' : 'fa-moon';

            let syncBadge = '';
            if (ev.sync_status === 'synced') {
                syncBadge = '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i>synced</span>';
            } else if (ev.sync_status === 'modified') {
                syncBadge = '<span class="badge bg-info-subtle text-info"><i class="fa-solid fa-pen me-1"></i>modified</span>';
            } else {
                syncBadge = '<span class="badge bg-warning-subtle text-warning"><i class="fa-regular fa-clock me-1"></i>pending</span>';
            }

            html += `
                <div class="session-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge ${shiftBadgeClass} px-2 py-1 fw-bold">
                            <i class="fa-solid ${icon} me-1"></i>Ca ${ev.shift} (${shiftTime})
                        </span>
                        ${syncBadge}
                    </div>

                    <div class="d-flex align-items-baseline gap-2 mb-1">
                        <span class="badge bg-secondary-subtle text-secondary border">Lớp</span>
                        <h6 class="fw-bold text-dark mb-0">${ev.class_name}</h6>
                    </div>

                    <div class="fw-semibold text-primary small mb-1">
                        <i class="fa-solid fa-book me-1"></i>${ev.subject_name}
                        <span class="text-secondary fw-normal">• Buổi #${ev.session}</span>
                    </div>

                    ${ev.teacher_name ? `<div class="text-muted small mb-2"><i class="fa-solid fa-user-tie me-1"></i>Giảng viên: <strong>${ev.teacher_name}</strong></div>` : ''}

                    <div class="bg-light rounded p-2 small text-secondary mb-3" style="font-size: 0.785rem;">
                        <strong>Nội dung:</strong> ${ev.content}
                    </div>

                    <a href="/schedules/class/${ev.class_id}/subject/${ev.subject_id}" class="btn btn-sm btn-outline-primary w-100 fw-semibold">
                        Xem chi tiết & Sửa lịch <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
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
            <td><input type="number" name="modules[${moduleIndex}][practice_time]" class="form-control form-control-sm text-center" value="3" min="0" required></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()">
                    <i class="fa-solid fa-trash-can"></i>
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
</script>
</body>
</html>
