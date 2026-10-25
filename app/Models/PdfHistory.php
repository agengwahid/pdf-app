<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'action',
        'original_name',
        'result_name',
        'file_path',
        'original_size',
        'result_size',
        'page_count',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'original_size' => 'integer',
        'result_size' => 'integer',
        'page_count' => 'integer',
    ];

    protected $appends = [
        'download_url',
        'reduction_percentage',
        'formatted_original_size',
        'formatted_result_size',
    ];

    public function getDownloadUrlAttribute(): string
    {
        return route('pdf.download', ['id' => $this->id]);
    }

    public function getReductionPercentageAttribute(): ?float
    {
        if ($this->original_size > 0 && $this->result_size > 0) {
            $diff = $this->original_size - $this->result_size;
            return round(($diff / $this->original_size) * 100, 1);
        }
        return null;
    }

    public function getFormattedOriginalSizeAttribute(): string
    {
        return $this->formatBytes($this->original_size);
    }

    public function getFormattedResultSizeAttribute(): string
    {
        return $this->formatBytes($this->result_size);
    }

    private function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = log($bytes, 1024);
        $floor = floor($base);
        return round(pow(1024, $base - $floor), $precision) . ' ' . ($units[$floor] ?? 'B');
    }
}
