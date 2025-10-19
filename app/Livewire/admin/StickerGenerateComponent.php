<?php
// app/Livewire/Admin/StickerGenerateComponent.php (Updated)

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\StickerTemplate;
use App\Models\User;
use App\Services\StickerGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Jobs\GenerateStickersJob;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
class StickerGenerateComponent extends Component
{
    public $selectedTemplateId;
    public $userType = 'all';
    public $quantity = 1;
    public $preview = false;
    public $selectedUserIds = [];
    public $generationMode = 'quantity'; // 'quantity' or 'users'
    public $isGenerating = false;
    public $lastGeneratedZip = null;
public $numberRange = ''; // e.g. "1,2,5-10"
    protected $stickerService;
        public $generationKey = null;    // track the job key for polling
    public $generationStartedAt = null;
    public $progress = 0;
public $total = 0;
    public function boot(StickerGeneratorService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

public function mount()
{
    $firstTemplate = StickerTemplate::orderBy('created_at', 'asc')->first();
    $this->selectedTemplateId = $firstTemplate?->id;
    $this->templates = StickerTemplate::all();
}


public function generateStickers()
{
    $this->lastGeneratedZip = null;
$this->progress = 0;
$this->total = 0;
    $this->validate([
        'selectedTemplateId' => 'required|exists:sticker_templates,id',
        'numberRange'        => 'required|string'
    ]);

    $numbers = $this->parseNumberRange($this->numberRange);

    if (empty($numbers)) {
        session()->flash('error', 'No valid numbers found.');
        return;
    }

    // Prevent accidental duplicate dispatches per user for 30s
    $userId = auth()->id() ?: 'guest';
    $userLockKey = "sticker_generation_lock:user:{$userId}";
    if (! Cache::add($userLockKey, true, 2)) {
        session()->flash('warning', 'Sticker generation already in progress. Please wait a moment.');
        return;
    }

    $key = 'gen_' . Str::random(12);
    $cacheKey = "sticker_generation:{$key}";
    Cache::put($cacheKey, 'pending', 60*60);

    $this->generationKey = $key;
    $this->isGenerating = true;
    $this->generationStartedAt = now();

    Log::info('Dispatching GenerateStickersJob', [
        'key'           => $key,
        'template_id'   => $this->selectedTemplateId,
        'numbers_count' => count($numbers),
        'initiator'     => $userId,
    ]);

    GenerateStickersJob::dispatch($this->selectedTemplateId, $numbers, $key, $userId)
        ->onQueue('default');
            $template = StickerTemplate::find($this->selectedTemplateId);
    if ($template) {
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => Auth::guard('admin')->id(),
            'area_id'    => null, // set if relevant
            'action'     => 'generate',
            'details'    => 'Admin ' 
                . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname
                . ' started generating stickers for template "' . $template->name . '" '
                . 'with ' . count($numbers) . ' number(s).',
            'created_at' => now(),
        ]);
    }

    session()->flash('success', 'Sticker generation has started — this will run in background.');
}



    /**
     * Called by the front-end periodically (wire:poll) to check for completion.
     */
public function checkGenerationStatus()
{
    if (! $this->generationKey) {
        return;
    }

    $progressKey = "stickers_progress_{$this->generationKey}";
    $resultKey   = "sticker_generation:{$this->generationKey}";

    // progress updates
    $data = Cache::get($progressKey);
    if ($data) {
        $this->progress = $data['current'] ?? 0;
        $this->total = $data['total'] ?? 0;
    }

    // check final result (zip or error)
    $result = Cache::get($resultKey);
    if ($result === null) {
        return; // still pending
    }

    if (is_array($result) && isset($result['error'])) {
        $this->isGenerating = false;
        $this->lastGeneratedZip = null;
        session()->flash('error', 'Sticker generation failed: ' . $result['error']);
        Cache::forget($resultKey);
        Cache::forget($progressKey);
        $this->generationKey = null;
        return;
    }

    if (is_array($result) && isset($result['zip'])) {
        $this->isGenerating = false;
        $this->lastGeneratedZip = $result['zip'];
        // finalize progress if missing
        $this->progress = $this->total ?: ($this->progress ?: 0);
        // optionally remove cache entries after some time
        // Cache::forget($resultKey);
        // Cache::forget($progressKey);
        $this->generationKey = null;
        session()->flash('success', 'Stickers are ready for download.');
    }
}


private function parseNumberRange($input)
{
    $numbers = [];

    foreach (explode(',', $input) as $part) {
        $part = trim($part);
        if (preg_match('/^(\d+)-(\d+)$/', $part, $matches)) {
            $start = (int)$matches[1];
            $end = (int)$matches[2];
            if ($start <= $end) {
                $numbers = array_merge($numbers, range($start, $end));
            }
        } elseif (is_numeric($part)) {
            $numbers[] = (int)$part;
        }
    }

    return array_unique($numbers);
}


public function getDownloadUrlProperty()
{
    if (!$this->lastGeneratedZip) {
        return null;
    }
    
    return route('stickers.download', ['filename' => basename($this->lastGeneratedZip)]);
}

    public function togglePreview()
    {
        $this->preview = !$this->preview;
    }

    private function getUsersForGeneration()
    {
        if ($this->generationMode === 'users' && !empty($this->selectedUserIds)) {
            return User::whereIn('id', $this->selectedUserIds)->get();
        }

        // Get users based on type and quantity
        $query = User::query();

        switch ($this->userType) {
            case 'employee':
                $query->where('user_type', 'employee');
                break;
            case 'student':
                $query->where('user_type', 'student');
                break;
        }

        return $query->limit($this->quantity)->get();
    }
    

    public function render()
    {
         $templates = StickerTemplate::all();
        $selectedTemplate = $this->selectedTemplateId 
            ? StickerTemplate::find($this->selectedTemplateId) 
            : null;

        $users = User::when($this->userType !== 'all', function($query) {
                return $query->where('user_type', $this->userType);
            })
            ->limit(100)
            ->get();

        return view('livewire.admin.sticker-generate-component', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'users' => $users
        ]);
    }
}