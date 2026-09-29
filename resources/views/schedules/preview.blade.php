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
                                            title="Lưu thay đổi dòng này"
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
</script>
@endpush
