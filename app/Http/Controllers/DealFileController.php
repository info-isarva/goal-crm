<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Support\RecordAccess;
use App\Support\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DealFileController extends Controller
{
    public function __construct()
    {
        // Prevent creating/editing/deleting deal files when a historical financial year is selected.
        // Use the middleware class directly to avoid alias resolution issues.
        $this->middleware(\App\Http\Middleware\PreventHistoricalFinancialYear::class)->only([
            'store', 'destroy', 'download'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_type' => 'required|in:file upload,file links',
            'file_upload' => 'nullable|file|mimes:' . SafeUpload::documentMimesRule() . '|max:10240',
            'file_link' => 'nullable|string|max:2000',
            'file_name' => 'required|string|max:150',
            'related_id' => 'required|integer',
        ]);

        $relatedId = (int) $request->related_id;
        RecordAccess::authorizeDeal(Auth::user(), $relatedId);

        if ($request->file_type === 'file upload') {
            if ($request->hasFile('file_upload')) {
                $file = $request->file('file_upload');
                $extension = SafeUpload::documentExtension($file);
                if (!$extension) {
                    return redirect()->back()->with('error', 'This file type is not allowed.');
                }

                File::create([
                    'file_type' => $request->file_type,
                    'file_path' => File::storeUpload($file, 'deal', $relatedId, $extension),
                    'file_name' => $request->file_name,
                    'related_type' => 'deal',
                    'related_id' => $relatedId,
                    'uploaded_by' => Auth::id(),
                ]);
            }
        } elseif ($request->file_type === 'file links') {
            if (!empty($request->file_link)) {
                $links = preg_split('/[\n,]+/', $request->file_link);
                $count = 0;
                foreach ($links as $link) {
                    $link = trim($link);
                    if (SafeUpload::isHttpUrl($link)) {
                        File::create([
                            'file_type' => $request->file_type,
                            'file_path' => $link,
                            'file_name' => $request->file_name . ($count > 0 ? ' #' . ($count+1) : ''),
                            'related_type' => 'deal',
                            'related_id' => $relatedId,
                            'uploaded_by' => Auth::id(),
                        ]);
                        $count++;
                    }
                }
            }
        }

        return redirect()->back()->with(['success' => 'File(s) added successfully', 'show_files_tab' => true]);
    }

    public function destroy(File $file)
    {
        RecordAccess::authorizeActivity(Auth::user(), $file);

        $file->update(['deleted_by' => Auth::id()]);
        $file->delete();
        return redirect()->back()->with('success', 'File deleted successfully');
    }

    public function download(File $file)
    {
        RecordAccess::authorizeActivity(Auth::user(), $file);

        if (!$file->isUpload()) {
            return redirect()->back()->with('error', 'Download not available for links.');
        }

        $filePath = $file->absolutePath();
        if (!$filePath) {
            return redirect()->back()->with('error', 'File not found.');
        }

        return response()->download($filePath, SafeUpload::downloadName($file->file_name, $filePath), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
