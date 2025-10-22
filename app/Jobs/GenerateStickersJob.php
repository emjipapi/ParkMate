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
use Illuminate\Support\Facades\DB;

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
// --- start: DB update with debugging logs ---
$numbers = array_map('intval', $this->numbers);
Log::info("Sticker update: preparing to update vehicles", [
    'template_id' => $this->templateId,
    'numbers' => $numbers,
    'job_key' => $this->key,
]);

if (! empty($numbers)) {
    try {
        // create placeholders for parameter binding
        $placeholders = implode(',', array_fill(0, count($numbers), '?'));

        // For serial format like "S0004" (one prefix char), substring from 2
        $sqlWhere = "CAST(SUBSTRING(serial_number, 2) AS UNSIGNED) IN ($placeholders)";

        // 1) SELECT matched rows to inspect why matching may fail
        $selectSql = "SELECT id, serial_number FROM vehicles WHERE {$sqlWhere} LIMIT 1000";
        $matched = DB::select($selectSql, $numbers);

        Log::info("Sticker update: matched vehicles (sample)", [
            'matched_count' => count($matched),
            // map to simple array for readability
            'samples' => array_map(function($r){ return ['id' => $r->id, 'serial' => $r->serial_number]; }, array_slice($matched, 0, 10)),
        ]);

        // 2) Perform the update and log affected rows
        $affected = DB::table('vehicles')
            ->whereRaw($sqlWhere, $numbers)
            ->update([
                'sticker_template_id' => $this->templateId,
                'updated_at' => now(),
            ]);

        Log::info("Sticker update: update completed", [
            'affected_rows' => $affected,
            'template_id' => $this->templateId,
            'numbers_count' => count($numbers),
        ]);
    } catch (\Throwable $ex) {
        Log::error("Sticker update: exception during DB update", [
            'message' => $ex->getMessage(),
            'trace'   => $ex->getTraceAsString(),
            'numbers' => $numbers,
            'template_id' => $this->templateId,
        ]);
    }
} else {
    Log::info("Sticker update: no numbers provided, skipping DB update", [
        'job_key' => $this->key,
    ]);
}
// --- end: DB update with debugging logs ---

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
