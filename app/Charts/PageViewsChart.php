<?php
namespace App\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use App\Models\PageView;
use Illuminate\Support\Facades\DB;

class PageViewsChart extends Chart
{
    public function __construct()
    {
        parent::__construct();

        // Aggregate page views
        $data = PageView::select('url', DB::raw('count(*) as views'))
            ->groupBy('url')
            ->orderBy('views', 'desc')
            ->get();

        $this->labels($data->pluck('url')->toArray());
        $this->dataset('Page Views', 'bar', $data->pluck('views')->toArray())
             ->backgroundColor('rgba(54, 162, 235, 0.7)')
             ->color('rgba(54, 162, 235, 1)');
    }
}
