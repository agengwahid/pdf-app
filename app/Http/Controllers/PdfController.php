<?php

namespace App\Http\Controllers;

use App\Models\PdfHistory;
use App\Services\PdfService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PdfController extends Controller
{
    protected PdfService $pdfService;
    protected string $storageDir;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
        $this->storageDir = storage_path('app/pdf_studio');
        if (!File::exists($this->storageDir)) {
            File::makeDirectory($this->storageDir, 0755, true, true);
        }
    }

    /**
     * Display the main application interface.
     */
    public function index(Request $request): Response
    {
        $sessionId = $request->session()->getId() ?: 'default_session';

        $history = PdfHistory::query()
            ->where('session_id', $sessionId)
            ->orWhereNull('session_id')
            ->latest()
            ->take(20)
            ->get();

        return Inertia::render('PdfStudio', [
            'initialTab' => $request->query('tab', 'merge'),
            'history' => $history,
        ]);
    }

    /**
     * Generate visual page thumbnails for a given uploaded PDF.
     */
    public function renderThumbnails(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400', // max 100MB
            'max_pages' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $uploaded = $request->file('file');
            $tempPath = $uploaded->getRealPath();
            $maxPages = (int) $request->input('max_pages', 60);

            $pageCount = $this->pdfService->getPageCount($tempPath);
            $thumbnails = $this->pdfService->generateThumbnails($tempPath, $maxPages);

            return response()->json([
                'success' => true,
                'page_count' => $pageCount,
                'thumbnails' => $thumbnails,
                'original_name' => $uploaded->getClientOriginalName(),
                'original_size' => $uploaded->getSize(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render page previews: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Merge multiple PDF files.
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|min:2',
            'files.*' => 'required|file|mimes:pdf|max:102400',
            'custom_name' => 'nullable|string|max:120',
        ]);

        try {
            $uploadedFiles = $request->file('files');
            $inputPaths = [];
            $originalNames = [];
            $totalOrigSize = 0;

            foreach ($uploadedFiles as $file) {
                $inputPaths[] = $file->getRealPath();
                $originalNames[] = $file->getClientOriginalName();
                $totalOrigSize += $file->getSize();
            }

            $customName = trim($request->input('custom_name', ''));
            $baseName = $customName ? Str::slug(pathinfo($customName, PATHINFO_FILENAME)) : 'merged_' . date('Ymd_His');
            $resultFilename = $baseName . '.pdf';
            $outputPath = $this->storageDir . '/' . Str::random(16) . '_' . $resultFilename;

            $this->pdfService->merge($inputPaths, $outputPath);

            $resultSize = filesize($outputPath);
            $pageCount = $this->pdfService->getPageCount($outputPath);

            $history = PdfHistory::create([
                'session_id' => $request->session()->getId() ?: 'default_session',
                'action' => 'merge',
                'original_name' => implode(', ', array_slice($originalNames, 0, 3)) . (count($originalNames) > 3 ? ' (+' . (count($originalNames) - 3) . ' more)' : ''),
                'result_name' => $resultFilename,
                'file_path' => $outputPath,
                'original_size' => $totalOrigSize,
                'result_size' => $resultSize,
                'page_count' => $pageCount,
                'meta' => [
                    'file_count' => count($uploadedFiles),
                    'sources' => $originalNames,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully merged ' . count($uploadedFiles) . ' PDF files.',
                'result_name' => $resultFilename,
                'result_size' => $resultSize,
                'page_count' => $pageCount,
                'download_url' => $history->download_url,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Merge failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compress a single PDF.
     */
    public function compress(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
            'level' => 'required|string|in:low,medium,high',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $level = $request->input('level', 'medium');

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $resultFilename = $baseName . '_compressed_' . $level . '.pdf';
            $outputPath = $this->storageDir . '/' . Str::random(16) . '_' . $resultFilename;

            $stats = $this->pdfService->compress($file->getRealPath(), $outputPath, $level);
            $pageCount = $this->pdfService->getPageCount($outputPath);

            $history = PdfHistory::create([
                'session_id' => $request->session()->getId() ?: 'default_session',
                'action' => 'compress',
                'original_name' => $originalName,
                'result_name' => $resultFilename,
                'file_path' => $outputPath,
                'original_size' => $stats['original_size'],
                'result_size' => $stats['result_size'],
                'page_count' => $pageCount,
                'meta' => [
                    'preset' => $level,
                    'reduction_percent' => $stats['reduction_percent'],
                    'duration_ms' => $stats['duration_ms'],
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Compression completed with {$stats['reduction_percent']}% reduction.",
                'original_size' => $stats['original_size'],
                'result_size' => $stats['result_size'],
                'reduction_percent' => $stats['reduction_percent'],
                'formatted_original_size' => $history->formatted_original_size,
                'formatted_result_size' => $history->formatted_result_size,
                'duration_ms' => $stats['duration_ms'],
                'download_url' => $history->download_url,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Compression failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Split a PDF by page ranges or page numbers.
     */
    public function split(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
            'pages' => 'nullable|array',
            'range' => 'nullable|string',
            'mode' => 'required|string|in:single,zip',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $mode = $request->input('mode', 'single');

            // Parse pages array or range string
            $pageNumbers = $request->input('pages', []);
            $rangeString = trim($request->input('range', ''));

            if (!empty($rangeString)) {
                $pageNumbers = array_merge($pageNumbers, $this->parseRangeString($rangeString));
            }

            $pageNumbers = array_values(array_unique(array_filter($pageNumbers, fn($p) => is_numeric($p) && (int)$p >= 1)));
            if (empty($pageNumbers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide at least one valid page number or range (e.g. 1-3, 5).',
                ], 422);
            }

            $extension = $mode === 'zip' ? 'zip' : 'pdf';
            $resultFilename = $baseName . '_split_' . ($mode === 'zip' ? 'archive' : 'pages') . '.' . $extension;
            $outputPath = $this->storageDir . '/' . Str::random(16) . '_' . $resultFilename;

            $stats = $this->pdfService->split($file->getRealPath(), $pageNumbers, $outputPath, $mode);

            $history = PdfHistory::create([
                'session_id' => $request->session()->getId() ?: 'default_session',
                'action' => 'split',
                'original_name' => $originalName,
                'result_name' => $resultFilename,
                'file_path' => $outputPath,
                'original_size' => $file->getSize(),
                'result_size' => $stats['file_size'],
                'page_count' => count($pageNumbers),
                'meta' => [
                    'mode' => $mode,
                    'pages_extracted' => $stats['pages_extracted'],
                    'selected_pages' => $pageNumbers,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully extracted {$stats['pages_extracted']} pages.",
                'result_name' => $resultFilename,
                'result_size' => $stats['file_size'],
                'pages_extracted' => $stats['pages_extracted'],
                'download_url' => $history->download_url,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Split failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Manage PDF: reorder, rotate, delete pages.
     */
    public function manage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:102400',
            'pages_config' => 'required', // json string or array
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);

            $pagesConfig = $request->input('pages_config');
            if (is_string($pagesConfig)) {
                $pagesConfig = json_decode($pagesConfig, true);
            }

            if (!is_array($pagesConfig) || empty($pagesConfig)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pages configured or all pages were deleted.',
                ], 422);
            }

            $resultFilename = $baseName . '_reordered.pdf';
            $outputPath = $this->storageDir . '/' . Str::random(16) . '_' . $resultFilename;

            $stats = $this->pdfService->manage($file->getRealPath(), $pagesConfig, $outputPath);

            $history = PdfHistory::create([
                'session_id' => $request->session()->getId() ?: 'default_session',
                'action' => 'manage',
                'original_name' => $originalName,
                'result_name' => $resultFilename,
                'file_path' => $outputPath,
                'original_size' => $file->getSize(),
                'result_size' => $stats['file_size'],
                'page_count' => $stats['total_pages'],
                'meta' => [
                    'pages_count' => $stats['total_pages'],
                    'config' => $pagesConfig,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully saved PDF with {$stats['total_pages']} pages.",
                'result_name' => $resultFilename,
                'result_size' => $stats['file_size'],
                'total_pages' => $stats['total_pages'],
                'download_url' => $history->download_url,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Manage operation failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Securely download generated PDF or archive.
     */
    public function download(int $id): BinaryFileResponse|JsonResponse
    {
        $history = PdfHistory::findOrFail($id);

        if (!File::exists($history->file_path)) {
            return response()->json([
                'error' => 'The requested file has expired or was removed from local storage.',
            ], 404);
        }

        $mime = Str::endsWith($history->result_name, '.zip') ? 'application/zip' : 'application/pdf';

        return response()->download($history->file_path, $history->result_name, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Delete a single history record and file.
     */
    public function deleteHistory(int $id): JsonResponse
    {
        $history = PdfHistory::findOrFail($id);
        if (File::exists($history->file_path)) {
            @unlink($history->file_path);
        }
        $history->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Clear all history records and files.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId() ?: 'default_session';
        $records = PdfHistory::query()->where('session_id', $sessionId)->orWhereNull('session_id')->get();

        foreach ($records as $item) {
            if (File::exists($item->file_path)) {
                @unlink($item->file_path);
            }
            $item->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Helper to parse range strings like "1-4, 7, 9-12" into array of page numbers.
     *
     * @return array<int, int>
     */
    protected function parseRangeString(string $rangeStr): array
    {
        $pages = [];
        $parts = explode(',', $rangeStr);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (str_contains($part, '-')) {
                $sub = explode('-', $part, 2);
                $start = (int) trim($sub[0]);
                $end = (int) trim($sub[1]);
                if ($start > 0 && $end >= $start && ($end - $start) < 500) {
                    for ($i = $start; $i <= $end; $i++) {
                        $pages[] = $i;
                    }
                }
            } elseif (is_numeric($part)) {
                $p = (int) $part;
                if ($p > 0) {
                    $pages[] = $p;
                }
            }
        }

        return $pages;
    }

    /**
     * Convert Word document (.docx, .doc, .odt, .rtf) to PDF.
     */
    public function wordToPdf(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['docx', 'doc', 'odt', 'rtf'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format file tidak didukung. Harap unggah file dokumen Word (.docx, .doc, .odt, .rtf).',
                ], 422);
            }

            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $cleanName = Str::slug($baseName) ?: 'dokumen';
            $resultFilename = $cleanName . '.pdf';
            $outputPath = $this->storageDir . '/' . Str::random(16) . '_' . $resultFilename;

            $stats = $this->pdfService->convertWordToPdf($file->getRealPath(), $outputPath);

            $history = PdfHistory::create([
                'session_id' => $request->session()->getId() ?: 'default_session',
                'action' => 'word_to_pdf',
                'original_name' => $originalName,
                'result_name' => $resultFilename,
                'file_path' => $outputPath,
                'original_size' => $stats['original_size'],
                'result_size' => $stats['result_size'],
                'page_count' => $stats['page_count'],
                'meta' => [
                    'duration_ms' => $stats['duration_ms'],
                    'format' => $ext,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Dokumen Word berhasil dikonversi ke PDF ({$stats['page_count']} halaman).",
                'result_name' => $resultFilename,
                'result_size' => $stats['result_size'],
                'page_count' => $stats['page_count'],
                'duration_ms' => $stats['duration_ms'],
                'download_url' => $history->download_url,
                'history' => $history,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Konversi gagal: ' . $e->getMessage(),
            ], 422);
        }
    }
}

