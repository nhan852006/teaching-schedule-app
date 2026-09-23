<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Kế hoạch Giảng dạy & Sổ tay Giáo án</title>
    
    <!-- Bootstrap 5 CSS & FontAwesome 6 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-bg: #f8fafc;
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
            padding: 1.25rem 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.06);
        }
        .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .card-custom {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--card-border);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            font-size: 0.825rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            color: #64748b;
            border-top: none;
        }
        .badge-shift-sang {
            background-color: #fef3c7;
            color: #b45309;
            font-weight: 600;
        }
        .badge-shift-chieu {
            background-color: #e0e7ff;
            color: #4338ca;
            font-weight: 600;
        }
        .modal-header-custom {
            background: #f8fafc;
            border-bottom: 1px solid var(--card-border);
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }
        .btn-custom-action {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.55rem 1.15rem;
        }
        .teacher-badge {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- 1. Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="{{ route('schedules.index') }}">
            <div class="stat-icon-box bg-primary text-white" style="width: 38px; height: 38px; font-size: 1.1rem;">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <span>TeachPlan <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">PRO</span></span>
        </a>

        <!-- Right Side: Teacher Switcher & Quick Actions -->
        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Form Teacher Switcher -->
            <form action="{{ route('schedules.teacher.switch') }}" method="POST" class="d-flex align-items-center gap-2 m-0">
                @csrf
                <span class="text-muted small d-none d-md-inline fw-semibold"><i class="fa-solid fa-user-tie text-primary me-1"></i> Giảng viên:</span>
                <select name="teacher_id" class="form-select form-select-sm fw-bold border-primary shadow-none" style="min-width: 220px; border-radius: 8px;" onchange="this.form.submit()">
                    @foreach($allTeachers as $t)
                        <option value="{{ $t->id }}" {{ $t->id === $currentTeacher->id ? 'selected' : '' }}>
                            👨‍🏫 {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container-fluid px-lg-5 py-4">

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-danger"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Welcome & Action Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                Không gian Giảng dạy: <span class="text-primary">{{ $currentTeacher->name }}</span>
            </h3>
            <p class="text-muted small mb-0">
                <i class="fa-regular fa-envelope me-1"></i> {{ $currentTeacher->email }} | Quản lý tiến độ giảng dạy, mô-đun học phần & xuất sổ tay cá nhân.
            </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-custom-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddSubject">
                <i class="fa-solid fa-folder-plus me-1"></i> + Môn & Mô-đun mới
            </button>
            <button type="button" class="btn btn-success btn-custom-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGeneratePlan">
                <i class="fa-solid fa-calendar-plus me-1"></i> + Lập Kế hoạch Dạy
            </button>
            <button type="button" class="btn btn-outline-secondary btn-custom-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddClass">
                <i class="fa-solid fa-users-rectangle me-1"></i> + Thêm Lớp
            </button>
            <button type="button" class="btn btn-outline-primary btn-custom-action shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportCSV">
                <i class="fa-solid fa-file-csv me-1"></i> Import CSV
            </button>
        </div>
    </div>

    <!-- 2. KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Card 1 -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Lớp đang dạy</div>
                    <div class="fs-2 fw-bold text-dark mt-1">{{ $totalClassesCount }} <span class="fs-6 fw-normal text-muted">lớp</span></div>
                    <div class="text-success small fw-medium mt-1"><i class="fa-solid fa-circle-check me-1"></i>Theo phân công</div>
                </div>
                <div class="stat-icon-box bg-primary-subtle text-primary">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Môn học phụ trách</div>
                    <div class="fs-2 fw-bold text-dark mt-1">{{ $totalSubjectsCount }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                    <div class="text-primary small fw-medium mt-1"><i class="fa-solid fa-book-bookmark me-1"></i>{{ $mySubjects->count() }} môn sở hữu</div>
                </div>
                <div class="stat-icon-box bg-success-subtle text-success">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Tổng số buổi đã lên lịch</div>
                    <div class="fs-2 fw-bold text-dark mt-1">{{ $totalSessionsCount }} <span class="fs-6 fw-normal text-muted">buổi</span></div>
                    <div class="text-muted small fw-medium mt-1"><i class="fa-regular fa-clock me-1"></i>Thứ 2 - Thứ 7</div>
                </div>
                <div class="stat-icon-box bg-warning-subtle text-warning">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Google Calendar</div>
                    <div class="fs-2 fw-bold text-dark mt-1">{{ $syncPercentage }}%</div>
                    <div class="text-muted small mt-1">
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

    <!-- 3. Main Dashboard Body -->
    <div class="row g-4">
        <!-- Cột Phải: Bảng Tiến độ Lớp - Môn Học & Xuất Sổ tay (Chiếm 8 phần) -->
        <div class="col-lg-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-list-check text-primary me-2"></i>Kế hoạch Giảng dạy theo Lớp & Môn
                    </h5>
                    <!-- Live Search Input -->
                    <div class="position-relative" style="min-width: 250px;">
                        <input type="text" id="tableSearch" class="form-control form-control-sm ps-4 rounded-pill" placeholder="Tìm theo lớp hoặc môn...">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-2 text-muted small"></i>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="scheduleTable">
                        <thead>
                            <tr>
                                <th>Lớp học</th>
                                <th>Môn học</th>
                                <th>Mã môn</th>
                                <th class="text-center">Số buổi</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pairs as $p)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="stat-icon-box bg-primary-subtle text-primary" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                <i class="fa-solid fa-users"></i>
                                            </div>
                                            <span class="fw-bold text-dark">{{ $p->class?->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-secondary">{{ $p->subject?->name }}</div>
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
                                               class="btn btn-outline-primary" title="Xem chi tiết & sửa inline">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> Chi tiết & Sửa
                                            </a>
                                            <a href="{{ route('schedules.export_word', ['class_id' => $p->class_id, 'subject_id' => $p->subject_id]) }}" 
                                               class="btn btn-outline-success" title="Xuất file Word sổ tay giảng dạy">
                                                <i class="fa-solid fa-file-word"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fa-2x mb-2 text-secondary d-block"></i>
                                        Chưa có kế hoạch giảng dạy nào cho Thầy/Cô này.<br>
                                        Bấm <strong>"+ Lập Kế hoạch Dạy"</strong> hoặc <strong>"Import CSV"</strong> để bắt đầu ngay!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột Trái: Lịch Giảng Sắp Tới & Môn Học Phụ Trách (Chiếm 4 phần) -->
        <div class="col-lg-4">
            <!-- Widget 1: Lịch dạy sắp tới -->
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-regular fa-calendar-check text-warning me-2"></i>Lịch dạy sắp tới (Thứ 2 - Thứ 7)
                </h6>
                <div class="list-group list-group-flush">
                    @forelse($upcomingSchedules as $up)
                        <div class="list-group-item px-0 py-2 border-light">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold text-dark small">{{ $up->class?->name }} - {{ $up->subject?->name }}</div>
                                    <div class="text-muted" style="font-size: 0.775rem;">
                                        <i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($up->teaching_date)->format('d/m/Y') }} (Thứ {{ \Carbon\Carbon::parse($up->teaching_date)->dayOfWeekIso + 1 == 8 ? 'CN' : \Carbon\Carbon::parse($up->teaching_date)->dayOfWeekIso + 1 }})
                                        • Buổi #{{ $up->session_number }}
                                    </div>
                                </div>
                                <span class="badge {{ $up->session_shift === 'Sáng' ? 'badge-shift-sang' : 'badge-shift-chieu' }} px-2 py-1">
                                    {{ $up->session_shift }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small py-3 text-center">Chưa có lịch dạy sắp tới.</div>
                    @endforelse
                </div>
            </div>

            <!-- Widget 2: Môn học do Thầy/Cô phụ trách -->
            <div class="card card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-layer-group text-info me-2"></i>Môn học của tôi
                    </h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0 text-primary" data-bs-toggle="modal" data-bs-target="#modalAddSubject">
                        + Tạo môn
                    </button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($mySubjects as $subj)
                        <div class="list-group-item px-0 py-2 border-light d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold text-dark small">{{ $subj->name }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Mã: {{ $subj->code }}</div>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $subj->total_sessions }} buổi</span>
                        </div>
                    @empty
                        <div class="text-muted small py-3 text-center">Chưa tạo môn học nào.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================= MODALS ======================= -->

<!-- Modal 1: Thêm Môn học & Mô-đun Buổi học -->
<div class="modal fade" id="modalAddSubject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-folder-plus text-primary me-2"></i>Thêm Môn học & Xây dựng Mô-đun Bài giảng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('schedules.subjects.store_custom') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Mã Môn học <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="VD: IT4505" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Tên Môn học <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="VD: Chuyên đề Phát triển Ứng dụng Doanh nghiệp" required>
                        </div>
                    </div>

                    <!-- Dynamic Modules -->
                    <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                        <h6 class="fw-bold mb-0 text-secondary">
                            <i class="fa-solid fa-list-ol me-1"></i>Danh sách Mô-đun / Buổi học:
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addModuleRow()">
                            <i class="fa-solid fa-plus me-1"></i> Thêm buổi
                        </button>
                    </div>

                    <div class="table-responsive border rounded-3 p-2 bg-light">
                        <table class="table table-sm table-borderless align-middle mb-0" id="moduleTable">
                            <thead>
                                <tr class="text-muted small">
                                    <th style="width: 60px;">Buổi</th>
                                    <th>Nội dung bài học</th>
                                    <th style="width: 100px;">Tiết LT</th>
                                    <th style="width: 100px;">Tiết TH</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="moduleTableBody">
                                <!-- Row 1 -->
                                <tr>
                                    <td class="text-center fw-bold text-muted">1</td>
                                    <td><input type="text" name="modules[0][content]" class="form-control form-control-sm" placeholder="Nội dung bài giảng..." required></td>
                                    <td><input type="number" name="modules[0][theory_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
                                    <td><input type="number" name="modules[0][practice_time]" class="form-control form-control-sm text-center" value="3" min="0" required></td>
                                    <td></td>
                                </tr>
                                <!-- Row 2 -->
                                <tr>
                                    <td class="text-center fw-bold text-muted">2</td>
                                    <td><input type="text" name="modules[1][content]" class="form-control form-control-sm" placeholder="Nội dung bài giảng..." required></td>
                                    <td><input type="number" name="modules[1][theory_time]" class="form-control form-control-sm text-center" value="2" min="0" required></td>
                                    <td><input type="number" name="modules[1][practice_time]" class="form-control form-control-sm text-center" value="3" min="0" required></td>
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

<!-- Modal 2: Lập Kế hoạch Giảng dạy Cá nhân (Tự động sinh lịch) -->
<div class="modal fade" id="modalGeneratePlan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header modal-header-custom">
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
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">Tạo Kế hoạch ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Thêm nhanh Lớp học -->
<div class="modal fade" id="modalAddClass" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header modal-header-custom">
                <h6 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-users text-secondary me-2"></i>Thêm Lớp học mới
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('schedules.classes.store_quick') }}" method="POST">
                @csrf
                <div class="modal-body p-3">
                    <label class="form-label small fw-bold">Tên Lớp học:</label>
                    <input type="text" name="name" class="form-control" placeholder="VD: K21-CNTT1" required>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Lưu Lớp</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 4: Import CSV -->
<div class="modal fade" id="modalImportCSV" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-file-csv text-primary me-2"></i>Tải lên File CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Nav Tabs -->
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
                    <!-- Tab 1 -->
                    <div class="tab-pane fade show active" id="tab-subject">
                        <p class="text-muted small">Headers: <code>Mã Môn, Tên Môn, Buổi số, Nội dung giảng dạy, Số tiết LT, Số tiết TH</code></p>
                        <form action="{{ route('schedules.import.subject_contents') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control form-control-sm mb-3" type="file" name="csv_file" accept=".csv,.txt" required>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Import Nội dung Môn học</button>
                        </form>
                    </div>

                    <!-- Tab 2 -->
                    <div class="tab-pane fade" id="tab-schedule">
                        <p class="text-muted small">Headers: <code>Ngày, Buổi, Tên Lớp, Tên Môn</code></p>
                        <form action="{{ route('schedules.import.schedules') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input class="form-control form-control-sm mb-3" type="file" name="csv_file" accept=".csv,.txt" required>
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Import Lịch Giảng dạy</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live Search Table
    document.getElementById('tableSearch').addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        const rows = document.querySelectorAll('#scheduleTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    });

    // Dynamic Module Adding in Modal 1
    let moduleIndex = 2;
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
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeModuleRow(this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        moduleIndex++;
    }

    function removeModuleRow(btn) {
        const row = btn.closest('tr');
        const tbody = document.getElementById('moduleTableBody');
        if (tbody.children.length > 1) {
            row.remove();
            // Re-index displayed session numbers
            Array.from(tbody.children).forEach((tr, idx) => {
                tr.children[0].innerText = idx + 1;
            });
        }
    }
</script>
</body>
</html>
