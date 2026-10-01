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
     * Xuất trọn bộ Sổ Giáo Án của môn học cho lớp (Trang bìa + tất cả các buổi gộp 1 file Word duy nhất)
     */
    public function exportBooklet(Classes $class, Subject $subject, Collection $schedules, array $options = []): string
    {
        $tempDir = $this->initTempDir();

        $type = $subject->effective_type; // 'integrated', 'theory', 'practice'
        $teacher = $subject->teacher;
        $teacherName = $options['giang_vien'] ?? ($teacher?->name ?? 'Hoàng Văn Nhân');

        $firstSession = $schedules->first();
        $firstDate = $firstSession?->teaching_date;
        $firstCarbon = $firstDate ? Carbon::parse($firstDate) : Carbon::now();
        $year = $firstCarbon->year;
        $academicYear = $options['nam_hoc'] ?? "{$year}-" . ($year + 1);

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name);
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code);
        $fileName = "So_Giao_An_{$subject->form_code}_{$cleanClassName}_{$cleanSubjectCode}_" . time() . ".docx";
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        // Nếu môn học có file mẫu tải lên, sử dụng công cụ ghép nối OpenXML để giữ trọn hình ảnh và bố cục
        if ($subject->countUploadedTemplates() > 0) {
            $sessionDocPaths = [];
            $prevLessonTitle = 'Không';

            foreach ($schedules as $schedule) {
                $sessNum = $schedule->session_number;
                if ($subject->hasTemplateForSession($sessNum)) {
                    $templatePath = $subject->getTemplatePathForSession($sessNum);
                    $sessionDocPaths[] = $this->processCustomTemplate($templatePath, $schedule, $class, $subject, $teacherName);
                } else {
                    $content = $schedule->subjectContent;
                    $sessionDocPaths[] = $this->renderSingleLessonDocx($schedule, $content, $subject, $class, $type, $prevLessonTitle, $teacherName);
                }
                $content = $schedule->subjectContent;
                $prevLessonTitle = $content ? $content->title_clean : ('Bài học số ' . $sessNum);
            }

            $coverData = [
                'class_name'   => $class->name,
                'subject_name' => $subject->name,
                'subject_code' => $subject->code,
                'teacher_name' => $teacherName,
                'academic_year'=> $academicYear,
                'form_label'   => match ($type) {
                    'theory'   => 'Mẫu số 9a',
                    'practice' => 'Mẫu số 9b',
                    default    => 'Mẫu số 9c',
                },
                'type_label'   => match ($type) {
                    'theory'   => 'LÝ THUYẾT',
                    'practice' => 'THỰC HÀNH',
                    default    => 'TÍCH HỢP',
                },
                'year'         => $year,
            ];

            return $this->buildMergedBookletDocx($sessionDocPaths, $outputPath, $coverData);
        }

        // Trường hợp môn học chưa có file mẫu nào: dùng bộ sinh mẫu tự động PhpWord
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        // 1. Tạo Trang Bìa
        $this->renderCoverPage($phpWord, $class, $subject, $teacherName, $academicYear, $year, $type);

        // 2. Tạo từng Giáo án cho từng buổi học
        $prevLessonTitle = 'Không';
        foreach ($schedules as $index => $schedule) {
            $content = $schedule->subjectContent;
            $this->renderSingleLessonSection($phpWord, $schedule, $content, $subject, $class, $type, $prevLessonTitle, $teacherName);
            $prevLessonTitle = $content ? $content->title_clean : ('Bài học số ' . $schedule->session_number);
        }

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
     * Xuất riêng 1 file Trang Bìa Sổ Giáo Án (.docx)
     */
    public function exportCoverPageDocx(Classes $class, Subject $subject, string $teacherName, array $options = []): string
    {
        $tempDir = $this->initTempDir();
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $type = $subject->effective_type ?? 'integrated';
        $year = (int)($options['nam'] ?? date('Y'));
        $academicYear = $options['nam_hoc'] ?? "{$year}-" . ($year + 1);

        $this->renderCoverPage($phpWord, $class, $subject, $teacherName, $academicYear, $year, $type);

        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name);
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code);
        $fileName = "00_Trang_Bia_{$cleanClassName}_{$cleanSubjectCode}_" . uniqid() . ".docx";
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
     * Xuất trọn gói toàn bộ giáo án từng buổi của lớp chia thành các file lẻ và nén vào 1 file ZIP
     * Kèm theo file: 00_Trang_Bia_[tenlop]_[mamon].docx
     * Tên các file con: Giao_An_So_XX_[tenlop]_[mamon].docx
     */
    public function exportClassLessonPlansZip(Classes $class, Subject $subject, $schedules, array $options = []): string
    {
        $tempDir = $this->initTempDir();
        $cleanClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class->name);
        $cleanSubjectCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $subject->code);

        $firstSession = is_iterable($schedules) ? (is_object($schedules) && method_exists($schedules, 'first') ? $schedules->first() : ($schedules[0] ?? null)) : null;
        $firstDate = $firstSession?->teaching_date;
        $firstCarbon = $firstDate ? Carbon::parse($firstDate) : Carbon::now();
        $year = (int)($options['nam'] ?? $firstCarbon->year);
        $academicYear = $options['nam_hoc'] ?? "{$year}-" . ($year + 1);
        $options['nam'] = $year;
        $options['nam_hoc'] = $academicYear;

        $zipPath = $tempDir . DIRECTORY_SEPARATOR . "{$cleanClassName}-{$cleanSubjectCode}_" . uniqid() . ".zip";
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Không thể tạo file ZIP tại: {$zipPath}");
        }

        $teacherName = $options['giang_vien'] ?? ($subject->teacher?->name ?? 'Hoàng Văn Nhân');
        $createdFiles = [];

        try {
            // 1. Thêm file Trang Bìa (00_Trang_Bia_...)
            $coverDocxPath = $this->exportCoverPageDocx($class, $subject, $teacherName, $options);
            $createdFiles[] = $coverDocxPath;
            $coverEntryName = "00_Trang_Bia_{$cleanClassName}_{$cleanSubjectCode}.docx";
            $zip->addFile($coverDocxPath, $coverEntryName);

            // 2. Thêm từng giáo án của các buổi học
            foreach ($schedules as $schedule) {
                $sessDocxPath = $this->exportSinglePlan($schedule, $options);
                $createdFiles[] = $sessDocxPath;
                $sessPad = sprintf('%02d', $schedule->session_number);
                $entryName = "Giao_An_So_{$sessPad}_{$cleanClassName}_{$cleanSubjectCode}.docx";
                $zip->addFile($sessDocxPath, $entryName);
            }

            $zip->close();
        } finally {
            // Dọn dẹp tất cả các file tạm lẻ sau khi nén vào file ZIP
            foreach ($createdFiles as $f) {
                if (file_exists($f)) {
                    @unlink($f);
                }
            }
        }

        return $zipPath;
    }

    /**
     * Xuất lẻ giáo án của 1 buổi học cụ thể
     */
    public function exportSinglePlan(Schedule $schedule, array $options = []): string
    {
        $subject = $schedule->subject;
        $class = $schedule->class;
        $teacherName = $options['giang_vien'] ?? ($schedule->teacher?->name ?? ($subject?->teacher?->name ?? 'Hoàng Văn Nhân'));

        // Ưu tiên: nếu buổi này có file mẫu Word riêng đã tải lên -> điền động và xuất file mẫu chuẩn
        if ($subject && $subject->hasTemplateForSession($schedule->session_number)) {
            $templatePath = $subject->getTemplatePathForSession($schedule->session_number);
            return $this->processCustomTemplate($templatePath, $schedule, $class, $subject, $teacherName);
        }

        // Nếu chưa có file mẫu: sinh mẫu chuẩn từ nội dung trong hệ thống
        $tempDir = $this->initTempDir();
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $type = $subject ? $subject->effective_type : 'integrated';

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
     * Xử lý điền dữ liệu động vào file mẫu Word (.docx) của Thầy:
     * - Phương án A: Ghép chung dòng với ngày: Thực hiện ngày: dd/mm/yyyy - Lớp: TênLớp
     * - Ngày ký: trước ngày dạy 1 tuần (teaching_date - 7 days)
     * - Ký duyệt: Tên giáo viên đứng lớp (IN HOA), Trưởng khoa cố định
     * - Giữ nguyên 100% hình ảnh, bảng biểu và định dạng gốc
     */
    public function processCustomTemplate(string $templatePath, Schedule $schedule, Classes $class, Subject $subject, string $teacherName): string
    {
        $tempDir = $this->initTempDir();

        $zip = new \ZipArchive();
        if ($zip->open($templatePath) !== true) {
            throw new Exception("Không thể mở file giáo án mẫu tại: {$templatePath}");
        }

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $files[$name] = $zip->getFromIndex($i);
        }
        $zip->close();

        if (!isset($files['word/document.xml'])) {
            throw new Exception("File giáo án mẫu không đúng định dạng OpenXML (.docx hợp lệ).");
        }

        $docXml = $files['word/document.xml'];

        // 1. Tính toán ngày tháng và thông tin
        $teachingDate = $schedule->teaching_date ? Carbon::parse($schedule->teaching_date) : Carbon::now();
        $teachingDateStr = $teachingDate->format('d/m/Y');

        // Yêu cầu 5: Ngày ký trước ngày dạy 1 tuần
        $signDate = $teachingDate->copy()->subDays(7);
        $signDay = $signDate->format('d');
        $signMonth = $signDate->format('m');
        $signYear = $signDate->format('Y');

        // Yêu cầu 5: Tên giáo viên theo phân công đứng lớp (IN HOA)
        $cleanTeacherName = preg_replace('/^(?:ThS|TS|PGS|GS|Ths|Ts)\.?\s+/iu', '', trim($teacherName));
        $teacherUpper = htmlspecialchars(mb_strtoupper($cleanTeacherName, 'UTF-8'), ENT_XML1, 'UTF-8');
        $headOfDept = 'THÁI QUỐC THẮNG';
        $className = htmlspecialchars($class->name, ENT_XML1, 'UTF-8');
        $sessionNum = (int)$schedule->session_number;

        // 2. Thay thế placeholder macros nếu file có dùng macro ${...}
        $macros = [
            '${lop}'                => $className,
            '${ten_lop}'            => $className,
            '${ngay_day}'           => $teachingDateStr,
            '${ngay_thuc_hien}'     => $teachingDateStr,
            '${ngay_ky}'            => $signDay,
            '${thang_ky}'           => $signMonth,
            '${nam_ky}'             => $signYear,
            '${giang_vien}'         => $teacherUpper,
            '${giao_vien}'          => $teacherUpper,
            '${truong_khoa}'        => $headOfDept,
            '${so_giao_an}'         => sprintf('%02d', $sessionNum),
            '${thoi_gian_thuc_hien}'=> '4 giờ',
        ];
        $docXml = strtr($docXml, $macros);

        // 3. Tự động nhận diện và thay thế chính xác các phần trong cấu trúc file mẫu:

        // A. Thay thế mục: Thực hiện ngày: ... thành: Thực hiện ngày: dd/mm/yyyy  -  Lớp: TênLớp (Phương án A)
        $docXml = preg_replace_callback('/<w:p\b[^>]*>.*?<\/w:p>/s', function($match) use ($teachingDateStr, $className) {
            $pXml = $match[0];
            $cleanText = strip_tags($pXml);
            if (mb_stripos($cleanText, 'thực hiện ngày') !== false) {
                // Trích xuất pPr để giữ nguyên tab stop và lề paragraph
                preg_match('/<w:pPr\b.*?<\/w:pPr>/s', $pXml, $pPrMatch);
                $pPr = $pPrMatch[0] ?? '';
                $newText = "Thực hiện ngày: {$teachingDateStr}  -  Lớp: {$className}";
                return "<w:p>{$pPr}<w:r><w:tab/></w:r><w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:t xml:space=\"preserve\">{$newText}</w:t></w:r></w:p>";
            }
            return $pXml;
        }, $docXml);

        // B. Thay thế mục: GIÁO ÁN SỐ: ... đảm bảo số buổi học khớp chính xác với lịch dạy
        $docXml = preg_replace_callback('/<w:p\b[^>]*>.*?<\/w:p>/s', function($match) use ($sessionNum) {
            $pXml = $match[0];
            $cleanText = strip_tags($pXml);
            if (mb_stripos($cleanText, 'giáo án số') !== false) {
                preg_match('/<w:pPr\b.*?<\/w:pPr>/s', $pXml, $pPrMatch);
                $pPr = $pPrMatch[0] ?? '';
                $sessStr = sprintf('%02d', $sessionNum);
                return "<w:p>{$pPr}" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:t>GIÁO ÁN SỐ:</w:t></w:r>" .
                       "<w:r><w:rPr><w:color w:val=\"000000\"/></w:rPr><w:t xml:space=\"preserve\">  </w:t></w:r>" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/><w:sz w:val=\"30\"/><w:szCs w:val=\"30\"/></w:rPr><w:t>{$sessStr}</w:t></w:r>" .
                       "<w:r><w:rPr><w:color w:val=\"000000\"/></w:rPr><w:tab/><w:t>Thời gian thực hiện</w:t><w:tab/><w:t>: 4 giờ</w:t></w:r>" .
                       "</w:p>";
            }
            return $pXml;
        }, $docXml);

        // C. Thay thế ngày ký duyệt ở cuối file: TRƯỞNG KHOA [tab] Ngày ... tháng ... năm ...
        $docXml = preg_replace_callback('/<w:p\b[^>]*>.*?<\/w:p>/s', function($match) use ($signDay, $signMonth, $signYear) {
            $pXml = $match[0];
            $cleanText = strip_tags($pXml);
            if (mb_stripos($cleanText, 'trưởng khoa') !== false && (mb_stripos($cleanText, 'ngày') !== false || mb_stripos($cleanText, 'ngay') !== false)) {
                preg_match('/<w:pPr\b.*?<\/w:pPr>/s', $pXml, $pPrMatch);
                $pPr = $pPrMatch[0] ?? '';
                $signText = "Ngày {$signDay} tháng {$signMonth} năm {$signYear}.";
                return "<w:p>{$pPr}" .
                       "<w:r><w:rPr><w:color w:val=\"000000\"/></w:rPr><w:tab/></w:r>" .
                       "<w:r><w:rPr><w:color w:val=\"000000\"/></w:rPr><w:t>TRƯỞNG KHOA</w:t></w:r>" .
                       "<w:r><w:rPr><w:color w:val=\"000000\"/></w:rPr><w:tab/></w:r>" .
                       "<w:r><w:rPr><w:i/><w:color w:val=\"000000\"/></w:rPr><w:t xml:space=\"preserve\">{$signText}</w:t></w:r>" .
                       "</w:p>";
            }
            return $pXml;
        }, $docXml);

        // D. Thay thế tên giáo viên ở phần ký tên cuối giáo án (Trưởng khoa: THÁI QUỐC THẮNG - Giáo viên: $teacherUpper)
        $docXml = preg_replace_callback('/<w:p\b[^>]*>.*?<\/w:p>/s', function($match) use ($headOfDept, $teacherUpper) {
            $pXml = $match[0];
            $cleanText = strip_tags($pXml);
            $cleanLower = mb_strtolower($cleanText, 'UTF-8');
            if (mb_stripos($cleanLower, 'thái quốc thắng') !== false && (
                mb_stripos($cleanLower, 'hoàng văn nhân') !== false ||
                substr_count($cleanLower, 'thái quốc thắng') >= 2 ||
                mb_stripos($cleanLower, 'giáo viên') !== false
            )) {
                preg_match('/<w:pPr\b.*?<\/w:pPr>/s', $pXml, $pPrMatch);
                $pPr = $pPrMatch[0] ?? '';
                return "<w:p>{$pPr}" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:tab/></w:r>" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:t>{$headOfDept}</w:t></w:r>" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:tab/></w:r>" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:t>{$teacherUpper}</w:t></w:r>" .
                       "<w:r><w:rPr><w:b/><w:color w:val=\"000000\"/></w:rPr><w:tab/></w:r>" .
                       "</w:p>";
            }
            return $pXml;
        }, $docXml);

        $files['word/document.xml'] = $docXml;

        // Lưu file tạm đã xử lý
        $outPath = $tempDir . DIRECTORY_SEPARATOR . 'proc_buoi_' . $schedule->session_number . '_' . uniqid() . '.docx';
        $outZip = new \ZipArchive();
        if ($outZip->open($outPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Không thể tạo file tạm tại: {$outPath}");
        }

        foreach ($files as $name => $content) {
            $outZip->addFromString($name, $content);
        }
        $outZip->close();

        return $outPath;
    }

    /**
     * Sinh file Word lẻ cho 1 buổi học từ cơ chế sinh mẫu chuẩn PhpWord
     */
    public function renderSingleLessonDocx(
        Schedule $schedule,
        ?SubjectContent $content,
        ?Subject $subject,
        ?Classes $class,
        string $type,
        string $prevLessonTitle,
        string $teacherName
    ): string {
        $tempDir = $this->initTempDir();
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $this->renderSingleLessonSection($phpWord, $schedule, $content, $subject, $class, $type, $prevLessonTitle, $teacherName);

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'gen_buoi_' . $schedule->session_number . '_' . uniqid() . '.docx';
        $oldErrorLevel = error_reporting();
        error_reporting($oldErrorLevel & ~E_NOTICE);
        try {
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);
        } finally {
            error_reporting($oldErrorLevel);
        }

        return $tempPath;
    }

    /**
     * Ghép nối nhiều file .docx thành 1 file Sổ Giáo Án duy nhất (kèm trang bìa)
     * - Bảo toàn 100% hình ảnh minh hoạ, bảng biểu, ngắt trang
     * - Tránh xung đột ID ảnh (rId) và mối liên kết rels trong OpenXML
     */
    public function buildMergedBookletDocx(array $sessionDocPaths, string $outputPath, array $coverData = []): string
    {
        if (empty($sessionDocPaths)) {
            throw new Exception("Không có file giáo án nào để ghép sổ.");
        }

        $basePath = $sessionDocPaths[0];

        $zip = new \ZipArchive();
        if ($zip->open($basePath) !== true) {
            throw new Exception("Không thể mở file mẫu giáo án đầu tiên: " . $basePath);
        }

        $baseFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $baseFiles[$name] = $zip->getFromIndex($i);
        }
        $zip->close();

        if (!isset($baseFiles['word/document.xml'])) {
            throw new Exception("File đầu tiên không hợp lệ.");
        }

        $baseDocXml = $baseFiles['word/document.xml'];
        $baseRelsXml = $baseFiles['word/_rels/document.xml.rels'] ?? '';

        // Thu thập toàn bộ media của file gốc
        $masterMedia = [];
        foreach ($baseFiles as $name => $content) {
            if (str_starts_with($name, 'word/media/')) {
                $masterMedia[$name] = $content;
            }
        }

        // Bổ sung các định dạng ảnh phổ biến vào [Content_Types].xml nếu còn thiếu
        $contentTypesXml = $baseFiles['[Content_Types].xml'] ?? '';
        if (!empty($contentTypesXml)) {
            $neededMimes = [
                'png'  => 'image/png',
                'gif'  => 'image/gif',
                'jpeg' => 'image/jpeg',
                'jpg'  => 'image/jpeg',
                'emf'  => 'image/x-emf',
                'wmf'  => 'image/x-wmf',
                'tiff' => 'image/tiff',
                'tif'  => 'image/tiff',
            ];
            foreach ($neededMimes as $ext => $mime) {
                if (!str_contains($contentTypesXml, 'Extension="' . $ext . '"')) {
                    $contentTypesXml = str_replace('</Types>', '<Default Extension="' . $ext . '" ContentType="' . $mime . '"/></Types>', $contentTypesXml);
                }
            }
            $baseFiles['[Content_Types].xml'] = $contentTypesXml;
        }

        // Đọc relationships XML của file gốc và tìm Relationship ID của Footer chuẩn
        $masterFooterRelId = null;
        $relsXmlDoc = new \DOMDocument();
        if (!empty($baseRelsXml)) {
            @$relsXmlDoc->loadXML($baseRelsXml);
            $relNodes = $relsXmlDoc->getElementsByTagName('Relationship');
            foreach ($relNodes as $relNode) {
                $rType = $relNode->getAttribute('Type');
                $rTarget = $relNode->getAttribute('Target');
                if (str_contains($rType, '/relationships/footer') || str_starts_with($rTarget, 'footer')) {
                    $masterFooterRelId = $relNode->getAttribute('Id');
                    break;
                }
            }
        } else {
            $relsXmlDoc->loadXML('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
        }

        // Tách body và phần sectPr kết thúc của file gốc
        $bStart = strpos($baseDocXml, '<w:body>');
        $bEnd = strrpos($baseDocXml, '</w:body>');
        if ($bStart === false || $bEnd === false) {
            throw new Exception("Cấu trúc XML của file giáo án không hợp lệ.");
        }
        $bStart += strlen('<w:body>');
        $baseBody = substr($baseDocXml, $bStart, $bEnd - $bStart);
        $baseBody = rtrim($baseBody);

        // Tìm sectPr cuối cùng chính xác bằng strrpos để không làm rách XML (tránh regex tham lam)
        $lastSectPos = strrpos($baseBody, '<w:sectPr');
        if ($lastSectPos !== false && str_ends_with($baseBody, '</w:sectPr>')) {
            $baseTrailingSectPr = substr($baseBody, $lastSectPos);
            $combinedBody = substr($baseBody, 0, $lastSectPos);
        } else {
            $baseTrailingSectPr = '<w:sectPr><w:pgSz w:w="11907" w:h="16840" w:code="9"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="567" w:footer="567" w:gutter="0"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr>';
            $combinedBody = $baseBody;
        }

        // Chuẩn hoá footer trong sectPr chuẩn
        if ($masterFooterRelId) {
            if (str_contains($baseTrailingSectPr, '<w:footerReference')) {
                $stdSectPr = preg_replace('/<w:footerReference\b[^>]*\/>/', '<w:footerReference w:type="default" r:id="' . $masterFooterRelId . '"/>', $baseTrailingSectPr);
            } else {
                $stdSectPr = preg_replace('/(<w:sectPr\b[^>]*>)/', '$1<w:footerReference w:type="default" r:id="' . $masterFooterRelId . '"/>', $baseTrailingSectPr);
            }
            // Chuẩn hoá tất cả footer references trong phần thân buổi 1
            $combinedBody = preg_replace('/<w:footerReference\b[^>]*\/>/', '<w:footerReference w:type="default" r:id="' . $masterFooterRelId . '"/>', $combinedBody);
        } else {
            $stdSectPr = preg_replace('/<w:footerReference\b[^>]*\/>/', '', $baseTrailingSectPr);
            $combinedBody = preg_replace('/<w:footerReference\b[^>]*\/>/', '', $combinedBody);
        }

        // Chèn Trang Bìa chuẩn lên đầu (nếu có thông tin bìa)
        if (!empty($coverData)) {
            $coverXml = $this->generateCoverPageXml($coverData);
            $combinedBody = $coverXml . $combinedBody;
        }

        $rIdCounter = 1000;

        // Lần lượt ghép các file giáo án của các buổi tiếp theo
        for ($docIdx = 1; $docIdx < count($sessionDocPaths); $docIdx++) {
            $subPath = $sessionDocPaths[$docIdx];
            $subZip = new \ZipArchive();
            if ($subZip->open($subPath) !== true) {
                continue;
            }

            $subFiles = [];
            for ($k = 0; $k < $subZip->numFiles; $k++) {
                $sname = $subZip->getNameIndex($k);
                $subFiles[$sname] = $subZip->getFromIndex($k);
            }
            $subZip->close();

            if (!isset($subFiles['word/document.xml'])) {
                continue;
            }

            $subDocXml = $subFiles['word/document.xml'];
            $subRelsXml = $subFiles['word/_rels/document.xml.rels'] ?? '';

            // Đổi tên ảnh và remap relationship ID để không bao giờ bị đè ảnh
            $rIdMap = [];
            if (!empty($subRelsXml)) {
                $subRelsDoc = new \DOMDocument();
                if (@$subRelsDoc->loadXML($subRelsXml)) {
                    $relNodes = $subRelsDoc->getElementsByTagName('Relationship');
                    foreach ($relNodes as $relNode) {
                        $oldId = $relNode->getAttribute('Id');
                        $target = $relNode->getAttribute('Target');
                        $type = $relNode->getAttribute('Type');

                        if (str_contains($type, 'image') || str_starts_with($target, 'media/')) {
                            $mediaName = basename($target);
                            $newMediaTarget = "media/doc_{$docIdx}_{$mediaName}";
                            $subMediaPath = "word/{$target}";

                            if (isset($subFiles[$subMediaPath])) {
                                $masterMedia["word/{$newMediaTarget}"] = $subFiles[$subMediaPath];
                            }

                            $rIdCounter++;
                            $newId = "rIdMed{$rIdCounter}";
                            $rIdMap[$oldId] = $newId;

                            $newRel = $relsXmlDoc->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
                            $newRel->setAttribute('Id', $newId);
                            $newRel->setAttribute('Type', $type);
                            $newRel->setAttribute('Target', $newMediaTarget);
                            if ($relsXmlDoc->documentElement) {
                                $relsXmlDoc->documentElement->appendChild($newRel);
                            }
                        }
                    }
                }
            }

            // Sắp xếp key dài trước ngắn sau để tránh thay thế nhầm chuỗi con
            uksort($rIdMap, fn($a, $b) => strlen($b) <=> strlen($a));

            // Thay thế các ID liên kết ảnh trong XML buổi này
            foreach ($rIdMap as $oldId => $newId) {
                $subDocXml = preg_replace('/([\" >])' . preg_quote($oldId, '/') . '([\" <])/', '$1' . $newId . '$2', $subDocXml);
            }

            // Đồng bộ toàn bộ liên kết chân trang về footer của master document
            if ($masterFooterRelId) {
                $subDocXml = preg_replace('/<w:footerReference\b[^>]*\/>/', '<w:footerReference w:type="default" r:id="' . $masterFooterRelId . '"/>', $subDocXml);
            } else {
                $subDocXml = preg_replace('/<w:footerReference\b[^>]*\/>/', '', $subDocXml);
            }

            // Trích xuất phần thân buổi này
            $sStart = strpos($subDocXml, '<w:body>');
            $sEnd = strrpos($subDocXml, '</w:body>');
            if ($sStart === false || $sEnd === false) {
                continue;
            }
            $sStart += strlen('<w:body>');
            $subBody = substr($subDocXml, $sStart, $sEnd - $sStart);
            $subBody = rtrim($subBody);

            $lastSubSectPos = strrpos($subBody, '<w:sectPr');
            if ($lastSubSectPos !== false && str_ends_with($subBody, '</w:sectPr>')) {
                $subMain = substr($subBody, 0, $lastSubSectPos);
            } else {
                $subMain = $subBody;
            }

            // Thêm ngắt phần giữa các buổi và ghép nội dung buổi mới
            $combinedBody .= "<w:p><w:pPr>{$stdSectPr}</w:pPr></w:p>" . $subMain;
        }

        // Đóng lại với sectPr cuối cùng của văn bản
        $finalDocXml = substr($baseDocXml, 0, $bStart) . $combinedBody . $stdSectPr . substr($baseDocXml, $bEnd);

        // Đánh lại số thứ tự id duy nhất cho toàn bộ wp:docPr để tuân thủ 100% chuẩn OpenXML
        $docPrCounter = 0;
        $finalDocXml = preg_replace_callback('/<wp:docPr\b([^>]*)>/', function($m) use (&$docPrCounter) {
            $docPrCounter++;
            $attrs = $m[1];
            if (preg_match('/id="[^"]*"/', $attrs)) {
                $attrs = preg_replace('/id="[^"]*"/', 'id="' . $docPrCounter . '"', $attrs);
            } else {
                $attrs .= ' id="' . $docPrCounter . '"';
            }
            return '<wp:docPr' . $attrs . '>';
        }, $finalDocXml);

        $finalRelsXml = $relsXmlDoc->saveXML();

        // Tạo file zip đầu ra hoàn chỉnh
        $outZip = new \ZipArchive();
        if ($outZip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Không thể tạo file sổ giáo án tại: " . $outputPath);
        }

        foreach ($baseFiles as $fname => $fcontent) {
            if ($fname === 'word/document.xml') {
                $outZip->addFromString($fname, $finalDocXml);
            } elseif ($fname === 'word/_rels/document.xml.rels') {
                $outZip->addFromString($fname, $finalRelsXml);
            } elseif ($fname === '[Content_Types].xml') {
                $outZip->addFromString($fname, $contentTypesXml);
            } elseif (!str_starts_with($fname, 'word/media/')) {
                $outZip->addFromString($fname, $fcontent);
            }
        }

        foreach ($masterMedia as $mname => $mcontent) {
            $outZip->addFromString($mname, $mcontent);
        }

        $outZip->close();

        return $outputPath;
    }

    /**
     * Tạo mã OpenXML cho Trang Bìa Sổ Giáo Án (trang đầu tiên)
     */
    protected function generateCoverPageXml(array $data): string
    {
        $className   = htmlspecialchars($data['class_name'] ?? 'Lớp', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $subjectName = htmlspecialchars($data['subject_name'] ?? 'Môn học', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $subjectCode = htmlspecialchars($data['subject_code'] ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $teacherName = htmlspecialchars($data['teacher_name'] ?? 'Hoàng Văn Nhân', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $academicYr  = htmlspecialchars($data['academic_year'] ?? '2026-2027', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $formLabel   = htmlspecialchars($data['form_label'] ?? 'Mẫu số 9c', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $typeLabel   = htmlspecialchars($data['type_label'] ?? 'TÍCH HỢP', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $year        = htmlspecialchars($data['year'] ?? date('Y'), ENT_XML1 | ENT_COMPAT, 'UTF-8');

        return <<<XML
<w:p>
    <w:pPr><w:jc w:val="right"/></w:pPr>
    <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t>{$formLabel}</w:t></w:r>
</w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="60"/></w:pPr>
    <w:r><w:rPr><w:b/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t>UBND THÀNH PHỐ ĐỒNG NAI</w:t></w:r>
</w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="100"/></w:pPr>
    <w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>TRƯỜNG CAO ĐẲNG KỸ THUẬT - CÔNG NGHỆ ĐỒNG NAI</w:t></w:r>
</w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="600"/></w:pPr>
    <w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t>---------------------------------</w:t></w:r>
</w:p>
<w:p><w:pPr><w:spacing w:after="300"/></w:pPr></w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="120"/></w:pPr>
    <w:r><w:rPr><w:b/><w:sz w:val="52"/><w:szCs w:val="52"/></w:rPr><w:t>SỔ GIÁO ÁN</w:t></w:r>
</w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/></w:pPr>
    <w:r><w:rPr><w:b/><w:color w:val="1F497D"/><w:sz w:val="44"/><w:szCs w:val="44"/></w:rPr><w:t>{$typeLabel}</w:t></w:r>
</w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/><w:spacing w:after="600"/></w:pPr>
    <w:r><w:rPr><w:i/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>(Ban hành kèm theo Quyết định:......../QĐ-CĐKTCN ngày …………………..)</w:t></w:r>
</w:p>
<w:p><w:pPr><w:spacing w:after="500"/></w:pPr></w:p>
<w:tbl>
    <w:tblPr>
        <w:jc w:val="center"/>
        <w:tblW w:w="8500" w:type="dxa"/>
        <w:tblBorders>
            <w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/>
            <w:insideH w:val="none"/><w:insideV w:val="none"/>
        </w:tblBorders>
    </w:tblPr>
    <w:tr>
        <w:tc><w:tcPr><w:tcW w:w="2600" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>Môn học / Mô đun:</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="5900" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>{$subjectName} ({$subjectCode})</w:t></w:r></w:p></w:tc>
    </w:tr>
    <w:tr>
        <w:tc><w:tcPr><w:tcW w:w="2600" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>Lớp học:</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="5900" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>{$className}</w:t></w:r></w:p></w:tc>
    </w:tr>
    <w:tr>
        <w:tc><w:tcPr><w:tcW w:w="2600" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>Giáo viên:</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="5900" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>{$teacherName}</w:t></w:r></w:p></w:tc>
    </w:tr>
    <w:tr>
        <w:tc><w:tcPr><w:tcW w:w="2600" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>Năm học:</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="5900" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>{$academicYr}</w:t></w:r></w:p></w:tc>
    </w:tr>
</w:tbl>
<w:p><w:pPr><w:spacing w:after="800"/></w:pPr></w:p>
<w:p>
    <w:pPr><w:jc w:val="center"/></w:pPr>
    <w:r><w:rPr><w:i/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t>Đồng Nai, năm {$year}</w:t></w:r>
</w:p>
<w:p>
    <w:pPr>
        <w:sectPr>
            <w:pgSz w:w="11907" w:h="16840" w:code="9"/>
            <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
        </w:sectPr>
    </w:pPr>
</w:p>
XML;
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
