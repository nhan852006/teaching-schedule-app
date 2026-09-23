<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kế hoạch giảng dạy: {{ $class->name }} - {{ $subject->name }}</title>
    
    <!-- Bootstrap 5 CSS & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card-custom { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: none; }
        .table th { background-color: #f1f4f8; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; color: #495057; }
        .badge-pending { background-color: #ffc107; color: #212529; }
        .badge-synced { background-color: #198754; color: #ffffff; }
        .badge-modified { background-color: #0dcaf0; color: #212529; }
        .table-input { min-width: 145px; border-radius: 6px; }
        .table-select { min-width: 105px; border-radius: 6px; }
        .saving-indicator { font-size: 0.75rem; display: none; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-lg-5">
    <!-- Header & Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('schedules.index') }}" class="text-decoration-none">Trang chủ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Lịch giảng chi tiết</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-chalkboard-user text-primary me-2"></i>
                Lớp: <span class="text-primary">{{ $class->name }}</span> | Môn: <span class="text-success">{{ $subject->name }}</span> ({{ $subject->code }})
            </h3>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <!-- Nút Đồng bộ Google Calendar -->
            <button id="btnSyncCalendar" class="btn btn-outline-primary px-3 shadow-sm" onclick="syncCalendar()">
                <i class="fa-brands fa-google text-danger me-1"></i> Đồng bộ Google Calendar
            </button>

            <!-- Nút Xuất Sổ tay Word -->
            <a href="{{ route('schedules.export_word', ['class_id' => $class->id, 'subject_id' => $subject->id]) }}" 
               class="btn btn-primary px-3 shadow-sm">
                <i class="fa-solid fa-file-word me-1"></i> Xuất Sổ tay Word
            </a>
        </div>
    </div>

    <!-- Main Content Table Card -->
    <div class="card card-custom p-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold text-secondary">
                <i class="fa-solid fa-list-check me-2"></i>Chi tiết từng buổi học & Đồng bộ
            </h5>
            <span class="text-muted small">
                Tổng số buổi: <strong>{{ $schedules->count() }}</strong>
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead>
                    <tr class="text-center align-middle">
                        <th style="width: 70px;">Buổi</th>
                        <th style="width: 170px;">Ngày dạy</th>
                        <th style="width: 130px;">Ca dạy</th>
                        <th>Nội dung giảng dạy</th>
                        <th style="width: 80px;">Số tiết LT</th>
                        <th style="width: 80px;">Số tiết TH</th>
                        <th style="width: 140px;">Trạng thái</th>
                        <th style="width: 100px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $item)
                        @php
                            $content = $item->subjectContent;
                        @endphp
                        <tr id="row-{{ $item->id }}">
                            <td class="text-center fw-bold text-secondary">
                                #{{ $item->session_number }}
                            </td>

                            <!-- Input Ngày dạy -->
                            <td>
                                <input type="date" 
                                       class="form-control form-control-sm table-input" 
                                       id="date-{{ $item->id }}" 
                                       value="{{ $item->teaching_date ? $item->teaching_date->format('Y-m-d') : '' }}"
                                       onchange="markRowAsDirty({{ $item->id }})">
                            </td>

                            <!-- Select Ca dạy -->
                            <td>
                                <select class="form-select form-select-sm table-select" 
                                        id="shift-{{ $item->id }}"
                                        onchange="markRowAsDirty({{ $item->id }})">
                                    <option value="Sáng" {{ $item->session_shift === 'Sáng' ? 'selected' : '' }}>Sáng</option>
                                    <option value="Chiều" {{ $item->session_shift === 'Chiều' ? 'selected' : '' }}>Chiều</option>
                                </select>
                            </td>

                            <!-- Nội dung bài học -->
                            <td>
                                <div class="text-wrap" style="max-width: 500px;">
                                    {{ $content->content ?? 'Chưa cập nhật nội dung cho buổi này' }}
                                </div>
                            </td>

                            <td class="text-center">{{ $content->theory_time ?? 0 }}</td>
                            <td class="text-center">{{ $content->practice_time ?? 0 }}</td>

                            <!-- Trạng thái Đồng bộ -->
                            <td class="text-center" id="status-container-{{ $item->id }}">
                                @if($item->sync_status === 'synced')
                                    <span class="badge badge-synced px-2 py-1"><i class="fa-solid fa-check me-1"></i>synced</span>
                                @elseif($item->sync_status === 'modified')
                                    <span class="badge badge-modified px-2 py-1"><i class="fa-solid fa-pen me-1"></i>modified</span>
                                @else
                                    <span class="badge badge-pending px-2 py-1"><i class="fa-regular fa-clock me-1"></i>pending</span>
                                @endif
                            </td>

                            <!-- Button lưu trực tiếp nếu cần -->
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-secondary" 
                                        id="btn-save-{{ $item->id }}"
                                        title="Lưu thay đổi dòng này"
                                        onclick="saveRowData({{ $item->id }})">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                                <div class="saving-indicator text-muted" id="saving-{{ $item->id }}">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Chưa có dữ liệu lịch dạy cho lớp và môn học này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript AJAX Logic -->
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

        fetch(`/schedules/${scheduleId}/update-inline`, {
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
                    badgeHtml = '<span class="badge badge-synced px-2 py-1"><i class="fa-solid fa-check me-1"></i>synced</span>';
                } else if (data.sync_status === 'modified') {
                    badgeHtml = '<span class="badge badge-modified px-2 py-1"><i class="fa-solid fa-pen me-1"></i>modified</span>';
                } else {
                    badgeHtml = '<span class="badge badge-pending px-2 py-1"><i class="fa-regular fa-clock me-1"></i>pending</span>';
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
        btnSync.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang đồng bộ...';

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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
