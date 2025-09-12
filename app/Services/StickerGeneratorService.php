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
    
public function generateStickerFromNumber(StickerTemplate $template, int $number)
{
    $template->refresh();
    $templatePath = storage_path('app/public/' . $template->file_path);
    $image = Image::read($templatePath);

    $width = $template->width;
    $height = $template->height;

    $config = $template->element_config ?: $this->getDefaultElementConfig();

    // Just put the number where user_id normally is
$this->addTextElement(
    $image, 
    str_pad($number, 4, '0', STR_PAD_LEFT),
    $config['int'] ?? ['x_percent' => 10, 'y_percent' => 20],
    $config['int']['font_size'] ?? 18,
    $width,
    $height
);

    $filename = 'sticker_' . $number . '_' . time() . '.png';
    $outputPath = 'generated-stickers/' . $filename;

    $outputDir = storage_path('app/public/generated-stickers');
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $image->save(storage_path('app/public/' . $outputPath));

    return $outputPath;
}

public function generateBatchFromNumbers(StickerTemplate $template, array $numbers)
{
    $generated = [];
    foreach ($numbers as $num) {
        try {
            $path = $this->generateStickerFromNumber($template, $num);
            $generated[] = [
                'number' => $num,
                'file_path' => $path,
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            $generated[] = [
                'number' => $num,
                'file_path' => null,
                'status' => 'error',
                'error_message' => $e->getMessage()
            ];
        }
    }
    return $generated;
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

// In your StickerGeneratorService.php, update the addTextElement method:

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
        $gdFontSize = round($size * 1.333); // enlarge for GD
        $font->file(public_path('fonts/Inter-Regular.ttf'));
        
        // REMOVE THIS LINE: $font->size(round((int)$size * 0.75));
        // REPLACE WITH:
        $font->size($gdFontSize);
        
        $font->color($color);
        $font->align($align);
        $font->valign('middle');
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