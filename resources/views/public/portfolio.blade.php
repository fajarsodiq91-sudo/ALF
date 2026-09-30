@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', SiteContent::text('portfolio.meta_title'))
@section('description', SiteContent::text('portfolio.meta_description'))

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">{{ SiteContent::text('portfolio.hero_eyebrow') }}</p>
      <h1>{{ SiteContent::text('portfolio.hero_title') }}</h1>
      <p>{{ SiteContent::multiline('portfolio.hero_text') }}</p>
    </div>
  </section>

  <section class="section" id="portfolio">
    <div class="container">
      <div class="filter-bar reveal" role="tablist" aria-label="Portfolio filters">
        <button class="filter-btn active" data-filter="all">{{ SiteContent::text('portfolio.filter_all') }}</button>
        @foreach ($categories as $code => $label)
          <button class="filter-btn" data-filter="{{ $code }}">{{ $label }}</button>
        @endforeach
      </div>
      <div class="portfolio-grid">
        @foreach ($projects as $project)
          <article class="portfolio-card reveal" data-category="{{ $project->category }}">
            @if ($project->imageUrl())
              <img src="{{ $project->imageUrl() }}" alt="{{ $project->title }}" loading="lazy" />
            @endif
            <div class="portfolio-card__content">
              <h3>{{ $project->title }}</h3>
              <p>{{ $project->body }}</p>
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  @include('public._cta')
@endsection
