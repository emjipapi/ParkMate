<?php
// app/Services/StickerGeneratorService.php

namespace App\Services;

use App\Models\StickerTemplate;
use App\Models\User;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class StickerGeneratorService
{
    public function generateSticker(StickerTemplate $template, User $user, array $customData = [])
    {
        // Load the template
        $templatePath = storage_path('app/public/' . $template->file_path);
        $image = Image::read($templatePath);

        // Get template dimensions
        $width = $template->width;
        $height = $template->height;

        // Calculate responsive font sizes based on image size
        $baseFontSize = max(12, $width / 40);
        $smallFontSize = max(10, $width / 50);

        // Get element configuration or use defaults
        $config = $template->element_config ?: $this->getDefaultElementConfig();

        // Add text elements with precise positioning
        $this->addTextElement($image, $user->employee_id ?? $user->student_id ?? $user->id, 
                             $config['user_id'] ?? [], $baseFontSize, $width, $height);

        $this->addTextElement($image, $user->name, 
                             $config['name'] ?? ['x_percent' => 10, 'y_percent' => 30], 
                             $baseFontSize, $width, $height);

        $this->addTextElement($image, $user->department ?? 'General', 
                             $config['department'] ?? ['x_percent' => 10, 'y_percent' => 50], 
                             $smallFontSize, $width, $height);

        $this->addTextElement($image, $this->formatExpiryDate($user->parking_permit_expiry ?? now()->addYear()), 
                             $config['expiry'] ?? ['x_percent' => 10, 'y_percent' => 70], 
                             $smallFontSize, $width, $height);

        // Add custom data if provided
        foreach ($customData as $key => $value) {
            if (isset($config[$key])) {
                $this->addTextElement($image, $value, $config[$key], $smallFontSize, $width, $height);
            }
        }

        // Generate filename
        $filename = 'sticker_' . ($user->employee_id ?? $user->student_id ?? $user->id) . '_' . time() . '.png';
        $outputPath = 'generated-stickers/' . $filename;

        // Save the generated sticker
        $image->save(storage_path('app/public/' . $outputPath));

        return $outputPath;
    }

    public function generateBatchStickers(StickerTemplate $template, array $userIds, string $userType = 'all')
    {
        $generatedStickers = [];
        $users = $this->getUsersForBatch($userIds, $userType);

        foreach ($users as $user) {
            try {
                $stickerPath = $this->generateSticker($template, $user);
                $generatedStickers[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'file_path' => $stickerPath,
                    'status' => 'success'
                ];
            } catch (\Exception $e) {
                $generatedStickers[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'file_path' => null,
                    'status' => 'error',
                    'error_message' => $e->getMessage()
                ];
            }
        }

        return $generatedStickers;
    }

    public function createStickerZip(array $stickerPaths, string $zipName = null)
    {
        $zipName = $zipName ?: 'parking_stickers_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/public/downloads/' . $zipName);

        // Ensure downloads directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($stickerPaths as $index => $stickerData) {
                if (isset($stickerData['file_path']) && $stickerData['status'] === 'success') {
                    $fullPath = storage_path('app/public/' . $stickerData['file_path']);
                    if (file_exists($fullPath)) {
                        $zip->addFile($fullPath, basename($stickerData['file_path']));
                    }
                }
            }
            $zip->close();
            return 'downloads/' . $zipName;
        }

        throw new \Exception('Could not create zip file');
    }

    private function addTextElement($image, $text, array $config, $fontSize, $templateWidth, $templateHeight)
    {
        // Calculate position based on percentages with proper alignment
        $xPercent = $config['x_percent'] ?? 10;
        $yPercent = $config['y_percent'] ?? 10;
        
        // Convert percentages to actual pixel positions
        $x = ($xPercent * $templateWidth) / 100;
        $y = ($yPercent * $templateHeight) / 100;

        // Use custom font size if specified
        $size = $config['font_size'] ?? $fontSize;
        $color = $config['color'] ?? '#000000';
        $fontFile = $config['font_file'] ?? null;

        // Determine text alignment based on x position
        $align = 'left';
        if ($xPercent >= 80) {
            $align = 'right';
        } elseif ($xPercent >= 40 && $xPercent <= 60) {
            $align = 'center';
        }

        $image->text($text, (int)$x, (int)$y, function($font) use ($size, $color, $fontFile, $align) {
            $font->size($size);
            $font->color($color);
            $font->align($align); // Set text alignment
            $font->valign('middle'); // Vertical alignment
            if ($fontFile && file_exists($fontFile)) {
                $font->file($fontFile);
            }
        });
    }

    private function getDefaultElementConfig()
    {
        return [
            'user_id' => ['x_percent' => 10, 'y_percent' => 15, 'font_size' => null, 'color' => '#000000'],
            'name' => ['x_percent' => 10, 'y_percent' => 30, 'font_size' => null, 'color' => '#000000'],
            'department' => ['x_percent' => 10, 'y_percent' => 50, 'font_size' => null, 'color' => '#666666'],
            'expiry' => ['x_percent' => 10, 'y_percent' => 70, 'font_size' => null, 'color' => '#999999'],
        ];
    }

    private function getUsersForBatch(array $userIds, string $userType)
    {
        $query = User::whereIn('id', $userIds);

        switch ($userType) {
            case 'employee':
                $query->where('user_type', 'employee');
                break;
            case 'student':
                $query->where('user_type', 'student');
                break;
            // 'all' - no additional filter
        }

        return $query->get();
    }

    private function formatExpiryDate($date)
    {
        if (!$date) {
            return 'No Expiry';
        }
        
        try {
            return date('Y-m-d', strtotime($date));
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }
}