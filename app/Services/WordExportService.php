<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\IOFactory;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Exception;

class WordExportService
{
    /**
     * Xuất Kế hoạch giảng dạy Mẫu số 8 ra file .docx
     * Chuẩn font Unicode Times New Roman, chia 3 cột LT, TH, KT
     *
     * @param Classes $class
     * @param Subject $subject
     * @param Collection $schedules
     * @param array $options
     * @return string Đường dẫn file tạm được sinh ra
     */
    public function exportMau08(Classes $class, Subject $subject, Collection $schedules, array $options = []): string
    {
        $templatePath = storage_path('app/templates/mau_08_template.docx');

        // Tạo template chuẩn nếu chưa tồn tại
        if (!file_exists($templatePath)) {
            $this->ensureMau08TemplateExists($templatePath);
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // 1. Xác định thời gian & Năm học, Học kỳ
        $firstSession = $schedules->first();
        $firstDate = $firstSession?->teaching_date;
        $firstCarbon = $firstDate ? Carbon::parse($firstDate) : Carbon::now();
        $year = $firstCarbon->year;

        $academicYear = $options['nam_hoc'] ?? "{$year}-" . ($year + 1);
        $semester = $options['hoc_ky'] ?? ($firstCarbon->month >= 8 ? '1' : ($firstCarbon->month <= 5 ? '2' : '1'));
        $teacherName = $options['giang_vien'] ?? ($subject->teacher?->name ?? 'Hoàng Văn Nhân');
        $defaultEquipment = $options['thiet_bi'] ?? 'Phòng máy tính, máy chiếu, bảng, viết';

        // 2. Tính tổng số giờ LT, TH, KT
        $tongLt = 0;
        $tongTh = 0;
        $tongKt = 0;

        foreach ($schedules as $schedule) {
            $content = $schedule->subjectContent;
            if ($content) {
                $tongLt += (int)($content->theory_time ?? 0);
                $tongTh += (int)($content->practice_time ?? 0);
                $tongKt += (int)($content->test_time ?? 0);
            }
        }

        $soGioMon = $tongLt + $tongTh + $tongKt;
        $soGioTruoc = $options['so_gio_truoc'] ?? 0;
        $soGioKyNay = $options['so_gio_ky_nay'] ?? $soGioMon;
        $soGioConLai = $options['so_gio_con_lai'] ?? 0;

        // 3. Gán các biến Header & Metadata
        $templateProcessor->setValue('lop', htmlspecialchars($class->name, ENT_QUOTES, 'UTF-8'));
        $templateProcessor->setValue('nam', (string)$year);
        $templateProcessor->setValue('nam_hoc', htmlspecialchars($academicYear, ENT_QUOTES, 'UTF-8'));
        $templateProcessor->setValue('mon_hoc', htmlspecialchars($subject->name, ENT_QUOTES, 'UTF-8'));
        $templateProcessor->setValue('giang_vien', htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8'));
        $templateProcessor->setValue('hoc_ky', (string)$semester);

        $templateProcessor->setValue('so_gio_mon', (string)$soGioMon);
        $templateProcessor->setValue('tong_lt', (string)$tongLt);
        $templateProcessor->setValue('tong_th', (string)$tongTh);
        $templateProcessor->setValue('tong_kt', (string)$tongKt);
        $templateProcessor->setValue('so_gio_truoc', (string)$soGioTruoc);
        $templateProcessor->setValue('so_gio_ky_nay', (string)$soGioKyNay);
        $templateProcessor->setValue('so_gio_con_lai', (string)$soGioConLai);

        // 4. cloneRow Logic: Nhân bản các hàng trong bảng tiến độ giảng dạy
        $count = $schedules->count();
        if ($count > 0) {
            $templateProcessor->cloneRow('stt', $count);

            foreach ($schedules as $index => $schedule) {
                $i = $index + 1;
                $content = $schedule->subjectContent;

                $formattedDate = $schedule->teaching_date 
                    ? Carbon::parse($schedule->teaching_date)->format('d/m/Y') 
                    : '';

                $rawContent = $content?->content ?? ('Buổi ' . ($schedule->session_number ?? $i));
                // Chuyển ký tự xuống dòng thành ngắt dòng XML <w:br/> của Word
                $escapedContent = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8');
                $multilineContent = str_replace(["\r\n", "\r", "\n"], '</w:t><w:br/><w:t>', $escapedContent);

                $ltVal = ($content && $content->theory_time > 0) ? (string)$content->theory_time : '';
                $thVal = ($content && $content->practice_time > 0) ? (string)$content->practice_time : '';
                $ktVal = ($content && $content->test_time > 0) ? (string)$content->test_time : '';

                $noteVal = '';
                if ($content && $content->test_time > 0) {
                    $noteVal = 'Kiểm tra';
                }

                $templateProcessor->setValue("stt#{$i}", (string)($schedule->session_number ?? $i));
                $templateProcessor->setValue("noi_dung#{$i}", $multilineContent);
                $templateProcessor->setValue("lt#{$i}", $ltVal);
                $templateProcessor->setValue("th#{$i}", $thVal);
                $templateProcessor->setValue("kt#{$i}", $ktVal);
                $templateProcessor->setValue("ngay#{$i}", $formattedDate);
                $templateProcessor->setValue("thiet_bi#{$i}", htmlspecialchars($defaultEquipment, ENT_QUOTES, 'UTF-8'));
                $templateProcessor->setValue("ghi_chu#{$i}", htmlspecialchars($noteVal, ENT_QUOTES, 'UTF-8'));
            }
        } else {
            $templateProcessor->setValue('stt', '');
            $templateProcessor->setValue('noi_dung', '');
            $templateProcessor->setValue('lt', '');
            $templateProcessor->setValue('th', '');
            $templateProcessor->setValue('kt', '');
            $templateProcessor->setValue('ngay', '');
            $templateProcessor->setValue('thiet_bi', '');
            $templateProcessor->setValue('ghi_chu', '');
        }

        // 5. Lưu file tạm
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name);
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code);
        $fileName = "Ke_Hoach_Giang_Day_Mau_08_{$cleanClassName}_{$cleanSubjectCode}_" . time() . ".docx";
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        $templateProcessor->saveAs($outputPath);

        return $outputPath;
    }

    /**
     * Backward-compatible alias
     */
    public function exportHandbook(Classes $class, Subject $subject, Collection $schedules): string
    {
        return $this->exportMau08($class, $subject, $schedules);
    }

    /**
     * Tạo file mẫu Word Mẫu số 8 chuẩn Bộ GD / Trường CĐ KT-CN Đồng Nai
     * Sử dụng 100% font Times New Roman, cấu hình chia 3 cột LT, TH, KT
     */
    public function ensureMau08TemplateExists(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        // Cấu hình trang A4 đứng, lề chuẩn văn bản hành chính Việt Nam
        $section = $phpWord->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => Converter::cmToTwip(1.5),
            'marginBottom' => Converter::cmToTwip(1.5),
            'marginLeft'   => Converter::cmToTwip(2.0),
            'marginRight'  => Converter::cmToTwip(1.5),
        ]);

        // 1. BẢNG TIÊU ĐỀ ĐẦU TRANG & METADATA
        $headerTable = $section->addTable([
            'alignment'  => JcTable::CENTER,
            'cellMargin' => 40,
        ]);

        $r1 = $headerTable->addRow();
        // Cột trái: Tên trường & Khoa
        $c1 = $r1->addCell(3400);
        $c1->addText('TRƯỜNG CAO ĐẲNG', ['name' => 'Times New Roman', 'size' => 10], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $c1->addText('KỸ THUẬT - CÔNG NGHỆ ĐỒNG NAI', ['name' => 'Times New Roman', 'size' => 10, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $c1->addText('KHOA CÔNG NGHỆ THÔNG TIN', ['name' => 'Times New Roman', 'size' => 10, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Cột giữa: Tiêu đề KẾ HOẠCH GIẢNG DẠY
        $c2 = $r1->addCell(3500);
        $c2->addText('KẾ HOẠCH GIẢNG DẠY', ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Cột phải: Mẫu số 8
        $c3 = $r1->addCell(2700);
        $c3->addText('Mẫu số 8', ['name' => 'Times New Roman', 'size' => 10, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $c3->addText('(Ban hành kèm theo Quyết định:......../QĐ-CĐKTCN ngày …......)', ['name' => 'Times New Roman', 'size' => 8, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Hàng thông tin chi tiết môn học & lớp học
        $r2 = $headerTable->addRow();
        $cMeta1 = $r2->addCell(3000);
        $cMeta1->addText('Lớp: ${lop}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 30]);
        $cMeta1->addText('Năm: ${nam}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 30]);
        $cMeta1->addText('Năm học: ${nam_hoc}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 30]);

        $cMeta2 = $r2->addCell(3600);
        $cMeta2->addText('Môn học: ${mon_hoc}', ['name' => 'Times New Roman', 'size' => 10, 'bold' => true], ['spaceAfter' => 30]);
        $cMeta2->addText('Giảng viên: ${giang_vien}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 30]);
        $cMeta2->addText('Học kỳ: ${hoc_ky}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 30]);

        $cMeta3 = $r2->addCell(3000);
        $cMeta3->addText('Số giờ môn học: ${so_gio_mon}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 20]);
        $cMeta3->addText('(LT: ${tong_lt}, TH: ${tong_th}, KT: ${tong_kt})', ['name' => 'Times New Roman', 'size' => 9, 'italic' => true], ['spaceAfter' => 20]);
        $cMeta3->addText('Số giờ đã giảng HK trước: ${so_gio_truoc}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 20]);
        $cMeta3->addText('Số giờ giảng trong HK: ${so_gio_ky_nay}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 20]);
        $cMeta3->addText('Số giờ còn lại: ${so_gio_con_lai}', ['name' => 'Times New Roman', 'size' => 10], ['spaceAfter' => 20]);

        $section->addTextBreak(1, ['size' => 6]);

        // 2. BẢNG TIẾN ĐỘ KẾ HOẠCH GIẢNG DẠY
        $tableStyle = [
            'borderColor' => '000000',
            'borderSize'  => 6,
            'cellMargin'  => 50,
            'alignment'   => JcTable::CENTER,
        ];
        $mainTable = $section->addTable($tableStyle);

        // Header Hàng 1
        $h1 = $mainTable->addRow(null, ['tblHeader' => true]);
        $h1->addCell(650, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Thứ tự bài giảng', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h1->addCell(3750, ['vMerge' => 'restart', 'valign' => 'center'])->addText("Tên bài giảng\n(Ghi tóm tắt nội dung)", ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h1->addCell(1500, ['gridSpan' => 3, 'valign' => 'center'])->addText('Số giờ', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h1->addCell(1100, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Ngày thực hiện', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h1->addCell(1800, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Thiết bị, phương tiện và đồ dùng dạy học', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h1->addCell(800, ['vMerge' => 'restart', 'valign' => 'center'])->addText('Ghi chú', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Header Hàng 2 (chia 3 cột con LT, TH, KT)
        $h2 = $mainTable->addRow(null, ['tblHeader' => true]);
        $h2->addCell(650, ['vMerge' => 'continue']);
        $h2->addCell(3750, ['vMerge' => 'continue']);
        $h2->addCell(500, ['valign' => 'center'])->addText('LT', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h2->addCell(500, ['valign' => 'center'])->addText('TH', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h2->addCell(500, ['valign' => 'center'])->addText('KT', ['name' => 'Times New Roman', 'size' => 9.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $h2->addCell(1100, ['vMerge' => 'continue']);
        $h2->addCell(1800, ['vMerge' => 'continue']);
        $h2->addCell(800, ['vMerge' => 'continue']);

        // Hàng dữ liệu mẫu cho TemplateProcessor cloneRow
        $rData = $mainTable->addRow();
        $rData->addCell(650, ['valign' => 'center'])->addText('${stt}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $rData->addCell(3750, ['valign' => 'center'])->addText('${noi_dung}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
        $rData->addCell(500, ['valign' => 'center'])->addText('${lt}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $rData->addCell(500, ['valign' => 'center'])->addText('${th}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $rData->addCell(500, ['valign' => 'center'])->addText('${kt}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $rData->addCell(1100, ['valign' => 'center'])->addText('${ngay}', ['name' => 'Times New Roman', 'size' => 9.5], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $rData->addCell(1800, ['valign' => 'center'])->addText('${thiet_bi}', ['name' => 'Times New Roman', 'size' => 9], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
        $rData->addCell(800, ['valign' => 'center'])->addText('${ghi_chu}', ['name' => 'Times New Roman', 'size' => 9], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $section->addTextBreak(1, ['size' => 6]);

        // 3. BẢNG CHỮ KÝ CUỐI TRANG
        $signTable = $section->addTable([
            'alignment'  => JcTable::CENTER,
            'cellMargin' => 40,
        ]);

        $sRowDate = $signTable->addRow();
        $sRowDate->addCell(3200);
        $sRowDate->addCell(3200);
        $sCellDate = $sRowDate->addCell(3200);
        $sCellDate->addText('Đồng Nai, ngày … tháng … năm …', ['name' => 'Times New Roman', 'size' => 10, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $sRowTitle = $signTable->addRow();
        $sCol1 = $sRowTitle->addCell(3200);
        $sCol1->addText('Phó Hiệu trưởng đào tạo', ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $sCol1->addText('(Ký, đóng dấu)', ['name' => 'Times New Roman', 'size' => 9.5, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $sCol2 = $sRowTitle->addCell(3200);
        $sCol2->addText('Trưởng khoa', ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $sCol2->addText('(Ký tên)', ['name' => 'Times New Roman', 'size' => 9.5, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $sCol3 = $sRowTitle->addCell(3200);
        $sCol3->addText('Giảng viên', ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $sCol3->addText('(Ký tên)', ['name' => 'Times New Roman', 'size' => 9.5, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Dòng khoảng cách để ký tên
        $sRowSpace = $signTable->addRow();
        $sRowSpace->addCell(3200)->addTextBreak(3);
        $sRowSpace->addCell(3200)->addTextBreak(3);
        $sRowSpace->addCell(3200)->addTextBreak(3);

        // Dòng họ tên giảng viên
        $sRowName = $signTable->addRow();
        $sRowName->addCell(3200);
        $sRowName->addCell(3200);
        $sCellGvName = $sRowName->addCell(3200);
        $sCellGvName->addText('${giang_vien}', ['name' => 'Times New Roman', 'size' => 10.5, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
    }
}
