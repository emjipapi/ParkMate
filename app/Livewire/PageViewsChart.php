<?php

namespace App\Livewire;

use Livewire\Component;
use \App\Models\PageView;
use Illuminate\Support\Facades\DB;

class PageViewsChart extends Component
{
public $labels = [];
public $data = [];

public function mount()
{
    $pageViews = PageView::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->get();

    $this->labels = array_values($pageViews->pluck('date')->map(fn($d) => (string) $d)->toArray());
    $this->data   = array_values($pageViews->pluck('views')->toArray());
}

    public function render()
    {
        return view('livewire.page-views-chart');
    }
}