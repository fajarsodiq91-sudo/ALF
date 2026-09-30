<?php

namespace App\Http\Controllers;

use App\Models\SiteItem;
use App\Services\MasterData;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'homeServices' => SiteItem::active('home_service'),
            'testimonials' => SiteItem::active('testimonial'),
            'stats' => SiteItem::active('stat'),
        ]);
    }

    public function about(): View
    {
        return view('public.about', ['timeline' => SiteItem::active('timeline')]);
    }

    public function services(): View
    {
        return view('public.services', [
            'services' => SiteItem::active('service'),
            'trainings' => SiteItem::active('training'),
        ]);
    }

    public function portfolio(): View
    {
        $projects = SiteItem::active('portfolio');

        return view('public.portfolio', [
            'projects' => $projects,
            // Filter buttons follow the Master Data order, and only for categories that still have a project.
            'categories' => collect(MasterData::options('portfolio_category'))->only($projects->pluck('category')->unique()->all()),
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    public function thankYou(): View
    {
        return view('public.thank-you');
    }
}
