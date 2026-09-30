@extends('layouts.app')

@section('title', 'Quản lý Giáo viên Bộ môn - Khoa Công Nghệ Thông Tin')
@section('meta_description', 'Hệ thống quản lý hồ sơ giáo viên bộ môn, phân công giảng dạy, dùng chung môn học và lập kế hoạch giảng dạy cá nhân')

@section('header_actions')
    <!-- Nút Thêm mới giáo viên -->
    <button type="button" class="btn btn-sm btn-academic-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddTeacher">
        <i class="fa-solid fa-user-plus me-1" aria-hidden="true"></i> Thêm Giáo Viên Mới
    </button>
@endsection

@section('content')
<div class="container-fluid px-lg-5 py-4">

    <!-- Breadcrumb Navigation -->
    <nav aria-label="Đường dẫn phân cấp học vụ" class="mb-3">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('schedules.index') }}" class="text-decoration-none text-muted">Trang chủ Lịch dạy</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Quản Lý Giáo Viên Bộ Môn</li>
        </ol>
    </nav>

    <!-- Alert Messages (Toast / Flash Message) -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-danger"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Banner Header & Giảng viên đang kích hoạt -->
    <div class="card-academic p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-2 font-monospace">
                    HỒ SƠ NHÂN SỰ & PHÂN CÔNG GIẢNG DẠY
                </span>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Danh Sách Giáo Viên Bộ Môn
                </h1>
                <p class="text-muted small mb-0">
                    Quản lý thông tin giảng viên, sử dụng chung môn học và giáo án mẫu toàn khoa, phân công lịch dạy độc lập theo từng tài khoản.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="bg-light p-3 rounded-2 border text-start d-inline-block shadow-sm">
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Không gian đang làm việc</div>
                    <div class="fw-bold text-primary mt-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-check text-success"></i>
                        <span>{{ $currentTeacher->full_name_with_title }}</span>
                    </div>
                    <div class="text-muted small mt-1 font-monospace" style="font-size: 0.75rem;">
                        <i class="fa-regular fa-envelope me-1"></i>{{ $currentTeacher->email }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Khối Thống Kê KPI Tổng Thể -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card-academic p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">Tổng số giáo viên</div>
                        <div class="h3 fw-bold text-dark mb-0 mt-1">{{ $totalTeachers }}</div>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-3">
                        <i class="fa-solid fa-users-gear fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card-academic p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">Đang giảng dạy</div>
                        <div class="h3 fw-bold text-success mb-0 mt-1">{{ $activeTeachers }}</div>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-3">
                        <i class="fa-solid fa-user-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card-academic p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">Tạm nghỉ / Đã khóa</div>
                        <div class="h3 fw-bold text-warning-emphasis mb-0 mt-1">{{ $inactiveTeachers }}</div>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3">
                        <i class="fa-solid fa-user-slash fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card-academic p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.75rem;">Tổng buổi giảng dạy</div>
                        <div class="h3 fw-bold text-info-emphasis mb-0 mt-1">{{ $totalSchedulesCount }}</div>
                    </div>
                    <div class="p-3 bg-info-subtle text-info-emphasis rounded-3">
                        <i class="fa-solid fa-calendar-days fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thanh Công Cụ Tìm Kiếm & Lọc -->
    <div class="card-academic p-3 mb-4 bg-white">
        <form action="{{ route('teachers.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" 
                           placeholder="Tìm kiếm theo tên giáo viên, email, số điện thoại..." 
                           value="{{ $search }}">
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <select name="status" class="form-select text-muted" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Đang giảng dạy (Active)</option>
                    <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Tạm nghỉ / Đã khóa (Inactive)</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <select name="title" class="form-select text-muted" onchange="this.form.submit()">
                    <option value="">-- Học hàm/Học vị --</option>
                    @foreach($availableTitles as $t)
                        <option value="{{ $t }}" {{ $titleFilter === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-academic-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Lọc
                </button>
                @if($search || $statusFilter || $titleFilter)
                    <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- BẢNG DỮ LIỆU DESKTOP (Sticky Header, Scroll Container) -->
    <div class="card-academic bg-white shadow-sm d-none d-md-block mb-4 overflow-hidden">
        <div class="table-responsive" style="max-height: 650px;">
            <table class="table-academic border mb-0" aria-label="Danh sách giáo viên bộ môn">
                <thead class="sticky-top">
                    <tr>
                        <th scope="col" class="text-center" style="width: 50px;">#</th>
                        <th scope="col" style="min-width: 220px;">Giáo Viên</th>
                        <th scope="col" style="min-width: 200px;">Thông Tin Liên Hệ</th>
                        <th scope="col" style="min-width: 180px;">Khoa / Bộ Môn</th>
                        <th scope="col" class="text-center" style="width: 130px;">Quy Mô Dạy</th>
                        <th scope="col" class="text-center" style="width: 140px;">Trạng Thái</th>
                        <th scope="col" class="text-end" style="width: 240px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                        @php
                            $isCurrent = ($teacher->id === $currentTeacher->id);
                        @endphp
                        <tr class="{{ $isCurrent ? 'bg-primary-subtle bg-opacity-10' : '' }}" id="teacher-row-{{ $teacher->id }}">
                            <td class="text-center fw-bold text-muted font-monospace">
                                {{ $teachers->firstItem() + $index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm flex-shrink-0" 
                                         style="width: 36px; height: 36px; background-color: {{ $isCurrent ? '#1b4d89' : '#64748b' }};">
                                        {{ mb_substr($teacher->name, 0, 1, 'UTF-8') }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                            <span>{{ $teacher->full_name_with_title }}</span>
                                            @if($isCurrent)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 0.68rem;">Đang chọn</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small font-monospace" style="font-size: 0.75rem;">
                                            Mã định danh: #{{ $teacher->id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="fa-regular fa-envelope me-1 text-secondary"></i><a href="mailto:{{ $teacher->email }}" class="text-decoration-none text-dark">{{ $teacher->email }}</a></div>
                                    @if($teacher->phone)
                                        <div class="text-muted mt-0.5"><i class="fa-solid fa-phone me-1 text-secondary"></i>{{ $teacher->phone }}</div>
                                    @else
                                        <div class="text-muted fst-italic" style="font-size: 0.75rem;">Chưa có SĐT</div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-secondary">
                                    <i class="fa-solid fa-building-columns me-1 text-muted"></i>{{ $teacher->department ?: 'Khoa CNTT' }}
                                </div>
                                @if($teacher->notes)
                                    <div class="text-muted fst-italic mt-1 text-truncate" style="max-width: 220px; font-size: 0.75rem;" title="{{ $teacher->notes }}">
                                        {{ $teacher->notes }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" title="Số môn học trực tiếp biên soạn">
                                    <i class="fa-solid fa-book me-1"></i>{{ $teacher->subjects_count }} môn
                                </span>
                                <div class="mt-1">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" title="Tổng số buổi dạy được phân công">
                                        <i class="fa-solid fa-calendar-check me-1"></i>{{ $teacher->schedules_count }} buổi
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-xs rounded-pill px-2 py-1 border {{ $teacher->status === 'active' ? 'btn-outline-success' : 'btn-outline-secondary' }}" 
                                        onclick="toggleTeacherStatus({{ $teacher->id }})" 
                                        id="status-btn-{{ $teacher->id }}"
                                        title="Click để đổi trạng thái">
                                    <i class="fa-solid {{ $teacher->status === 'active' ? 'fa-check' : 'fa-lock' }} me-1"></i>
                                    <span id="status-label-{{ $teacher->id }}">{{ $teacher->status_label }}</span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                    <!-- Nút Chuyển không gian làm việc -->
                                    @if(!$isCurrent)
                                        <a href="{{ route('teachers.switch', $teacher->id) }}" 
                                           class="btn btn-academic-primary" 
                                           title="Chuyển sang làm việc với tư cách giảng viên này">
                                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Vào Lịch
                                        </a>
                                    @else
                                        <a href="{{ route('schedules.index') }}" 
                                           class="btn btn-outline-primary" 
                                           title="Đang trong không gian làm việc này">
                                            <i class="fa-solid fa-house-user me-1"></i> Dashboard
                                        </a>
                                    @endif

                                    <!-- Nút Lập lịch dạy phân công môn cho giáo viên này -->
                                    <button type="button" 
                                            class="btn btn-outline-success" 
                                            title="Phân công môn học sẵn có & Lập lịch dạy cho giáo viên này"
                                            onclick="openAssignPlanModal({{ $teacher->id }}, '{{ addslashes($teacher->full_name_with_title) }}')">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </button>

                                    <!-- Nút Sửa hồ sơ -->
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            title="Sửa thông tin hồ sơ"
                                            onclick='openEditTeacherModal(@json($teacher))'>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Nút Xóa an toàn -->
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            title="Xóa giáo viên"
                                            onclick="confirmDeleteTeacher({{ $teacher->id }}, '{{ addslashes($teacher->full_name_with_title) }}', {{ $teacher->subjects_count }}, {{ $teacher->schedules_count }})">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-user-xmark fs-2 text-secondary mb-2 d-block"></i>
                                Không tìm thấy giáo viên nào phù hợp với bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- GIAO DIỆN MOBILE / TABLET (< 768px): Thẻ Card tiện lợi -->
    <div class="d-md-none mb-4">
        @forelse($teachers as $teacher)
            @php
                $isCurrent = ($teacher->id === $currentTeacher->id);
            @endphp
            <div class="card-academic p-3 mb-3 bg-white border-start border-4 {{ $isCurrent ? 'border-primary' : 'border-secondary' }} shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm flex-shrink-0" 
                             style="width: 36px; height: 36px; background-color: {{ $isCurrent ? '#1b4d89' : '#64748b' }};">
                            {{ mb_substr($teacher->name, 0, 1, 'UTF-8') }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark fs-6">{{ $teacher->full_name_with_title }}</div>
                            <div class="text-muted small font-monospace">{{ $teacher->department ?: 'Khoa CNTT' }}</div>
                        </div>
                    </div>
                    <div>
                        <span class="badge {{ $teacher->status_badge_class }}">
                            {{ $teacher->status_label }}
                        </span>
                    </div>
                </div>

                <div class="small text-muted py-2 border-top border-bottom my-2">
                    <div><i class="fa-regular fa-envelope me-1"></i>{{ $teacher->email }}</div>
                    @if($teacher->phone)
                        <div><i class="fa-solid fa-phone me-1"></i>{{ $teacher->phone }}</div>
                    @endif
                    <div class="mt-1 d-flex gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $teacher->subjects_count }} môn</span>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $teacher->schedules_count }} buổi dạy</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-1 pt-1">
                    @if(!$isCurrent)
                        <a href="{{ route('teachers.switch', $teacher->id) }}" class="btn btn-sm btn-academic-primary flex-grow-1">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Vào Lịch
                        </a>
                    @else
                        <a href="{{ route('schedules.index') }}" class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="fa-solid fa-house-user me-1"></i> Dashboard
                        </a>
                    @endif

                    <button type="button" class="btn btn-sm btn-outline-success" 
                            onclick="openAssignPlanModal({{ $teacher->id }}, '{{ addslashes($teacher->full_name_with_title) }}')" title="Phân công lịch">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                            onclick='openEditTeacherModal(@json($teacher))' title="Sửa">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="confirmDeleteTeacher({{ $teacher->id }}, '{{ addslashes($teacher->full_name_with_title) }}', {{ $teacher->subjects_count }}, {{ $teacher->schedules_count }})" title="Xóa">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="card-academic p-4 text-center text-muted bg-white">
                <i class="fa-solid fa-user-xmark fs-2 text-secondary mb-2 d-block"></i>
                Không tìm thấy giáo viên nào.
            </div>
        @endforelse
    </div>

    <!-- Phân Trang -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="text-muted small">
            Hiển thị <strong>{{ $teachers->count() }}</strong> trên tổng số <strong>{{ $totalTeachers }}</strong> giáo viên
        </div>
        <div>
            {{ $teachers->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: THÊM MỚI GIÁO VIÊN BỘ MÔN                                        -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAddTeacher" tabindex="-1" aria-labelledby="modalAddTeacherTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-3">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalAddTeacherTitle">
                    <i class="fa-solid fa-user-plus text-primary me-2"></i>Thêm Giáo Viên Mới
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('teachers.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Họ và Tên -->
                        <div class="col-sm-8">
                            <label class="form-label small fw-bold text-dark">Họ và Tên Giáo Viên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Ví dụ: Hoàng Văn Nhân" required>
                        </div>
                        <!-- Học hàm / Học vị -->
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold text-dark">Học vị</label>
                            <select name="academic_title" class="form-select form-select-sm">
                                <option value="ThS" selected>ThS (Thạc sĩ)</option>
                                <option value="TS">TS (Tiến sĩ)</option>
                                <option value="PGS.TS">PGS.TS</option>
                                <option value="GS.TS">GS.TS</option>
                                <option value="KS">KS (Kỹ sư)</option>
                                <option value="CN">CN (Cử nhân)</option>
                                <option value="">Khác / Không</option>
                            </select>
                        </div>

                        <!-- Email -->
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="gv@university.edu.vn" required>
                        </div>
                        <!-- Số điện thoại -->
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">Số Điện Thoại</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="0912 345 678">
                        </div>

                        <!-- Khoa / Bộ Môn -->
                        <div class="col-sm-8">
                            <label class="form-label small fw-bold text-dark">Khoa / Bộ Môn Phụ Trách</label>
                            <input type="text" name="department" class="form-control form-control-sm" value="Khoa Công Nghệ Thông Tin">
                        </div>
                        <!-- Trạng thái -->
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold text-dark">Trạng Thái</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active" selected>Đang giảng dạy</option>
                                <option value="inactive">Tạm nghỉ</option>
                            </select>
                        </div>

                        <!-- Mật khẩu khởi tạo -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Mật Khẩu Khởi Tạo</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Mặc định: password123 nếu để trống">
                        </div>

                        <!-- Ghi chú -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Ghi Chú Nghiệp Vụ</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Ví dụ: Giảng viên chuyên ngành Mạng và Hệ thống..."></textarea>
                        </div>

                        <!-- Checkbox Chuyển sang làm việc ngay -->
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="switch_now" value="1" id="checkSwitchNow" checked>
                                <label class="form-check-label small fw-semibold text-primary" for="checkSwitchNow">
                                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Chuyển sang không gian làm việc của giáo viên này ngay sau khi tạo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm">
                        <i class="fa-solid fa-check me-1"></i> Lưu Giáo Viên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: CHỈNH SỬA HỒ SƠ GIÁO VIÊN                                        -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalEditTeacher" tabindex="-1" aria-labelledby="modalEditTeacherTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-3">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalEditTeacherTitle">
                    <i class="fa-solid fa-user-pen text-primary me-2"></i>Cập Nhật Hồ Sơ Giáo Viên
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formEditTeacher" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-8">
                            <label class="form-label small fw-bold text-dark">Họ và Tên Giáo Viên <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold text-dark">Học vị</label>
                            <select name="academic_title" id="edit_academic_title" class="form-select form-select-sm">
                                <option value="ThS">ThS (Thạc sĩ)</option>
                                <option value="TS">TS (Tiến sĩ)</option>
                                <option value="PGS.TS">PGS.TS</option>
                                <option value="GS.TS">GS.TS</option>
                                <option value="KS">KS (Kỹ sư)</option>
                                <option value="CN">CN (Cử nhân)</option>
                                <option value="">Khác / Không</option>
                            </select>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="edit_email" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-dark">Số Điện Thoại</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control form-control-sm">
                        </div>

                        <div class="col-sm-8">
                            <label class="form-label small fw-bold text-dark">Khoa / Bộ Môn</label>
                            <input type="text" name="department" id="edit_department" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label small fw-bold text-dark">Trạng Thái</label>
                            <select name="status" id="edit_status" class="form-select form-select-sm">
                                <option value="active">Đang giảng dạy</option>
                                <option value="inactive">Tạm nghỉ</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Đổi Mật Khẩu (Để trống nếu giữ nguyên)</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Nhập mật khẩu mới...">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold text-dark">Ghi Chú</label>
                            <textarea name="notes" id="edit_notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Cập Nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: PHÂN CÔNG MÔN HỌC SẴN CÓ & LẬP LỊCH DẠY CHO GIÁO VIÊN            -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalAssignPlan" tabindex="-1" aria-labelledby="modalAssignPlanTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-3">
                <h2 class="modal-title h5 fw-bold text-dark mb-0" id="modalAssignPlanTitle">
                    <i class="fa-solid fa-calendar-plus text-success me-2"></i>Phân Công Môn & Lập Lịch Giảng Dạy
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formAssignPlan" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-info-subtle small py-2 px-3 mb-3">
                        <i class="fa-solid fa-circle-info me-1 text-info-emphasis"></i>
                        Giáo viên nhận dạy: <strong class="text-dark" id="assignTeacherName">...</strong><br>
                        Hệ thống sẽ sử dụng môn học sẵn có (kèm toàn bộ nội dung buổi và giáo án mẫu chuẩn) để lập kế hoạch dạy cho lớp.
                    </div>

                    <!-- Chọn Lớp Học -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">1. Chọn Lớp Học Phụ Trách <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select form-select-sm" required>
                            <option value="">-- Chọn lớp học --</option>
                            @foreach($allClasses as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Chọn Môn Học Sẵn Có -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark">2. Chọn Môn Học Sẵn Có Của Bộ Môn <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select form-select-sm" required>
                            <option value="">-- Chọn môn học của Khoa/Bộ môn --</option>
                            @foreach($allSubjects as $s)
                                <option value="{{ $s->id }}">
                                    [{{ $s->code }}] {{ $s->name }} ({{ $s->total_sessions ?: $s->contents_count }} buổi - {{ $s->type_label }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small" style="font-size: 0.72rem;">
                            Toàn bộ đề cương, số tiết LT/TH và file giáo án mẫu đã tải lên sẽ được dùng chung tự động.
                        </div>
                    </div>

                    <!-- Ngày Bắt Đầu & Ca Học -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label small fw-bold text-dark">3. Ngày Bắt Đầu Dạy <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label small fw-bold text-dark">4. Ca Dạy <span class="text-danger">*</span></label>
                            <select name="session_shift" class="form-select form-select-sm" required>
                                <option value="Sáng">Ca Sáng (07:30 - 11:30)</option>
                                <option value="Chiều">Ca Chiều (13:00 - 17:00)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ngày Học Trong Tuần -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark">5. Các Ngày Học Trong Tuần (Thứ 2 - Thứ 7):</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="1" id="assign_d1" checked>
                                <label class="form-check-label small" for="assign_d1">Thứ 2</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="2" id="assign_d2">
                                <label class="form-check-label small" for="assign_d2">Thứ 3</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="3" id="assign_d3" checked>
                                <label class="form-check-label small" for="assign_d3">Thứ 4</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="4" id="assign_d4">
                                <label class="form-check-label small" for="assign_d4">Thứ 5</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="5" id="assign_d5" checked>
                                <label class="form-check-label small" for="assign_d5">Thứ 6</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="6" id="assign_d6">
                                <label class="form-check-label small" for="assign_d6">Thứ 7</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 shadow-sm">
                        <i class="fa-solid fa-calendar-check me-1"></i> Khởi Tạo & Vào Lớp
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form ẩn dùng để Xóa Giáo Viên -->
<form id="formDeleteTeacher" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Mở modal Sửa thông tin giáo viên
    function openEditTeacherModal(teacher) {
        document.getElementById('formEditTeacher').action = `{{ url('teachers') }}/${teacher.id}`;
        document.getElementById('edit_name').value = teacher.name || '';
        document.getElementById('edit_academic_title').value = teacher.academic_title || 'ThS';
        document.getElementById('edit_email').value = teacher.email || '';
        document.getElementById('edit_phone').value = teacher.phone || '';
        document.getElementById('edit_department').value = teacher.department || 'Khoa Công Nghệ Thông Tin';
        document.getElementById('edit_status').value = teacher.status || 'active';
        document.getElementById('edit_notes').value = teacher.notes || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditTeacher'));
        modal.show();
    }

    // Mở modal Phân công môn & Lập lịch
    function openAssignPlanModal(teacherId, teacherName) {
        document.getElementById('formAssignPlan').action = `{{ url('teachers') }}/${teacherId}/assign-plan`;
        document.getElementById('assignTeacherName').innerText = teacherName;

        const modal = new bootstrap.Modal(document.getElementById('modalAssignPlan'));
        modal.show();
    }

    // Xác nhận Xóa an toàn
    function confirmDeleteTeacher(id, name, subjectsCount, schedulesCount) {
        if (subjectsCount > 0 || schedulesCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Không thể xóa giáo viên',
                html: `Giáo viên <strong>${name}</strong> hiện đang phụ trách <strong>${subjectsCount} môn học</strong> và <strong>${schedulesCount} buổi giảng dạy</strong>.<br><br>Vui lòng chuyển trạng thái sang <strong>Tạm nghỉ / Đã khóa</strong> hoặc bàn giao lớp học trước khi xóa.`,
                confirmButtonText: 'Đã hiểu'
            });
            return;
        }

        Swal.fire({
            title: `Xóa giáo viên ${name}?`,
            text: 'Hành động này không thể hoàn tác nếu giáo viên này chưa có dữ liệu ràng buộc.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('formDeleteTeacher');
                form.action = `{{ url('teachers') }}/${id}`;
                form.submit();
            }
        });
    }

    // Đổi nhanh trạng thái Hoạt động / Tạm nghỉ qua AJAX
    function toggleTeacherStatus(id) {
        const btn = document.getElementById(`status-btn-${id}`);
        const label = document.getElementById(`status-label-${id}`);
        const originalHtml = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch(`{{ url('teachers') }}/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                if (data.status === 'active') {
                    btn.className = 'btn btn-xs rounded-pill px-2 py-1 border btn-outline-success';
                    btn.innerHTML = `<i class="fa-solid fa-check me-1"></i><span id="status-label-${id}">${data.label}</span>`;
                } else {
                    btn.className = 'btn btn-xs rounded-pill px-2 py-1 border btn-outline-secondary';
                    btn.innerHTML = `<i class="fa-solid fa-lock me-1"></i><span id="status-label-${id}">${data.label}</span>`;
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                btn.innerHTML = originalHtml;
                Swal.fire('Lỗi', data.message || 'Không thể đổi trạng thái', 'error');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            Swal.fire('Lỗi', 'Có lỗi kết nối máy chủ.', 'error');
        });
    }
</script>
@endpush
