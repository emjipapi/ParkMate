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
        try {
            $template = StickerTemplate::find($this->templateId);
            if (! $template) {
                Cache::put("sticker_generation:{$this->key}", ['error' => 'Template not found'], 60*60);
                return;
            }

            // actual generation (use your service)
            $results = $stickerService->generateBatchFromNumbers($template, $this->numbers);

            // create zip (should return storage path like 'stickers/xyz.zip')
            $zipPath = $stickerService->createStickerZip($results);

            // store success result in cache (keep for 24 hours)
            Cache::put("sticker_generation:{$this->key}", ['zip' => $zipPath], 24*60*60);
        } catch (\Throwable $e) {
            Log::error('GenerateStickersJob failed: ' . $e->getMessage(), [
                'key' => $this->key, 'exception' => $e
            ]);

            Cache::put("sticker_generation:{$this->key}", ['error' => $e->getMessage()], 60*60);
        }
    }
}
