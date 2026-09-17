<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function services(): View
    {
        return view('public.services');
    }

    public function portfolio(): View
    {
        return view('public.portfolio');
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
