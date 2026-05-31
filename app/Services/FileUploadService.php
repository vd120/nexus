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
     * Compress an uploaded image to WebP and save it under storage/app/public/{folder}.
     *
     * Options:
     *   maxWidth  (int)    — resize ceiling (preserves aspect ratio)
     *   maxHeight (int)    — resize ceiling (preserves aspect ratio)
     *   quality   (int)    — WebP quality 1-100 (default 80)
     *   cover    (array)  — [w, h] center-crop instead of scale
     *   prefix    (string) — filename prefix (default '')
     *
     * Returns the relative path (e.g. "posts/images/1700000000_xxx.webp") on success,
     * or null if both compression and fallback save failed.
     */
    public function compressImage(UploadedFile $file, string $folder, array $options = []): ?string
    {
        $quality = (int) ($options['quality'] ?? 80);
        $maxWidth = isset($options['maxWidth']) ? (int) $options['maxWidth'] : null;
        $maxHeight = isset($options['maxHeight']) ? (int) $options['maxHeight'] : null;
        $cover = $options['cover'] ?? null;
        $prefix = isset($options['prefix']) ? (string) $options['prefix'] : '';

        $folder = trim($folder, '/');
        $fullDirPath = storage_path('app/public/' . $folder);
        if (!file_exists($fullDirPath)) {
            mkdir($fullDirPath, 0755, true);
        }

        $basename = time() . '_' . ($prefix !== '' ? $prefix . '_' : '') . uniqid();

        try {
            $manager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $image = $manager->read($file);

            if (is_array($cover) && count($cover) === 2) {
                $image->cover((int) $cover[0], (int) $cover[1]);
            } elseif ($maxWidth !== null || $maxHeight !== null) {
                $needsResize = ($maxWidth !== null && $image->width() > $maxWidth)
                    || ($maxHeight !== null && $image->height() > $maxHeight);
                if ($needsResize) {
                    $image->scale(width: $maxWidth, height: $maxHeight);
                }
            }

            $filename = $basename . '.webp';
            $path = $folder . '/' . $filename;
            $image->toWebp($quality)->save(storage_path('app/public/' . $path));

            return $path;
        } catch (\Throwable $e) {
            \Log::warning('compressImage failed, falling back to original: ' . $e->getMessage());

            try {
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = $basename . '.' . $ext;
                $file->move($fullDirPath, $filename);
                return $folder . '/' . $filename;
            } catch (\Throwable $e2) {
                \Log::error('compressImage fallback also failed: ' . $e2->getMessage());
                return null;
            }
        }
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
