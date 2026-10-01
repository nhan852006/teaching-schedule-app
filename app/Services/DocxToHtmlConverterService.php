<?php

namespace App\Services;

class DocxToHtmlConverterService
{
    /**
     * Chuyển đổi file .docx sang chuỗi HTML hoàn chỉnh:
     * - Bảo toàn 100% hình ảnh minh họa (nhúng dạng base64 Data URI)
     * - Giữ nguyên toàn bộ cấu trúc bảng biểu, độ rộng cột, viền ô đen chuẩn A4
     * - Đồng bộ phông chữ Times New Roman và kiểu dáng hành chính sư phạm
     */
    public function convert(string $docxPath): ?string
    {
        if (!file_exists($docxPath)) {
            return null;
        }

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        @chmod($tempDir, 0777);

        $outHtml = $tempDir . DIRECTORY_SEPARATOR . 'html_' . uniqid() . '.html';
        $scriptPath = app_path('Services/docx_to_html.py');

        $pythonPaths = [
            '/usr/local/bin/python3',
            '/usr/bin/python3',
            'python3'
        ];

        $pythonCmd = 'python3';
        foreach ($pythonPaths as $p) {
            if ($p !== 'python3' && file_exists($p) && is_executable($p)) {
                $pythonCmd = $p;
                break;
            }
        }

        if (file_exists($scriptPath)) {
            $cmd = escapeshellcmd($pythonCmd) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($docxPath) . ' ' . escapeshellarg($outHtml) . ' 2>&1';
            @shell_exec($cmd);

            if (file_exists($outHtml) && filesize($outHtml) > 50) {
                $content = file_get_contents($outHtml);
                @unlink($outHtml);
                return $content;
            }
        }

        // Phương án dự phòng qua textutil nếu python không khả dụng
        $fallbackHtml = $tempDir . DIRECTORY_SEPARATOR . 'fb_' . uniqid() . '.html';
        @shell_exec('textutil -convert html ' . escapeshellarg($docxPath) . ' -output ' . escapeshellarg($fallbackHtml));
        if (file_exists($fallbackHtml)) {
            $rawHtml = file_get_contents($fallbackHtml);
            @unlink($fallbackHtml);
            return $rawHtml;
        }

        return null;
    }
}
