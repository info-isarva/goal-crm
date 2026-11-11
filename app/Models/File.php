<?php

namespace App\Models;

use App\Support\SafeUpload;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    /** Uploads are stored on the private "local" disk below this folder. */
    public const PRIVATE_DIR = 'crm_files';

    protected $table = 'files';

    protected $fillable = [
        'file_type',
        'file_path',
        'file_name',
        'related_type',
        'related_id',
        'user_restored_id',
        'user_owner_id',
        'user_assigned_id',
        'uploaded_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isUpload(): bool
    {
        return $this->file_type === 'file upload';
    }

    /**
     * Store an uploaded attachment privately and return the path to save in file_path.
     */
    public static function storeUpload(UploadedFile $file, string $relatedType, int $relatedId, string $extension): string
    {
        $dir = self::PRIVATE_DIR . '/' . ($relatedType === 'deal' ? 'deal' : 'lead') . '/' . $relatedId;

        return Storage::disk('local')->putFileAs($dir, $file, SafeUpload::randomName($extension));
    }

    /**
     * Store raw attachment contents privately and return the path to save in file_path.
     */
    public static function storeContents(string $contents, string $relatedType, int $relatedId, string $extension): string
    {
        $path = self::PRIVATE_DIR . '/' . ($relatedType === 'deal' ? 'deal' : 'lead') . '/' . $relatedId . '/' . SafeUpload::randomName($extension);
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    /**
     * Absolute path of an uploaded file on disk (private storage, or legacy public locations).
     */
    public function absolutePath(): ?string
    {
        if (!$this->isUpload() || !$this->file_path) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', (string) $this->file_path), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $candidates = str_starts_with($relative, self::PRIVATE_DIR . '/')
            ? [Storage::disk('local')->path($relative)]
            : [public_path($relative), storage_path('app/public/' . $relative)];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function downloadUrl(): string
    {
        if (!$this->isUpload()) {
            return SafeUpload::isHttpUrl($this->file_path) ? (string) $this->file_path : '#';
        }

        return $this->related_type === 'deal'
            ? route('deal-files.download', $this->id)
            : route('lead-files.download', $this->id);
    }

    public function deleteFromDisk(): void
    {
        $path = $this->absolutePath();
        if ($path) {
            @unlink($path);
        }
    }
}
