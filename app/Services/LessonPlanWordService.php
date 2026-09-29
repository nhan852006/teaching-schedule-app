<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\IOFactory;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\SubjectContent;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Exception;

class LessonPlanWordService
{
    /**
     * Khởi tạo cấu hình thư mục tạm cho PhpWord
     */
    protected function initTempDir(): string
    {
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        @chmod($tempDir, 0777);

        \PhpOffice\PhpWord\Settings::setTempDir($tempDir);

        return $tempDir;
    }

    /**
     * Xuất trọn bộ Sổ Giáo Án của môn học cho lớp (Trang bìa + tất cả các buổi)
     */
    public function exportBooklet(Classes $class, Subject $subject, Collection $schedules, array $options = []): string
    {
        $tempDir = $this->initTempDir();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $type = $subject->effective_type; // 'integrated', 'theory', 'practice'
        $teacher = $subject->teacher;
        $teacherName = $options['giang_vien'] ?? ($teacher?->name ?? 'Hoàng Văn Nhân');

        $firstSession = $schedules->first();
        $firstDate = $firstSession?->teaching_date;
        $firstCarbon = $firstDate ? Carbon::parse($firstDate) : Carbon::now();
        $year = $firstCarbon->year;
        $academicYear = $options['nam_hoc'] ?? "{$year}-" . ($year + 1);

        // 1. Tạo Trang Bìa
        $this->renderCoverPage($phpWord, $class, $subject, $teacherName, $academicYear, $year, $type);

        // 2. Tạo từng Giáo án cho từng buổi học
        $prevLessonTitle = 'Không';
        foreach ($schedules as $index => $schedule) {
            $content = $schedule->subjectContent;
            $this->renderSingleLessonSection($phpWord, $schedule, $content, $subject, $class, $type, $prevLessonTitle, $teacherName);
            $prevLessonTitle = $content ? $content->title_clean : ('Bài học số ' . $schedule->session_number);
        }

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name);
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code);
        $fileName = "So_Giao_An_{$subject->form_code}_{$cleanClassName}_{$cleanSubjectCode}_" . time() . ".docx";
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        $oldErrorLevel = error_reporting();
        error_reporting($oldErrorLevel & ~E_NOTICE);
        try {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($outputPath);
        } finally {
            error_reporting($oldErrorLevel);
        }

        return $outputPath;
    }

    /**
     * Xuất lẻ giáo án của 1 buổi học cụ thể
     */
    public function exportSinglePlan(Schedule $schedule, array $options = []): string
    {
        $tempDir = $this->initTempDir();

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $subject = $schedule->subject;
        $class = $schedule->class;
        $type = $subject ? $subject->effective_type : 'integrated';
        $teacherName = $options['giang_vien'] ?? ($schedule->teacher?->name ?? ($subject?->teacher?->name ?? 'Hoàng Văn Nhân'));

        // Tìm bài học trước
        $prevSchedule = Schedule::where('class_id', $schedule->class_id)
            ->where('subject_id', $schedule->subject_id)
            ->where('session_number', '<', $schedule->session_number)
            ->orderBy('session_number', 'desc')
            ->first();
        $prevTitle = $prevSchedule?->subjectContent?->title_clean ?? 'Không';

        $content = $schedule->subjectContent;
        $this->renderSingleLessonSection($phpWord, $schedule, $content, $subject, $class, $type, $prevTitle, $teacherName);

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class?->name ?? 'Lop');
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject?->code ?? 'Mon');
        $fileName = "Giao_An_So_{$schedule->session_number}_{$cleanClassName}_{$cleanSubjectCode}_" . time() . ".docx";
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        $oldErrorLevel = error_reporting();
        error_reporting($oldErrorLevel & ~E_NOTICE);
        try {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($outputPath);
        } finally {
            error_reporting($oldErrorLevel);
        }

        return $outputPath;
    }

    /**
     * Vẽ Trang Bìa Sổ Giáo Án
     */
    protected function renderCoverPage(PhpWord $phpWord, Classes $class, Subject $subject, string $teacherName, string $academicYear, int $year, string $type): void
    {
        $cover = $phpWord->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2.5),
            'marginLeft'   => Converter::cmToTwip(2.5),
            'marginRight'  => Converter::cmToTwip(2.5),
        ]);

        $typeTitle = match ($type) {
            'theory'   => 'LÝ THUYẾT',
            'practice' => 'THỰC HÀNH',
            default    => 'TÍCH HỢP',
        };

        $formCode = match ($type) {
            'theory'   => 'Mẫu số 9a',
            'practice' => 'Mẫu số 9b',
            default    => 'Mẫu số 9c',
        };

        $cover->addText($formCode, ['size' => 11, 'bold' => true], ['alignment' => Jc::RIGHT]);
        $cover->addText('UBND THÀNH PHỐ ĐỒNG NAI', ['size' => 12, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $cover->addText('TRƯỜNG CAO ĐẲNG KỸ THUẬT - CÔNG NGHỆ ĐỒNG NAI', ['size' => 12, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 30]);
        $cover->addText('---------------------------------', ['size' => 11], ['alignment' => Jc::CENTER, 'spaceAfter' => 150]);

        $cover->addTextBreak(3);
        $cover->addText('SỔ GIÁO ÁN', ['size' => 26, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        $cover->addText($typeTitle, ['size' => 22, 'bold' => true, 'color' => '1F497D'], ['alignment' => Jc::CENTER, 'spaceAfter' => 80]);
        $cover->addText('(Ban hành kèm theo Quyết định:......../QĐ-CĐKTCN ngày …………………..)', ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        $cover->addTextBreak(4);

        $tableStyle = ['alignment' => JcTable::CENTER, 'cellMargin' => 60];
        $metaTable = $cover->addTable($tableStyle);

        $row1 = $metaTable->addRow();
        $row1->addCell(2500)->addText('Môn học / Mô đun:', ['size' => 12, 'bold' => true]);
        $row1->addCell(6000)->addText($subject->name, ['size' => 12, 'bold' => true]);

        $row2 = $metaTable->addRow();
        $row2->addCell(2500)->addText('Lớp:', ['size' => 12, 'bold' => true]);
        $row2->addCell(6000)->addText($class->name, ['size' => 12]);

        $row3 = $metaTable->addRow();
        $row3->addCell(2500)->addText('Giảng viên:', ['size' => 12, 'bold' => true]);
        $row3->addCell(6000)->addText($teacherName, ['size' => 12]);

        $row4 = $metaTable->addRow();
        $row4->addCell(2500)->addText('Năm học:', ['size' => 12, 'bold' => true]);
        $row4->addCell(6000)->addText($academicYear, ['size' => 12]);

        $cover->addTextBreak(5);
        $cover->addText("Đồng Nai, năm {$year}", ['size' => 11, 'italic' => true], ['alignment' => Jc::CENTER]);
    }

    /**
     * Vẽ Giáo Án cho một buổi học cụ thể
     */
    protected function renderSingleLessonSection(
        PhpWord $phpWord,
        Schedule $schedule,
        ?SubjectContent $content,
        ?Subject $subject,
        ?Classes $class,
        string $type,
        string $prevLessonTitle,
        string $teacherName
    ): void {
        $section = $phpWord->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => Converter::cmToTwip(1.5),
            'marginBottom' => Converter::cmToTwip(1.5),
            'marginLeft'   => Converter::cmToTwip(2.0),
            'marginRight'  => Converter::cmToTwip(1.5),
        ]);

        $formCode = match ($type) {
            'theory'   => 'Mẫu số 9a',
            'practice' => 'Mẫu số 9b',
            default    => 'Mẫu số 9c',
        };

        // Header nhỏ góc phải
        $section->addText($formCode, ['size' => 9.5, 'italic' => true, 'bold' => true], ['alignment' => Jc::RIGHT, 'spaceAfter' => 40]);

        // BẢNG TIÊU ĐỀ ĐẦU GIÁO ÁN
        $tHead = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50, 'alignment' => JcTable::CENTER]);
        $rHead = $tHead->addRow();

        $cLeft = $rHead->addCell(3500, ['valign' => 'center']);
        $cLeft->addText("GIÁO ÁN SỐ: " . sprintf('%02d', $schedule->session_number), ['size' => 12, 'bold' => true], ['alignment' => Jc::CENTER]);

        $cRight = $rHead->addCell(5500, ['valign' => 'center']);
        
        $lt = $content?->theory_time ?? 0;
        $th = $content?->practice_time ?? 0;
        $kt = $content?->test_time ?? 0;
        $totalHours = $lt + $th + $kt;

        $timeStr = "{$totalHours} giờ";
        if ($type === 'integrated') {
            $timeStr .= " (LT: {$lt}, TH: {$th}, KT: {$kt})";
        } elseif ($type === 'theory') {
            $timeStr .= " (Lý thuyết: {$lt} tiết)";
        } else {
            $timeStr .= " (Thực hành: {$th} tiết)";
        }

        $formattedDate = $schedule->teaching_date ? Carbon::parse($schedule->teaching_date)->format('\n\gà\y d \t\h\á\n\g m \n\ă\m Y') : 'ngày......tháng......năm......';

        $cRight->addText("Thời gian thực hiện: " . $timeStr, ['size' => 10], ['spaceAfter' => 20]);
        $cRight->addText("Tên bài học trước: " . $prevLessonTitle, ['size' => 10], ['spaceAfter' => 20]);
        $cRight->addText("Thực hiện: " . $formattedDate, ['size' => 10, 'italic' => true], ['spaceAfter' => 0]);

        $section->addTextBreak(1, ['size' => 4]);

        // TÊN BÀI HỌC
        $lessonTitle = $content ? $content->content : ('Buổi học số ' . $schedule->session_number);
        $section->addText('TÊN BÀI: ' . mb_strtoupper($lessonTitle, 'UTF-8'), ['size' => 11, 'bold' => true], ['spaceAfter' => 40]);

        // MỤC TIÊU CỦA BÀI
        $section->addText('MỤC TIÊU CỦA BÀI:', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 20]);
        $section->addText('Sau khi học xong bài này người học có khả năng:', ['size' => 10, 'italic' => true], ['spaceAfter' => 20]);
        
        $knowledge = $content?->effective_knowledge ?? 'Nắm vững kiến thức trọng tâm của bài.';
        $skills = $content?->effective_skills ?? 'Thực hiện chính xác các thao tác kỹ năng.';
        $autonomy = $content?->effective_autonomy ?? 'Có tinh thần trách nhiệm, an toàn lao động.';

        $section->addText('- Kiến thức:', ['size' => 10, 'bold' => true], ['spaceAfter' => 10]);
        $this->addMultilineTextToSection($section, $knowledge, ['size' => 10], ['spaceAfter' => 20]);
        
        $section->addText('- Kỹ năng:', ['size' => 10, 'bold' => true], ['spaceAfter' => 10]);
        $this->addMultilineTextToSection($section, $skills, ['size' => 10], ['spaceAfter' => 20]);
        
        $section->addText('- Mức độ tự chủ và trách nhiệm:', ['size' => 10, 'bold' => true], ['spaceAfter' => 10]);
        $this->addMultilineTextToSection($section, $autonomy, ['size' => 10], ['spaceAfter' => 40]);

        // ĐỒ DÙNG VÀ TRANG THIẾT BỊ
        $equipment = $content?->effective_equipment ?? 'Phòng máy tính, máy chiếu, bảng, viết, Internet.';
        $section->addText('ĐỒ DÙNG VÀ TRANG THIẾT BỊ DẠY HỌC:', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 20]);
        $this->addMultilineTextToSection($section, $equipment, ['size' => 10], ['spaceAfter' => 40]);

        // HÌNH THỨC TỔ CHỨC DẠY HỌC (chỉ cho Mẫu 9b, 9c)
        if ($type !== 'theory') {
            $teachingForm = $content?->effective_teaching_form ?? 'Tập trung toàn lớp để hướng dẫn, kết hợp chia nhóm thực hành và kèm cặp cá nhân.';
            $section->addText('HÌNH THỨC TỔ CHỨC DẠY HỌC:', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 20]);
            $section->addText($teachingForm, ['size' => 10], ['spaceAfter' => 40]);
        }

        // I. ỔN ĐỊNH LỚP HỌC
        $section->addText('I. ỔN ĐỊNH LỚP HỌC: Thời gian: 05 phút', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 40]);

        // II. THỰC HIỆN BÀI HỌC
        $section->addText('II. THỰC HIỆN BÀI HỌC:', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 40]);

        // BẢNG TIẾN TRÌNH HOẠT ĐỘNG
        $tAct = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 40, 'alignment' => JcTable::CENTER]);

        // Header Hàng 1
        $rAct1 = $tAct->addRow(null, ['tblHeader' => true]);
        $rAct1->addCell(500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('TT', ['size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rAct1->addCell(2500, ['vMerge' => 'restart', 'valign' => 'center'])->addText('NỘI DUNG', ['size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rAct1->addCell(5000, ['gridSpan' => 2, 'valign' => 'center'])->addText('HOẠT ĐỘNG DẠY HỌC', ['size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rAct1->addCell(1000, ['vMerge' => 'restart', 'valign' => 'center'])->addText("THỜI\nGIAN", ['size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER]);

        // Header Hàng 2
        $rAct2 = $tAct->addRow(null, ['tblHeader' => true]);
        $rAct2->addCell(500, ['vMerge' => 'continue']);
        $rAct2->addCell(2500, ['vMerge' => 'continue']);
        $rAct2->addCell(2500, ['valign' => 'center'])->addText('HOẠT ĐỘNG CỦA GIÁO VIÊN', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rAct2->addCell(2500, ['valign' => 'center'])->addText('HOẠT ĐỘNG CỦA HỌC SINH', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rAct2->addCell(1000, ['vMerge' => 'continue']);

        // Xác định các bước hoạt động theo từng Mẫu 9a, 9b, 9c
        $activities = $this->buildActivitiesList($type, $content);

        foreach ($activities as $act) {
            $r = $tAct->addRow();
            $r->addCell(500, ['valign' => 'top'])->addText($act['tt'], ['size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER]);
            
            $cNoiDung = $r->addCell(2500, ['valign' => 'top']);
            $this->addMultilineTextToCell($cNoiDung, $act['noi_dung'], ['size' => 9.5, 'bold' => $act['bold'] ?? false]);

            $cGv = $r->addCell(2500, ['valign' => 'top']);
            $this->addMultilineTextToCell($cGv, $act['gv'], ['size' => 9]);

            $cHs = $r->addCell(2500, ['valign' => 'top']);
            $this->addMultilineTextToCell($cHs, $act['hs'], ['size' => 9]);

            $r->addCell(1000, ['valign' => 'top'])->addText($act['time'], ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        // TÀI LIỆU THAM KHẢO (Đặc thù Mẫu 9a)
        if ($type === 'theory') {
            $ref = $content?->effective_reference ?? 'Giáo trình môn học, tài liệu giảng dạy.';
            $section->addText('Nguồn tài liệu tham khảo: ' . $ref, ['size' => 9.5, 'italic' => true], ['spaceAfter' => 30]);
        }

        $section->addTextBreak(1, ['size' => 4]);

        // III. RÚT KINH NGHIỆM TỔ CHỨC THỰC HIỆN
        $expNote = $content?->experience_note ?? 'Lớp học tập nghiêm túc, nắm được kiến thức và thực hiện tốt yêu cầu bài học.';
        $section->addText('III. RÚT KINH NGHIỆM TỔ CHỨC THỰC HIỆN:', ['size' => 10.5, 'bold' => true], ['spaceAfter' => 20]);
        $section->addText($expNote, ['size' => 10, 'italic' => true], ['spaceAfter' => 60]);

        // BẢNG CHỮ KÝ (Khớp đúng mẫu: TRƯỞNG KHOA - THÁI QUỐC THẮNG | GIÁO VIÊN - HOÀNG VĂN NHÂN)
        $tSign = $section->addTable(['alignment' => JcTable::CENTER, 'cellMargin' => 40]);
        $rDate = $tSign->addRow();
        $rDate->addCell(4500);
        $cDate = $rDate->addCell(4500);
        $dateSignature = $schedule->teaching_date 
            ? Carbon::parse($schedule->teaching_date)->format('\N\gà\y d \t\h\á\n\g m \n\ă\m Y.')
            : 'Ngày … tháng … năm 2026.';
        $cDate->addText($dateSignature, ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER]);

        $rSignTitle = $tSign->addRow();
        $cK = $rSignTitle->addCell(4500);
        $cK->addText("TRƯỞNG KHOA", ['size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER]);
        $cK->addText("(Ký duyệt)", ['size' => 9, 'italic' => true], ['alignment' => Jc::CENTER]);

        $cGv = $rSignTitle->addCell(4500);
        $cGv->addText("Chữ ký giáo viên", ['size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER]);

        $rSpace = $tSign->addRow();
        $rSpace->addCell(4500)->addTextBreak(3);
        $rSpace->addCell(4500)->addTextBreak(3);

        $rName = $tSign->addRow();
        $cDeanName = $rName->addCell(4500);
        $cDeanName->addText('THÁI QUỐC THẮNG', ['size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER]);
        $cTeacherName = $rName->addCell(4500);
        $cTeacherName->addText(mb_strtoupper($teacherName, 'UTF-8'), ['size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER]);
    }

    /**
     * Helper thêm văn bản nhiều dòng vào ô bảng
     */
    protected function addMultilineTextToCell($cell, string $text, array $fontStyle = [], array $paraStyle = []): void
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cell->addText($trimmed, $fontStyle, array_merge(['spaceAfter' => 20], $paraStyle));
            } elseif ($idx === 0) {
                $cell->addText('', $fontStyle, $paraStyle);
            }
        }
    }

    /**
     * Helper thêm văn bản nhiều dòng vào section
     */
    protected function addMultilineTextToSection($section, string $text, array $fontStyle = [], array $paraStyle = []): void
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $section->addText($trimmed, $fontStyle, array_merge(['spaceAfter' => 20], $paraStyle));
            }
        }
    }

    /**
     * Xây dựng danh sách các bước hoạt động chuẩn theo biểu mẫu 9a, 9b, 9c
     */
    protected function buildActivitiesList(string $type, ?SubjectContent $content): array
    {
        $leadIn = $content?->effective_lead_in ?? 'Ổn định lớp, điểm danh; Gợi mở vấn đề, tạo tâm thế học tập.';
        $mainAct = $content?->effective_main ?? 'Giáo viên trình bày bài học, làm mẫu; Học sinh chú ý lắng nghe, thực hành.';
        $reinforce = $content?->effective_reinforce ?? 'Tổng kết bài học, nhận xét kết quả và lưu ý sai sót.';
        $selfStudy = $content?->effective_self_study ?? 'Ôn tập lại kiến thức và chuẩn bị bài học tiếp theo.';

        if ($type === 'theory') {
            // Mẫu 9a: Lý thuyết
            return [
                [
                    'tt'       => '1',
                    'noi_dung' => "Dẫn nhập\n(Gợi mở, trao đổi phương pháp học, tạo tâm thế tích cực...)",
                    'gv'       => $leadIn,
                    'hs'       => 'Lắng nghe, trả lời câu hỏi và định hướng bài học.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '2',
                    'noi_dung' => "Giảng bài mới\n(Nội dung lý thuyết trọng tâm và ví dụ minh họa)",
                    'gv'       => $mainAct,
                    'hs'       => 'Theo dõi bài giảng, ghi chép và đặt câu hỏi tương tác.',
                    'time'     => '150 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '3',
                    'noi_dung' => "Củng cố kiến thức và kết thúc bài\n(Đánh giá mức độ tiếp thu)",
                    'gv'       => $reinforce,
                    'hs'       => 'Hệ thống hóa kiến thức trọng tâm.',
                    'time'     => '15 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '4',
                    'noi_dung' => "Hướng dẫn tự học\n(Bài tập và đọc tài liệu)",
                    'gv'       => $selfStudy,
                    'hs'       => 'Ghi chép yêu cầu tự học về nhà.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
            ];
        } elseif ($type === 'practice') {
            // Mẫu 9b: Thực hành
            return [
                [
                    'tt'       => '1',
                    'noi_dung' => "Dẫn nhập\n(Gợi mở, tạo tâm thế tích cực của người học...)",
                    'gv'       => $leadIn,
                    'hs'       => 'Lắng nghe, chuẩn bị dụng cụ, thiết bị học tập.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '2',
                    'noi_dung' => "Hướng dẫn ban đầu\n(Hướng dẫn quy trình công nghệ; Phân công vị trí luyện tập)",
                    'gv'       => 'Thị phạm thao tác mẫu, giải thích các bước tiêu chuẩn và an toàn.',
                    'hs'       => 'Quan sát thao tác mẫu, ghi nhớ các bước quy trình.',
                    'time'     => '25 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '3',
                    'noi_dung' => "Hướng dẫn thường xuyên\n(Hướng dẫn học sinh rèn luyện để hình thành kỹ năng)",
                    'gv'       => $mainAct . ' Quan sát, nhắc nhở an toàn và uốn nắn thao tác.',
                    'hs'       => 'Thực hiện bài tập thực hành theo nhóm hoặc cá nhân trên máy.',
                    'time'     => '125 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '4',
                    'noi_dung' => "Hướng dẫn kết thúc\n(Nhận xét kết quả rèn luyện, lưu ý sai sót và cách khắc phục)",
                    'gv'       => $reinforce,
                    'hs'       => 'Đánh giá sản phẩm/kết quả thực hành, rút kinh nghiệm.',
                    'time'     => '15 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '5',
                    'noi_dung' => "Hướng dẫn tự rèn luyện\n(Rèn luyện thêm ngoài giờ)",
                    'gv'       => $selfStudy,
                    'hs'       => 'Thu dọn vệ sinh vị trí thực hành, tắt thiết bị đúng quy trình.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
            ];
        } else {
            // Mẫu 9c: Tích hợp
            return [
                [
                    'tt'       => '1',
                    'noi_dung' => "Dẫn nhập\n(Gợi mở, trao đổi phương pháp học, tạo tâm thế tích cực...)",
                    'gv'       => $leadIn,
                    'hs'       => 'Lắng nghe, tương tác và định hướng nhiệm vụ học tập.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '2',
                    'noi_dung' => "Giới thiệu chủ đề\n(Nội dung chủ đề cần giải quyết: tiêu chuẩn kiến thức, kỹ năng)",
                    'gv'       => 'Trình bày lý thuyết liên quan và phân tích quy trình thực hiện mẫu.',
                    'hs'       => 'Tiếp thu lý thuyết và quan sát trực quan thao tác mẫu.',
                    'time'     => '30 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '3',
                    'noi_dung' => "Giải quyết vấn đề\n(Hướng dẫn học sinh rèn luyện để hình thành phát triển năng lực)",
                    'gv'       => $mainAct . ' Theo dõi, hướng dẫn cá biệt và kiểm tra quy trình.',
                    'hs'       => 'Chủ động áp dụng kiến thức vào thực hành trực tiếp trên máy tính.',
                    'time'     => '115 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '4',
                    'noi_dung' => "Kết thúc vấn đề\n(- Củng cố kiến thức\n- Củng cố kỹ năng rèn luyện)",
                    'gv'       => $reinforce,
                    'hs'       => 'Trình bày kết quả, nhận xét chéo và tiếp thu đánh giá.',
                    'time'     => '20 phút',
                    'bold'     => true,
                ],
                [
                    'tt'       => '5',
                    'noi_dung' => "Hướng dẫn tự học\n(Bài tập củng cố và định hướng bài mới)",
                    'gv'       => $selfStudy,
                    'hs'       => 'Ghi chép bài tập, tắt máy và vệ sinh phòng thực hành.',
                    'time'     => '05 phút',
                    'bold'     => true,
                ],
            ];
        }
    }
}
