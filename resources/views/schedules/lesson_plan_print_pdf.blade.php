<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giáo Án Buổi {{ $schedule->session_number }} - {{ $class->name }} - {{ $subject->code }}</title>
    
    <!-- Google Fonts: Times New Roman standard typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* CSS Reset & Quy chuẩn Typography Sư Phạm */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', 'Tinos', serif;
            font-size: 13pt;
            line-height: 1.45;
            color: #000000;
            margin: 0;
            padding: 0;
            background-color: #525659;
        }

        /* Khung hiển thị trang A4 */
        .pdf-page-wrapper {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 20mm 15mm 20mm 20mm;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.35);
            position: relative;
        }

        /* Thanh công cụ nổi (Floating Toolbar) - Không in ra */
        .pdf-action-bar {
            position: fixed;
            top: 15px;
            right: 25px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            border: 1px solid #cbd5e1;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 13px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-weight: 600;
            border-radius: 20px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-print {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }
        .btn-print:hover {
            background-color: #b91c1c;
            color: #ffffff;
        }

        .btn-word {
            background-color: #1b4d89;
            color: #ffffff;
            border-color: #1b4d89;
        }
        .btn-word:hover {
            background-color: #123866;
            color: #ffffff;
        }

        .btn-back {
            background-color: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }
        .btn-back:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        /* Bảng biểu chuẩn hành chính sư phạm */
        table.lesson-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 12pt;
        }

        table.lesson-table th, table.lesson-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            vertical-align: top;
        }

        table.lesson-table th {
            text-align: center;
            font-weight: bold;
            background-color: #f8fafc;
        }

        /* Định dạng Header Giáo án chuẩn */
        .header-top {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-bottom: 15px;
        }

        .header-top .left-col, .header-top .right-col {
            width: 48%;
        }

        .text-bold { font-weight: bold; }
        .text-italic { font-style: italic; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }

        .line-accent {
            width: 120px;
            height: 1px;
            background: #000;
            margin: 4px auto;
        }

        /* CSS Quy chuẩn khi in ấn (Print Media) */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print, .pdf-action-bar {
                display: none !important;
            }

            .pdf-page-wrapper {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 20mm 15mm 20mm 20mm;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar -->
    <div class="pdf-action-bar no-print">
        <span style="font-family: sans-serif; font-size: 12px; color: #475569; font-weight: bold;">
            Buổi #{{ $schedule->session_number }}: {{ $class->name }}
        </span>
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> In / Lưu PDF
        </button>
        <a href="{{ route('schedules.export_single_lesson_plan', ['schedule_id' => $schedule->id]) }}" class="btn-action btn-word">
            <i class="fa-solid fa-file-word"></i> Tải File Word
        </a>
        <a href="{{ route('schedules.preview', ['class_id' => $class->id, 'subject_id' => $subject->id]) }}" class="btn-action btn-back">
            <i class="fa-solid fa-arrow-left"></i> Quay Lại
        </a>
    </div>

    <!-- Khung Trang A4 Nội Dung Giáo Án -->
    <div class="pdf-page-wrapper">
        @if(!empty($customHtml))
            <!-- Trường hợp môn đã có file mẫu Word riêng (Đã điền tự động lớp, ngày và chữ ký) -->
            <div class="custom-template-content">
                {!! $customHtml !!}
            </div>
        @else
            <!-- Trường hợp sinh mẫu chuẩn theo quy định Tổng cục GDNN (Mẫu số 09c / 09a / 09b) -->
            @php
                $content = $schedule->subjectContent;
                $teachingDate = $schedule->teaching_date ? \Carbon\Carbon::parse($schedule->teaching_date) : \Carbon\Carbon::now();
                $signDate = $teachingDate->copy()->subDays(7);
                $formLabel = match($subject->effective_type) {
                    'theory'   => 'Mẫu số 09a - LÝ THUYẾT',
                    'practice' => 'Mẫu số 09b - THỰC HÀNH',
                    default    => 'Mẫu số 09c - TÍCH HỢP',
                };
            @endphp

            <div class="header-top">
                <div class="left-col">
                    <div style="font-size: 11pt;">KHOA CÔNG NGHỆ THÔNG TIN</div>
                    <div class="text-bold" style="font-size: 12pt;">BỘ MÔN MẠNG & HỆ THỐNG</div>
                    <div class="line-accent"></div>
                </div>
                <div class="right-col">
                    <div class="text-bold" style="font-size: 11pt;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                    <div class="text-bold" style="font-size: 11pt;">Độc lập - Tự do - Hạnh phúc</div>
                    <div class="line-accent"></div>
                </div>
            </div>

            <div class="text-center" style="margin-top: 15px; margin-bottom: 20px;">
                <div class="text-bold" style="font-size: 15pt; text-transform: uppercase;">
                    GIÁO ÁN SỐ: {{ sprintf('%02d', $schedule->session_number) }}
                </div>
                <div class="text-italic" style="font-size: 11pt;">({{ $formLabel }})</div>
            </div>

            <div style="margin-bottom: 12px;">
                <div><strong>Môn học / Mô đun:</strong> {{ $subject->name }} (Mã: {{ $subject->code }})</div>
                <div><strong>Thực hiện ngày:</strong> {{ $teachingDate->format('d/m/Y') }}  -  <strong>Lớp:</strong> {{ $class->name }}</div>
                <div><strong>Thời gian thực hiện:</strong> 4 giờ ({{ $schedule->session_shift }})</div>
                <div><strong>Tên bài học:</strong> {{ $content?->title_clean ?? 'Bài học số ' . $schedule->session_number }}</div>
            </div>

            <!-- I. MỤC TIÊU -->
            <div style="margin-top: 15px;">
                <div class="text-bold">I. MỤC TIÊU BÀI HỌC:</div>
                <div style="padding-left: 20px;">
                    <div><strong>1. Về kiến thức:</strong> {{ $content?->objective_knowledge ?: 'Nắm vững nguyên lý và phương pháp thực hiện bài học.' }}</div>
                    <div><strong>2. Về kỹ năng:</strong> {{ $content?->objective_skills ?: 'Thực hành thành thạo các thao tác kỹ thuật và giải quyết các bài tập thực tế.' }}</div>
                    <div><strong>3. Năng lực tự chủ và trách nhiệm:</strong> {{ $content?->objective_autonomy ?: 'Chủ động, nghiêm túc, có ý thức kỷ luật và an toàn thông tin.' }}</div>
                </div>
            </div>

            <!-- II. ĐỒ DÙNG & TRANG THIẾT BỊ -->
            <div style="margin-top: 12px;">
                <div class="text-bold">II. ĐỒ DÙNG VÀ TRANG THIẾT BỊ DẠY HỌC:</div>
                <div style="padding-left: 20px;">
                    {{ $content?->teaching_equipment ?: 'Phòng máy tính, máy chiếu Projector, slide bài giảng, phần mềm thực hành, mạng Internet.' }}
                </div>
            </div>

            <!-- III. HÌNH THỨC TỔ CHỨC DẠY HỌC -->
            <div style="margin-top: 12px;">
                <div class="text-bold">III. HÌNH THỨC TỔ CHỨC DẠY HỌC:</div>
                <div style="padding-left: 20px;">
                    {{ $content?->teaching_form ?: 'Thuyết trình kết hợp hướng dẫn trực quan trên máy, thảo luận nhóm và hướng dẫn thực hành từng học viên.' }}
                </div>
            </div>

            <!-- IV. TIẾN TRÌNH DẠY HỌC -->
            <div style="margin-top: 15px;">
                <div class="text-bold">IV. TIẾN TRÌNH THỰC HIỆN BÀI GIẢNG:</div>
                <table class="lesson-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">TT</th>
                            <th style="width: 35%;">Nội dung giảng dạy</th>
                            <th style="width: 25%;">Hoạt động của giáo viên</th>
                            <th style="width: 25%;">Hoạt động của học viên</th>
                            <th style="width: 10%;">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td><strong>Ổn định lớp</strong>: Điểm danh, phổ biến nội quy</td>
                            <td>Điểm danh, kiểm tra sĩ số</td>
                            <td>Báo cáo sĩ số, ổn định chỗ ngồi</td>
                            <td class="text-center">5 phút</td>
                        </tr>
                        <tr>
                            <td class="text-center">2</td>
                            <td><strong>Dẫn nhập</strong>: {{ $content?->activity_lead_in ?: 'Nhắc lại kiến thức buổi trước, đặt vấn đề vào bài mới' }}</td>
                            <td>Thuyết trình, đặt câu hỏi gợi mở</td>
                            <td>Lắng nghe, trả lời câu hỏi</td>
                            <td class="text-center">15 phút</td>
                        </tr>
                        <tr>
                            <td class="text-center">3</td>
                            <td><strong>Nội dung trọng tâm</strong>:<br>{{ $content?->content ?: 'Triển khai chi tiết các mục kiến thức và kỹ năng' }}</td>
                            <td>Giảng giải, thao tác mẫu trên máy</td>
                            <td>Quan sát, ghi chép và thực hành theo hướng dẫn</td>
                            <td class="text-center">180 phút</td>
                        </tr>
                        <tr>
                            <td class="text-center">4</td>
                            <td><strong>Củng cố kiến thức</strong>: {{ $content?->activity_reinforce ?: 'Tổng kết các điểm then chốt' }}</td>
                            <td>Nhận xét, đánh giá kết quả thực hành</td>
                            <td>Lắng nghe, tự đánh giá và rút kinh nghiệm</td>
                            <td class="text-center">25 phút</td>
                        </tr>
                        <tr>
                            <td class="text-center">5</td>
                            <td><strong>Hướng dẫn tự học</strong>: {{ $content?->activity_self_study ?: 'Bài tập về nhà và tài liệu nghiên cứu' }}</td>
                            <td>Giao bài tập, hướng dẫn tài liệu</td>
                            <td>Ghi chép yêu cầu tự học</td>
                            <td class="text-center">15 phút</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- V. RÚT KINH NGHIỆM -->
            <div style="margin-top: 15px;">
                <div class="text-bold">V. RÚT KINH NGHIỆM TỔ CHỨC THỰC HIỆN:</div>
                <div style="padding-left: 20px; font-style: italic;">
                    {{ $content?->experience_note ?: 'Học viên tham gia đầy đủ, thực hiện tốt các yêu cầu bài giảng; cần tăng cường thời lượng rèn luyện kỹ năng thực hành.' }}
                </div>
            </div>

            <!-- KÝ DUYỆT CUỐI BÀI -->
            <div style="margin-top: 35px; display: flex; justify-content: space-between; text-align: center;">
                <div style="width: 45%;">
                    <div class="text-bold">TRƯỞNG KHOA</div>
                    <div style="font-size: 11pt; font-style: italic;">(Ký duyệt)</div>
                    <div style="margin-top: 60px;" class="text-bold">{{ $headOfDept }}</div>
                </div>
                <div style="width: 45%;">
                    <div class="text-italic" style="font-size: 11pt;">
                        Ngày {{ $signDate->format('d') }} tháng {{ $signDate->format('m') }} năm {{ $signDate->format('Y') }}.
                    </div>
                    <div class="text-bold">GIÁO VIÊN GIẢNG DẠY</div>
                    <div style="font-size: 11pt; font-style: italic;">(Ký và ghi rõ họ tên)</div>
                    <div style="margin-top: 60px;" class="text-bold">{{ mb_strtoupper($teacherName, 'UTF-8') }}</div>
                </div>
            </div>
        @endif
    </div>

    @if($autoPrint)
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
    @endif

</body>
</html>
