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

    protected $stickerService;

    public function boot(StickerGeneratorService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

    public function mount()
    {
        $firstTemplate = StickerTemplate::active()->first();
        $this->selectedTemplateId = $firstTemplate?->id;
    }

    public function generateStickers()
    {
        $this->validate([
            'selectedTemplateId' => 'required|exists:sticker_templates,id',
            'quantity' => 'required_if:generationMode,quantity|integer|min:1|max:1000',
        ]);

        $this->isGenerating = true;

        try {
            $template = StickerTemplate::find($this->selectedTemplateId);
            
            // Get users based on generation mode
            $users = $this->getUsersForGeneration();
            
            if ($users->isEmpty()) {
                session()->flash('error', 'No users found matching your criteria.');
                $this->isGenerating = false;
                return;
            }

            // Generate stickers
            $results = $this->stickerService->generateBatchStickers(
                $template, 
                $users->pluck('id')->toArray(), 
                $this->userType
            );

            // Create downloadable zip
            $zipPath = $this->stickerService->createStickerZip($results);
            $this->lastGeneratedZip = $zipPath;

            $successCount = collect($results)->where('status', 'success')->count();
            $errorCount = collect($results)->where('status', 'error')->count();

            session()->flash('success', 
                "Generated {$successCount} stickers successfully!" . 
                ($errorCount > 0 ? " ({$errorCount} failed)" : "")
            );

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating stickers: ' . $e->getMessage());
        }

        $this->isGenerating = false;
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
        $templates = StickerTemplate::active()->get();
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