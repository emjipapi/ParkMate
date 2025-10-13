<?php
// app/Livewire/Admin/StickerGeneratorComponent.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\StickerTemplate;
use Illuminate\Support\Facades\Auth;
class StickerGeneratorComponent extends Component
{
    public $activeTab = 'generate';
    
    public function mount()
    {
        $permissions = json_decode(Auth::guard('admin')->user()->permissions ?? '[]', true);

        if (in_array('generate_sticker', $permissions)) {
            $this->activeTab = 'generate';
        } elseif (in_array('manage_sticker', $permissions)) {
            $this->activeTab = 'manage';
        } else {
            // fallback if user has none of the three
            $this->activeTab = null;
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.sticker-generator-component');
    }
}