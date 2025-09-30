<?php

namespace App\Jobs;

use App\Models\StickerTemplate;
use App\Services\StickerGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateStickersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $templateId;
    public $numbers;     // array of ints
    public $key;         // unique key used to publish results to cache
    public $initiatorId; // optional user id who started it

    /**
     * Create a new job instance.
     *
     * @param int $templateId
     * @param array $numbers
     * @param string $key
     * @param int|null $initiatorId
     */
    public function __construct(int $templateId, array $numbers, string $key, $initiatorId = null)
    {
        $this->templateId = $templateId;
        $this->numbers = $numbers;
        $this->key = $key;
        $this->initiatorId = $initiatorId;
    }

public function handle(StickerGeneratorService $stickerService)
{
    $lockKey = "sticker_processing:{$this->key}";

    // Acquire lock: only one worker will succeed
    if (! Cache::add($lockKey, true, 3600)) {
        Log::warning("GenerateStickersJob skipped because lock exists: {$this->key}");
        return;
    }

    try {
        Log::info("GenerateStickersJob started: {$this->key}", [
            'numbers_count' => count($this->numbers),
            'template_id'   => $this->templateId,
        ]);

        $template = StickerTemplate::find($this->templateId);
        if (! $template) {
            Cache::put("sticker_generation:{$this->key}", ['error' => 'Template not found'], 60 * 60);
            return;
        }

        // Generate stickers
        $results = $stickerService->generateBatchFromNumbers($template, $this->numbers);

        // Create zip file on disk
        $zipPath = $stickerService->createStickerZip($results);

        // Store success result in cache
        Cache::put("sticker_generation:{$this->key}", ['zip' => $zipPath], 24 * 60 * 60);

        Log::info("GenerateStickersJob finished: {$this->key}", [
            'zip'            => $zipPath,
            'generated_count'=> count($results),
        ]);
    } catch (\Throwable $e) {
        Log::error('GenerateStickersJob failed: ' . $e->getMessage(), [
            'key'       => $this->key,
            'exception' => $e,
        ]);

        Cache::put("sticker_generation:{$this->key}", ['error' => $e->getMessage()], 60 * 60);

        // optional while debugging: rethrow to see full stack trace in the worker console
        // throw $e;
    } finally {
        // Release lock no matter what
        Cache::forget($lockKey);
    }
}

}
