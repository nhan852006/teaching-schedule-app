@extends('layouts.app')

@section('title', 'Quản lý Danh mục Môn học & Giáo trình')
@section('meta_description', 'Quản lý danh sách môn học, cấu hình số buổi, loại giáo án (Tích hợp, Lý thuyết, Thực hành) và cập nhật nội dung bài giảng.')

@section('header_actions')
    <!-- Teacher Switcher Form -->
    <form action="{{ route('schedules.teacher.switch') }}" method="POST" class="d-flex align-items-center gap-2">
        @csrf
        <label for="teacherSelect" class="visually-hidden">Chọn giảng viên</label>
        <div class="d-none d-md-flex align-items-center text-muted small me-1">
            <i class="fa-solid fa-chalkboard-user me-1 text-primary"></i>
            <span>Giảng viên:</span>
        </div>
        <select id="teacherSelect" name="teacher_id" class="form-select form-select-sm fw-bold border-secondary-subtle" style="min-width: 210px;" onchange="this.form.submit()">
            @foreach($allTeachers as $t)
                <option value="{{ $t->id }}" {{ $t->id === $currentTeacher->id ? 'selected' : '' }}>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
    </form>

    <button type="button" class="btn btn-sm btn-academic-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreateSubject">
        <i class="fa-solid fa-plus me-1"></i> Thêm Môn Học Mới
    </button>
@endsection

@section('content')
<div class="container-fluid px-lg-5 py-4">

    <!-- Breadcrumb Navigation -->
    <nav aria-label="Đường dẫn phân cấp học vụ" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="{{ route('schedules.index') }}" class="text-decoration-none text-muted">Trang chủ Lịch dạy</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Danh mục Môn học</li>
        </ol>
    </nav>

    <!-- Page Title & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">
                <i class="fa-solid fa-book-bookmark text-primary me-2"></i>Quản lý Danh mục Môn học
            </h1>
            <p class="text-muted small mb-0">
                Quản lý các môn học đã thêm, thiết lập số buổi học, loại giáo án và chỉnh sửa nội dung bài giảng chi tiết
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2">
                Tổng cộng: <strong>{{ $totalSubjects }} môn học</strong>
            </span>
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

    <!-- Thống kê loại môn học KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Tổng số môn học</div>
                <div class="fs-4 fw-bold text-dark mt-1">{{ $totalSubjects }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                <div class="text-primary small mt-1" style="font-size: 0.75rem;">Đã cấu hình trên hệ thống</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Môn Tích hợp (Mẫu 9c)</div>
                <div class="fs-4 fw-bold text-info-emphasis mt-1">{{ $integratedCount }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">Lý thuyết kết hợp thực hành</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Môn Thực hành (Mẫu 9b)</div>
                <div class="fs-4 fw-bold text-success mt-1">{{ $practiceCount }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">Chuyên thực hành phòng máy</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Môn Lý thuyết (Mẫu 9a)</div>
                <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ $theoryCount }} <span class="fs-6 fw-normal text-muted">môn</span></div>
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">Giảng dạy lý thuyết chuyên sâu</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
        <form method="GET" action="{{ route('subjects.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm theo mã môn hoặc tên môn học..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Tất cả loại môn học --</option>
                    <option value="integrated" {{ $typeFilter === 'integrated' ? 'selected' : '' }}>Tích hợp (Mẫu 9c)</option>
                    <option value="theory" {{ $typeFilter === 'theory' ? 'selected' : '' }}>Lý thuyết (Mẫu 9a)</option>
                    <option value="practice" {{ $typeFilter === 'practice' ? 'selected' : '' }}>Thực hành (Mẫu 9b)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-academic-outline w-100">
                    <i class="fa-solid fa-filter me-1"></i> Lọc dữ liệu
                </button>
            </div>
            @if($search || $typeFilter)
                <div class="col-md-2">
                    <a href="{{ route('subjects.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="fa-solid fa-rotate-left me-1"></i> Đặt lại
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Table of Subjects -->
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table-academic border mb-0">
                <thead>
                    <tr class="text-center align-middle">
                        <th style="width: 55px;">STT</th>
                        <th style="width: 140px;">Mã môn học</th>
                        <th class="text-start">Tên môn học / Mô-đun</th>
                        <th style="width: 170px;">Loại giáo án</th>
                        <th style="width: 110px;">Quy mô</th>
                        <th style="width: 150px;">Mẫu Word (.docx)</th>
                        <th style="width: 120px;">Lớp áp dụng</th>
                        <th style="width: 190px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $index => $sub)
                        @php
                            $tplCount = $sub->countUploadedTemplates();
                            $totalSess = $sub->total_sessions ?: $sub->contents_count;
                            $classesCount = $sub->classes->count();
                        @endphp
                        <tr>
                            <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1 fs-6">
                                    {{ $sub->code }}
                                </span>
                            </td>
                            <td class="text-start">
                                <a href="{{ route('subjects.show', $sub->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                    {{ $sub->name }}
                                </a>
                                <div class="text-muted small" style="font-size: 0.78rem;">
                                    Giảng viên phụ trách: {{ $sub->teacher?->name ?? 'Chưa phân công' }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($sub->effective_type === 'integrated')
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                        <i class="fa-solid fa-layer-group me-1"></i> Tích hợp (9c)
                                    </span>
                                @elseif($sub->effective_type === 'practice')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa-solid fa-laptop-code me-1"></i> Thực hành (9b)
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="fa-solid fa-chalkboard me-1"></i> Lý thuyết (9a)
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark">{{ $totalSess }}</span> <span class="text-muted small">buổi</span>
                            </td>
                            <td class="text-center">
                                @if($tplCount > 0)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="fa-solid fa-file-circle-check me-1"></i> {{ $tplCount }}/{{ $totalSess }} file
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted border px-2 py-1">
                                        <i class="fa-regular fa-file me-1"></i> 0/{{ $totalSess }} file
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($classesCount > 0)
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fa-solid fa-users me-1 text-secondary"></i> {{ $classesCount }} lớp
                                    </span>
                                @else
                                    <span class="text-muted small">Chưa xếp lịch</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <!-- Nút Quản lý nội dung buổi học -->
                                    <a href="{{ route('subjects.show', $sub->id) }}" class="btn btn-academic-primary btn-sm" title="Quản lý chi tiết nội dung các buổi học">
                                        <i class="fa-solid fa-list-check me-1"></i> Nội dung
                                    </a>
                                    <!-- Nút Sửa thông tin môn -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            title="Sửa thông tin môn học"
                                            onclick="openEditSubjectModal({{ $sub->id }}, '{{ addslashes($sub->code) }}', '{{ addslashes($sub->name) }}', '{{ $sub->effective_type }}')">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <!-- Nút Xoá môn -->
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            title="Xoá môn học này"
                                            onclick="confirmDeleteSubject({{ $sub->id }}, '{{ addslashes($sub->code) }}', '{{ addslashes($sub->name) }}', {{ $sub->schedules_count }})">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-2 text-secondary opacity-50 mb-2 d-block"></i>
                                <span>Chưa có môn học nào phù hợp với điều kiện tìm kiếm.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Thêm Môn Học Mới -->
<div class="modal fade" id="modalCreateSubject" tabindex="-1" aria-labelledby="modalCreateSubjectTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 8px;">
            <div class="modal-header bg-academic-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold fs-6" id="modalCreateSubjectTitle">
                    <i class="fa-solid fa-plus-circle me-2"></i>Thêm Môn Học Mới
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('subjects.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="new_code" class="form-label small fw-bold text-dark">Mã Môn Học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm text-uppercase font-monospace" id="new_code" name="code" placeholder="VD: CNTT-QT002, IT4505..." required>
                        <div class="form-text small">Mã định danh duy nhất cho môn học (viết hoa, không dấu).</div>
                    </div>
                    <div class="mb-3">
                        <label for="new_name" class="form-label small fw-bold text-dark">Tên Môn Học / Mô-đun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="new_name" name="name" placeholder="VD: Quản trị mạng Linux & Windows Server..." required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="new_subject_type" class="form-label small fw-bold text-dark">Loại Môn Học <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="new_subject_type" name="subject_type" required>
                                <option value="integrated" selected>Tích hợp (Mẫu 9c)</option>
                                <option value="practice">Thực hành (Mẫu 9b)</option>
                                <option value="theory">Lý thuyết (Mẫu 9a)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="new_total_sessions" class="form-label small fw-bold text-dark">Tổng Số Buổi Học <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm text-center" id="new_total_sessions" name="total_sessions" value="22" min="1" max="100" required>
                        </div>
                    </div>
                    <div class="alert alert-info border-info-subtle small py-2 px-3 mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Hệ thống sẽ tự động khởi tạo danh sách các buổi học tương ứng để Thầy có thể điền nội dung ngay sau khi tạo.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm">
                        <i class="fa-solid fa-check me-1"></i> Tạo Môn Học
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa Thông Tin Môn Học -->
<div class="modal fade" id="modalEditSubject" tabindex="-1" aria-labelledby="modalEditSubjectTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 8px;">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold fs-6 text-dark" id="modalEditSubjectTitle">
                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Chỉnh Sửa Thông Tin Môn Học
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditSubject" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_code" class="form-label small fw-bold text-dark">Mã Môn Học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm text-uppercase font-monospace" id="edit_code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label small fw-bold text-dark">Tên Môn Học / Mô-đun <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_subject_type" class="form-label small fw-bold text-dark">Loại Môn Học / Mẫu Giáo Án <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="edit_subject_type" name="subject_type" required>
                            <option value="integrated">Tích hợp (Mẫu số 9c)</option>
                            <option value="practice">Thực hành (Mẫu số 9b)</option>
                            <option value="theory">Lý thuyết (Mẫu số 9a)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-academic-primary px-3 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Form for Delete Subject -->
<form id="formDeleteSubject" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="force" id="deleteForceInput" value="0">
</form>

@endsection

@push('scripts')
<script>
    function openEditSubjectModal(id, code, name, type) {
        document.getElementById('edit_code').value = code;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_subject_type').value = type;

        const form = document.getElementById('formEditSubject');
        form.action = "{{ url('subjects') }}/" + id;

        const modal = new bootstrap.Modal(document.getElementById('modalEditSubject'));
        modal.show();
    }

    function confirmDeleteSubject(id, code, name, schedulesCount) {
        let warningText = 'Bạn có chắc chắn muốn xoá môn học ' + code + ' - ' + name + '? Hành động này sẽ xoá toàn bộ nội dung các buổi học và file mẫu liên quan.';
        if (schedulesCount > 0) {
            warningText = 'CẢNH BÁO: Môn học này đang có ' + schedulesCount + ' buổi học đã được xếp lịch dạy cho các lớp! Nếu bạn đồng ý xoá, toàn bộ lịch dạy của các lớp liên quan cũng sẽ bị xoá.';
        }

        Swal.fire({
            title: 'Xoá môn học ' + code + '?',
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý xoá môn này',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('formDeleteSubject');
                form.action = "{{ url('subjects') }}/" + id;
                document.getElementById('deleteForceInput').value = schedulesCount > 0 ? '1' : '0';
                form.submit();
            }
        });
    }
</script>
@endpush
