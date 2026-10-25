<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PdfService
{
    protected string $gsPath = '/usr/bin/gs';
    protected string $pdftoppmPath = '/usr/bin/pdftoppm';
    protected string $pdfinfoPath = '/usr/bin/pdfinfo';
    protected string $libreofficePath = '/usr/bin/libreoffice';

    public function __construct()
    {
        // Auto-detect binaries if installed elsewhere
        if (!file_exists($this->gsPath)) {
            $this->gsPath = trim(shell_exec('which gs 2>/dev/null') ?: 'gs');
        }
        if (!file_exists($this->pdftoppmPath)) {
            $this->pdftoppmPath = trim(shell_exec('which pdftoppm 2>/dev/null') ?: 'pdftoppm');
        }
        if (!file_exists($this->pdfinfoPath)) {
            $this->pdfinfoPath = trim(shell_exec('which pdfinfo 2>/dev/null') ?: 'pdfinfo');
        }
        if (!file_exists($this->libreofficePath)) {
            $this->libreofficePath = trim(shell_exec('which libreoffice 2>/dev/null') ?: shell_exec('which soffice 2>/dev/null') ?: 'libreoffice');
        }
    }

    /**
     * Get the number of pages in a PDF.
     */
    public function getPageCount(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        // Try pdfinfo first (fastest)
        $output = shell_exec(escapeshellcmd($this->pdfinfoPath) . ' ' . escapeshellarg($filePath) . ' 2>/dev/null');
        if ($output && preg_match('/Pages:\s+([0-9]+)/', $output, $matches)) {
            return (int) $matches[1];
        }

        // Fallback to FPDI
        try {
            $pdf = new RotatableFpdi();
            return (int) $pdf->setSourceFile($filePath);
        } catch (Exception $e) {
            // Try after normalizing with gs
            $tempNormalized = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';
            if ($this->normalizePdf($filePath, $tempNormalized)) {
                try {
                    $pdf = new RotatableFpdi();
                    $count = (int) $pdf->setSourceFile($tempNormalized);
                    @unlink($tempNormalized);
                    return $count;
                } catch (Exception) {
                    @unlink($tempNormalized);
                }
            }
        }

        return 0;
    }

    /**
     * Normalize PDF with Ghostscript (ensures compatibility with all parsers).
     */
    public function normalizePdf(string $inputPath, string $outputPath): bool
    {
        $cmd = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellcmd($this->gsPath),
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        exec($cmd, $out, $returnCode);
        return $returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Generate visual page thumbnails as base64 data URIs.
     *
     * @return array<int, array{page: int, dataUrl: string}>
     */
    public function generateThumbnails(string $filePath, int $maxPages = 80): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $tempDir = storage_path('app/temp_thumbs_' . uniqid());
        File::makeDirectory($tempDir, 0755, true, true);

        $prefix = $tempDir . '/page';
        $cmd = sprintf(
            '%s -png -r 90 -f 1 -l %d %s %s 2>&1',
            escapeshellcmd($this->pdftoppmPath),
            $maxPages,
            escapeshellarg($filePath),
            escapeshellarg($prefix)
        );

        exec($cmd, $out, $code);

        $thumbnails = [];
        $files = File::glob($tempDir . '/page-*.png');
        natsort($files);

        $pageNum = 1;
        foreach ($files as $file) {
            $data = file_get_contents($file);
            if ($data !== false) {
                $base64 = 'data:image/png;base64,' . base64_encode($data);
                $thumbnails[] = [
                    'page' => $pageNum,
                    'dataUrl' => $base64,
                ];
                $pageNum++;
            }
            @unlink($file);
        }

        @rmdir($tempDir);
        return $thumbnails;
    }

    /**
     * Merge multiple PDF files into one.
     *
     * @param array<int, string> $filePaths
     */
    public function merge(array $filePaths, string $outputPath): bool
    {
        if (empty($filePaths)) {
            throw new Exception('No input PDF files provided for merge.');
        }

        // Filter valid files
        $validPaths = array_values(array_filter($filePaths, fn($p) => file_exists($p) && filesize($p) > 0));
        if (empty($validPaths)) {
            throw new Exception('None of the provided PDF files exist or they are empty.');
        }

        // Primary: Ghostscript merge (retains highest fidelity & speed)
        $escapedInputs = implode(' ', array_map('escapeshellarg', $validPaths));
        $cmd = sprintf(
            '%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dAutoRotatePages=/None -sOutputFile=%s %s 2>&1',
            escapeshellcmd($this->gsPath),
            escapeshellarg($outputPath),
            $escapedInputs
        );

        exec($cmd, $output, $code);

        if ($code === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
            return true;
        }

        // Fallback: Pure FPDI merge
        try {
            $pdf = new RotatableFpdi();
            foreach ($validPaths as $file) {
                $count = $pdf->setSourceFile($file);
                for ($p = 1; $p <= $count; $p++) {
                    $tpl = $pdf->importPage($p);
                    $size = $pdf->getTemplateSize($tpl);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                }
            }
            $pdf->Output('F', $outputPath);
            return file_exists($outputPath) && filesize($outputPath) > 0;
        } catch (Exception $e) {
            Log::error('PDF Merge Error: ' . $e->getMessage());
            throw new Exception('Failed to merge PDF files: ' . $e->getMessage());
        }
    }

    /**
     * Compress a PDF using Ghostscript presets.
     *
     * @param string $level 'low' (300dpi / printer), 'medium' (150dpi / ebook), 'high' (72dpi / screen)
     * @return array{original_size: int, result_size: int, reduction_percent: float, duration_ms: float}
     */
    public function compress(string $filePath, string $outputPath, string $level = 'medium'): array
    {
        if (!file_exists($filePath)) {
            throw new Exception('Source PDF file does not exist.');
        }

        $origSize = filesize($filePath);
        $startTime = microtime(true);

        $pdfSetting = match (strtolower($level)) {
            'high' => '/screen',
            'low' => '/printer',
            default => '/ebook', // medium
        };

        // Ghostscript compression command with image downsampling & color compression
        $cmd = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=%s -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellcmd($this->gsPath),
            $pdfSetting,
            escapeshellarg($outputPath),
            escapeshellarg($filePath)
        );

        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
            // If high compression preset failed, fallback to /printer
            $fallbackCmd = sprintf(
                '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/printer -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
                escapeshellcmd($this->gsPath),
                escapeshellarg($outputPath),
                escapeshellarg($filePath)
            );
            exec($fallbackCmd, $fallbackOut, $fallbackCode);

            if ($fallbackCode !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
                throw new Exception('Ghostscript compression failed.');
            }
        }

        $resultSize = filesize($outputPath);

        // If the resulting file ended up larger (e.g. input was already extremely small/compressed),
        // we copy the original file to avoid inflating file size.
        if ($resultSize > $origSize) {
            copy($filePath, $outputPath);
            $resultSize = $origSize;
        }

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        $reduction = $origSize > 0 ? round((($origSize - $resultSize) / $origSize) * 100, 1) : 0;

        return [
            'original_size' => $origSize,
            'result_size' => $resultSize,
            'reduction_percent' => max(0, $reduction),
            'duration_ms' => $elapsed,
        ];
    }

    /**
     * Split / extract pages from a PDF.
     *
     * @param array<int, int> $selectedPages Array of 1-based page numbers
     * @param string $mode 'single' for one combined PDF, 'zip' for separate PDFs in a zip archive
     */
    public function split(string $filePath, array $selectedPages, string $outputPath, string $mode = 'single'): array
    {
        if (!file_exists($filePath)) {
            throw new Exception('Source PDF file does not exist.');
        }

        $totalCount = $this->getPageCount($filePath);
        if ($totalCount <= 0) {
            throw new Exception('Invalid PDF or unable to determine page count.');
        }

        // Filter and sort page numbers
        $pages = array_values(array_unique(array_filter($selectedPages, fn($p) => is_numeric($p) && $p >= 1 && $p <= $totalCount)));
        if (empty($pages)) {
            throw new Exception('Please select at least one valid page to extract.');
        }
        sort($pages);

        // Prepare working PDF (normalize first if necessary)
        $sourceToUse = $filePath;
        $tempNorm = null;
        try {
            $probe = new RotatableFpdi();
            $probe->setSourceFile($filePath);
        } catch (Exception) {
            $tempNorm = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';
            if ($this->normalizePdf($filePath, $tempNorm)) {
                $sourceToUse = $tempNorm;
            }
        }

        if ($mode === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create ZIP archive.');
            }

            $tempDir = storage_path('app/temp_split_' . uniqid());
            File::makeDirectory($tempDir, 0755, true, true);

            foreach ($pages as $p) {
                $pagePdfPath = $tempDir . "/page_{$p}.pdf";
                $pdf = new RotatableFpdi();
                $pdf->setSourceFile($sourceToUse);
                $tpl = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
                $pdf->Output('F', $pagePdfPath);

                $zip->addFile($pagePdfPath, "page_{$p}.pdf");
            }

            $zip->close();
            File::deleteDirectory($tempDir);

            if ($tempNorm && file_exists($tempNorm)) {
                @unlink($tempNorm);
            }

            return [
                'pages_extracted' => count($pages),
                'mode' => 'zip',
                'file_size' => filesize($outputPath),
            ];
        }

        // Single merged PDF with selected pages
        $pdf = new RotatableFpdi();
        $pdf->setSourceFile($sourceToUse);
        foreach ($pages as $p) {
            $tpl = $pdf->importPage($p);
            $size = $pdf->getTemplateSize($tpl);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);
        }
        $pdf->Output('F', $outputPath);

        if ($tempNorm && file_exists($tempNorm)) {
            @unlink($tempNorm);
        }

        return [
            'pages_extracted' => count($pages),
            'mode' => 'single',
            'file_size' => filesize($outputPath),
        ];
    }

    /**
     * Manage PDF: reorder, rotate, delete pages.
     *
     * @param array<int, array{page: int, rotation: int}> $pagesConfig
     */
    public function manage(string $filePath, array $pagesConfig, string $outputPath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception('Source PDF file does not exist.');
        }

        if (empty($pagesConfig)) {
            throw new Exception('No pages specified in the configuration.');
        }

        $sourceToUse = $filePath;
        $tempNorm = null;
        try {
            $probe = new RotatableFpdi();
            $probe->setSourceFile($filePath);
        } catch (Exception) {
            $tempNorm = tempnam(sys_get_temp_dir(), 'norm_') . '.pdf';
            if ($this->normalizePdf($filePath, $tempNorm)) {
                $sourceToUse = $tempNorm;
            }
        }

        $pdf = new RotatableFpdi();
        $pdf->setSourceFile($sourceToUse);

        foreach ($pagesConfig as $cfg) {
            $pageNo = (int) ($cfg['page'] ?? 1);
            $rotation = ((int) ($cfg['rotation'] ?? 0)) % 360;
            if ($rotation < 0) {
                $rotation += 360;
            }

            $tpl = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $w = $size['width'];
            $h = $size['height'];

            if ($rotation === 90 || $rotation === 270) {
                // Swapped dimensions for 90 and 270 degrees
                $orientation = $h > $w ? 'L' : 'P';
                $pdf->AddPage($orientation, [$h, $w]);
                $pdf->Rotate($rotation, $h / 2, $w / 2);
                $pdf->useTemplate($tpl, ($h - $w) / 2, ($w - $h) / 2, $w, $h);
            } else {
                $orientation = $w > $h ? 'L' : 'P';
                $pdf->AddPage($orientation, [$w, $h]);
                if ($rotation === 180) {
                    $pdf->Rotate(180, $w / 2, $h / 2);
                }
                $pdf->useTemplate($tpl, 0, 0, $w, $h);
            }
        }

        $pdf->Output('F', $outputPath);

        if ($tempNorm && file_exists($tempNorm)) {
            @unlink($tempNorm);
        }

        return [
            'total_pages' => count($pagesConfig),
            'file_size' => filesize($outputPath),
        ];
    }

    /**
     * Convert Word document (.docx, .doc, .odt, .rtf) to PDF using local LibreOffice headless.
     *
     * @return array{original_size: int, result_size: int, page_count: int, duration_ms: float}
     */
    public function convertWordToPdf(string $sourcePath, string $outputPath): array
    {
        if (!file_exists($sourcePath)) {
            throw new Exception('Source Word document does not exist.');
        }

        $origSize = filesize($sourcePath);
        $startTime = microtime(true);

        $tempDir = storage_path('app/temp_word_' . uniqid());
        $userProfileDir = $tempDir . '/profile';
        File::makeDirectory($userProfileDir, 0755, true, true);

        // Copy source file with original extension
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'docx';
        $tempInput = $tempDir . '/document.' . $extension;
        copy($sourcePath, $tempInput);

        // Run LibreOffice headless conversion with isolated user profile
        $cmd = sprintf(
            '%s --headless --convert-to pdf --outdir %s -env:UserInstallation=%s %s 2>&1',
            escapeshellcmd($this->libreofficePath),
            escapeshellarg($tempDir),
            escapeshellarg('file://' . $userProfileDir),
            escapeshellarg($tempInput)
        );

        exec($cmd, $output, $code);

        $expectedPdf = $tempDir . '/document.pdf';

        if (!file_exists($expectedPdf) || filesize($expectedPdf) === 0) {
            File::deleteDirectory($tempDir);
            Log::error('Word to PDF conversion failed: ' . implode("\n", $output));
            throw new Exception('Gagal mengonversi file Word ke PDF.');
        }

        copy($expectedPdf, $outputPath);
        File::deleteDirectory($tempDir);

        $resultSize = filesize($outputPath);
        $pageCount = $this->getPageCount($outputPath);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'original_size' => $origSize,
            'result_size' => $resultSize,
            'page_count' => $pageCount,
            'duration_ms' => $duration,
        ];
    }
}

