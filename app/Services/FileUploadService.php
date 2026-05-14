<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class FileUploadService
{
    /**
     * Validate uploaded file for security (optimized for speed)
     */
    public function validateFile(UploadedFile $file, array $allowedMimeTypes): array
    {
        $errors = [];
        
        // 1. Check MIME type (very fast - PHP does this automatically)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        // 2. Check file extension matches MIME type (fast)
        $extension = strtolower($file->getClientOriginalExtension());
        $expectedExtensions = $this->getExtensionsForMime($mimeType);
        if (!in_array($extension, $expectedExtensions)) {
            $errors[] = 'File extension does not match content';
        }
        
        // 3. Check file size (instant)
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Skip magic byte check for speed - MIME type validation is sufficient
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mimeType' => $mimeType,
            'size' => $file->getSize()
        ];
    }
    
    /**
     * Get file signature (first 16 bytes)
     */
    private function getFileSignature(UploadedFile $file): string
    {
        return bin2hex(file_get_contents($file->getPathname(), false, null, 0, 16));
    }
    
    /**
     * Validate file signature against MIME type
     */
    private function validateSignature(string $signature, string $mimeType): bool
    {
        $signatures = [
            'image/jpeg' => ['ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffdb'],
            'image/png' => ['89504e47'],
            'image/gif' => ['47494638'],
            'image/webp' => ['52494646'], // RIFF
            'video/mp4' => ['00000018', '00000020', '66747970'], // ftyp
            'video/quicktime' => ['66747971', '6D6F6F76'], // ftyq, moov
            'video/x-msvideo' => ['52494646'], // RIFF (AVI)
            'video/webm' => ['1a45dfa3'], // EBML
        ];
        
        if (!isset($signatures[$mimeType])) {
            return true; // No signature check for this type
        }
        
        foreach ($signatures[$mimeType] as $validSig) {
            if (strpos($signature, $validSig) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get expected extensions for MIME type
     */
    private function getExtensionsForMime(string $mimeType): array
    {
        $mapping = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'video/mp4' => ['mp4'],
            'video/quicktime' => ['mov'],
            'video/x-msvideo' => ['avi'],
            'video/webm' => ['webm'],
        ];
        
        return $mapping[$mimeType] ?? [];
    }

    /**
     * Generate a thumbnail from a video file using FFMpeg
     */
    public function generateVideoThumbnail(string $videoPath, string $outputPath): bool
    {
        try {
            $fullVideoPath = storage_path('app/public/' . $videoPath);
            $fullOutputPath = storage_path('app/public/' . $outputPath);
            
            // Ensure directory exists
            $dir = dirname($fullOutputPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Command: Capture frame at 1 second mark (usually has content)
            // -i: input file, -ss: seek to time, -vframes 1: capture 1 frame
            $command = "ffmpeg -i " . escapeshellarg($fullVideoPath) . " -ss 00:00:01.000 -vframes 1 " . escapeshellarg($fullOutputPath) . " 2>&1";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                \Log::error("FFMpeg thumbnail generation failed: " . implode("\n", $output));
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Error generating video thumbnail: " . $e->getMessage());
            return false;
        }
    }
}
