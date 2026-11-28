<?php

namespace App\Http\Controllers;

use App\Models\SiteItem;
use App\Models\TrainingProgram;
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
            'programs' => TrainingProgram::query()
                ->where('is_active', true)
                ->with(['category', 'images' => fn ($query) => $query->orderBy('sort_order')])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    /** Public description of an active training program. Prices are left to the admin, so none are shown. */
    public function program(TrainingProgram $program): View
    {
        abort_unless($program->is_active, 404);

        return view('public.program', [
            'program' => $program->load(['category', 'images' => fn ($query) => $query->orderBy('sort_order')]),
            'others' => TrainingProgram::query()
                ->where('is_active', true)
                ->whereKeyNot($program->id)
                ->with(['category', 'images' => fn ($query) => $query->orderBy('sort_order')])
                ->latest()
                ->limit(3)
                ->get(),
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
