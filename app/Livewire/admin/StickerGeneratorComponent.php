<?php
// app/Livewire/Admin/StickerGeneratorComponent.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\StickerTemplate;

class StickerGeneratorComponent extends Component
{
    public $activeTab = 'generate';
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.admin.sticker-generator-component');
    }
}