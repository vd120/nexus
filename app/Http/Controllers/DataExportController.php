<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateDataExport;
use App\Models\DataExportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataExportController extends Controller
{
    public function request(Request $request)
    {
        $user = $request->user();

        // Block if a pending/processing request exists
        $existing = DataExportRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => __('users.export_pending'),
                'status'  => $existing->status,
            ], 422);
        }

        $exportRequest = DataExportRequest::create([
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        GenerateDataExport::dispatch($exportRequest);

        return response()->json([
            'message' => __('users.export_queued'),
        ]);
    }

    public function download(Request $request, string $token)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link.');
        }

        $exportRequest = DataExportRequest::where('download_token', $token)
            ->where('status', 'ready')
            ->firstOrFail();

        if ($exportRequest->isExpired()) {
            $exportRequest->update(['status' => 'expired']);
            abort(410, 'This download link has expired.');
        }

        if (!$exportRequest->file_path || !Storage::exists($exportRequest->file_path)) {
            abort(404, 'Export file not found.');
        }

        return Storage::download(
            $exportRequest->file_path,
            'nexus-data-export.zip'
        );
    }
}
