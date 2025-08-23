<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Charts\PageViewsChart;

class AnalyticsController extends Controller
{
    public function index()
    {
        $chart = new PageViewsChart;

        return view('analytics-dashboard', compact('chart'));
    }
}
