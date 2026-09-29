<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\Classes;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Exception;

class WordExportService
{
    /**
     * Xuất Sổ tay giảng dạy ra file docx dựa vào TemplateProcessor cloneRow
     *
     * @param Classes $class
     * @param Subject $subject
     * @param Collection $schedules Danh sách schedule kèm subjectContent
     * @return string Đường dẫn file tạm được sinh ra
     */
    public function exportHandbook(Classes $class, Subject $subject, Collection $schedules): string
    {
        $templatePath = storage_path('app/templates/handbook_template.docx');

        // Tạo template mặc định nếu chưa tồn tại
        if (!file_exists($templatePath)) {
            $this->ensureDefaultTemplateExists($templatePath);
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // 1. Gán các biến cơ bản trong Header / Nội dung tài liệu
        $templateProcessor->setValue('ten_lop', htmlspecialchars($class->name, ENT_QUOTES, 'UTF-8'));
        $templateProcessor->setValue('ten_mon', htmlspecialchars($subject->name, ENT_QUOTES, 'UTF-8'));

        $count = $schedules->count();

        // 2. cloneRow Logic: Nhân bản hàng theo số lượng buổi học
        // Bảng trong template phải chứa ít nhất 1 hàng có biến ${stt}, ${ngay_day}, ${ca_day}, ${noi_dung}, ${lt}, ${th}
        if ($count > 0) {
            $templateProcessor->cloneRow('stt', $count);

            foreach ($schedules as $index => $schedule) {
                // PHPWord cloneRow đánh chỉ mục từ 1 (#1, #2, ...)
                $i = $index + 1;
                $content = $schedule->subjectContent;

                $formattedDate = $schedule->teaching_date 
                    ? Carbon::parse($schedule->teaching_date)->format('d/m/Y') 
                    : '';

                $templateProcessor->setValue("stt#{$i}", $schedule->session_number ?? $i);
                $templateProcessor->setValue("ngay_day#{$i}", $formattedDate);
                $templateProcessor->setValue("ca_day#{$i}", $schedule->session_shift);
                $templateProcessor->setValue("noi_dung#{$i}", htmlspecialchars($content?->content ?? 'Chưa có nội dung', ENT_QUOTES, 'UTF-8'));
                $templateProcessor->setValue("lt#{$i}", $content?->theory_time ?? 0);
                $templateProcessor->setValue("th#{$i}", $content?->practice_time ?? 0);
                $templateProcessor->setValue("kt#{$i}", $content?->test_time ?? 0);
            }
        } else {
            // Không có dữ liệu thì xoá placeholder hàng
            $templateProcessor->setValue('stt', '');
            $templateProcessor->setValue('ngay_day', '');
            $templateProcessor->setValue('ca_day', '');
            $templateProcessor->setValue('noi_dung', '');
            $templateProcessor->setValue('lt', '');
            $templateProcessor->setValue('th', '');
            $templateProcessor->setValue('kt', '');
        }

        // Tạo file tạm trong thư mục storage/app/temp
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $fileName = 'So_Tay_Giang_Day_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name) . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code) . '_' . time() . '.docx';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        $templateProcessor->saveAs($outputPath);

        return $outputPath;
    }

    /**
     * Helper tự động tạo file mẫu template nếu người dùng chưa đặt file vào storage
     */
    protected function ensureDefaultTemplateExists(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('SỔ TAY KẾ HOẠCH GIẢNG DẠY', 1);
        $section->addText('Lớp: ${ten_lop}');
        $section->addText('Môn học: ${ten_mon}');
        $section->addTextBreak(1);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        // Table Header
        $table->addRow();
        $table->addCell(1000)->addText('STT', ['bold' => true]);
        $table->addCell(2000)->addText('Ngày dạy', ['bold' => true]);
        $table->addCell(1500)->addText('Ca dạy', ['bold' => true]);
        $table->addCell(4500)->addText('Nội dung giảng dạy', ['bold' => true]);
        $table->addCell(1000)->addText('LT', ['bold' => true]);
        $table->addCell(1000)->addText('TH', ['bold' => true]);

        // Table Data Template Row
        $table->addRow();
        $table->addCell(1000)->addText('${stt}');
        $table->addCell(2000)->addText('${ngay_day}');
        $table->addCell(1500)->addText('${ca_day}');
        $table->addCell(4500)->addText('${noi_dung}');
        $table->addCell(1000)->addText('${lt}');
        $table->addCell(1000)->addText('${th}');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
    }
}
