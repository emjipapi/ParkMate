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
        $baseFontSize = max(16, $width / 30);
        $smallFontSize = max(12, $width / 40);

        // Get element configuration or use defaults
        $config = $template->element_config ?: $this->getDefaultElementConfig();

        // Add text elements with proper positioning
        $this->addTextElement($image, $user->employee_id ?? $user->student_id ?? $user->id, 
                             $config['user_id'] ?? ['x_percent' => 10, 'y_percent' => 20], 
                             $config['user_id']['font_size'] ?? $baseFontSize, $width, $height);

        $this->addTextElement($image, $user->name, 
                             $config['name'] ?? ['x_percent' => 10, 'y_percent' => 40], 
                             $config['name']['font_size'] ?? $baseFontSize, $width, $height);

        $this->addTextElement($image, $user->department ?? 'General', 
                             $config['department'] ?? ['x_percent' => 10, 'y_percent' => 60], 
                             $config['department']['font_size'] ?? $smallFontSize, $width, $height);

        $this->addTextElement($image, $this->formatExpiryDate($user->parking_permit_expiry ?? now()->addYear()), 
                             $config['expiry'] ?? ['x_percent' => 10, 'y_percent' => 80], 
                             $config['expiry']['font_size'] ?? $smallFontSize, $width, $height);

        // Add custom data if provided
        foreach ($customData as $key => $value) {
            if (isset($config[$key])) {
                $this->addTextElement($image, $value, $config[$key], 
                                    $config[$key]['font_size'] ?? $smallFontSize, $width, $height);
            }
        }

        // Generate filename
        $filename = 'sticker_' . ($user->employee_id ?? $user->student_id ?? $user->id) . '_' . time() . '.png';
        $outputPath = 'generated-stickers/' . $filename;

        // Ensure directory exists
        $outputDir = storage_path('app/public/generated-stickers');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

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
        // Get configuration with defaults
        $xPercent = $config['x_percent'] ?? 10;
        $yPercent = $config['y_percent'] ?? 10;
        $size = $config['font_size'] ?? $fontSize;
        $color = $config['color'] ?? '#000000';
        
        // Convert percentages to actual pixel positions
        $x = ($xPercent * $templateWidth) / 100;
        $y = ($yPercent * $templateHeight) / 100;

        // Determine text alignment based on X position (matches frontend preview)
        $align = 'left';
        if ($xPercent >= 75) {
            $align = 'right';
        } elseif ($xPercent >= 25 && $xPercent <= 74) {
            $align = 'center';
        }

        // Apply text with proper positioning
        $image->text($text, (int)$x, (int)$y, function($font) use ($size, $color, $align) {
            $font->size((int)$size);
            $font->color($color);
            $font->align($align);
            $font->valign('middle');
            
            // Use default font or specify a font file path if needed
            // $font->file(public_path('fonts/arial.ttf'));
        });
    }

    private function getDefaultElementConfig()
    {
        // Default configuration with proper spacing to avoid overlapping
        return [
            'user_id' => [
                'x_percent' => 10, 
                'y_percent' => 20, 
                'font_size' => 18, 
                'color' => '#000000'
            ],
            'name' => [
                'x_percent' => 10, 
                'y_percent' => 40, 
                'font_size' => 16, 
                'color' => '#000000'
            ],
            'department' => [
                'x_percent' => 10, 
                'y_percent' => 60, 
                'font_size' => 14, 
                'color' => '#666666'
            ],
            'expiry' => [
                'x_percent' => 10, 
                'y_percent' => 80, 
                'font_size' => 12, 
                'color' => '#999999'
            ],
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
            return date('M d, Y', strtotime($date));
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }
}