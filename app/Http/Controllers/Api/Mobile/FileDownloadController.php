<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Support\RecordAccess;
use App\Support\SafeUpload;
use Illuminate\Http\Request;

class FileDownloadController extends Controller
{
    public function download(Request $request, File $file)
    {
        if (!RecordAccess::canAccessActivity($request->user(), $file)) {
            return response()->json(['success' => false, 'message' => 'You do not have access to this file.'], 403);
        }

        if (!$file->isUpload()) {
            return response()->json(['success' => false, 'message' => 'Download not available for links.'], 422);
        }

        $filePath = $file->absolutePath();
        if (!$filePath) {
            return response()->json(['success' => false, 'message' => 'File not found.'], 404);
        }

        return response()->download($filePath, SafeUpload::downloadName($file->file_name, $filePath), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
