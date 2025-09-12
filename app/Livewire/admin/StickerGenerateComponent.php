<?php
// app/Livewire/Admin/StickerGenerateComponent.php (Updated)

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\StickerTemplate;
use App\Models\User;
use App\Services\StickerGeneratorService;
use Illuminate\Support\Facades\Storage;

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
    $this->validate([
        'selectedTemplateId' => 'required|exists:sticker_templates,id',
        'numberRange' => 'required|string'
    ]);

    $this->isGenerating = true;

    try {
        $template = StickerTemplate::find($this->selectedTemplateId);

        // Parse numbers from input
        $numbers = $this->parseNumberRange($this->numberRange);

        if (empty($numbers)) {
            session()->flash('error', 'No valid numbers found.');
            $this->isGenerating = false;
            return;
        }

        // Generate stickers (no user model now)
        $results = $this->stickerService->generateBatchFromNumbers($template, $numbers);

        $zipPath = $this->stickerService->createStickerZip($results);
        $this->lastGeneratedZip = $zipPath;

        session()->flash('success', "Generated " . count($results) . " stickers successfully!");
    } catch (\Exception $e) {
        session()->flash('error', 'Error generating stickers: ' . $e->getMessage());
    }

    $this->isGenerating = false;
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


    public function downloadStickers()
    {
        if ($this->lastGeneratedZip && Storage::disk('public')->exists($this->lastGeneratedZip)) {
            return response()->download(storage_path('app/public/' . $this->lastGeneratedZip));
        }
        
        session()->flash('error', 'No stickers available for download.');
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