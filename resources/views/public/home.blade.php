@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', SiteContent::text('home.meta_title'))
@section('og-title', SiteContent::text('home.og_title'))
@section('description', SiteContent::text('home.meta_description'))

@section('content')
  <section class="hero" id="home">
    <div class="container hero__grid">
      <div class="hero__content reveal">
        <p class="eyebrow">{{ SiteContent::text('home.hero_eyebrow') }}</p>
        <h1>{{ SiteContent::text('home.hero_title') }}</h1>
        <p class="hero__text">{{ SiteContent::multiline('home.hero_text') }}</p>
        <div class="hero__actions">
          <a class="btn btn--primary" href="{{ route('contact') }}">{{ SiteContent::text('home.hero_primary_button') }}</a>
          <a class="btn btn--secondary" href="{{ route('services') }}">{{ SiteContent::text('home.hero_secondary_button') }}</a>
        </div>
        <div class="hero__badges">
          <span>{{ SiteContent::text('home.hero_badge_1') }}</span>
          <span>{{ SiteContent::text('home.hero_badge_2') }}</span>
          <span>{{ SiteContent::text('home.hero_badge_3') }}</span>
        </div>
      </div>
      <div class="hero__visual reveal reveal--right">
        <img src="{{ SiteContent::image('home.hero_image') }}" alt="{{ SiteContent::text('home.hero_image_alt') }}" loading="lazy" />
      </div>
    </div>
  </section>

  <section class="section" id="about-teaser">
    <div class="container about__grid">
      <div class="about__content reveal">
        <p class="eyebrow">{{ SiteContent::text('home.about_eyebrow') }}</p>
        <h2>{{ SiteContent::text('home.about_title') }}</h2>
        <p>{{ SiteContent::multiline('home.about_text') }}</p>
        <div class="hero__actions">
          <a class="btn btn--secondary" href="{{ route('about') }}">{{ SiteContent::text('home.about_button') }}</a>
        </div>
      </div>
      <div class="about__sidebar reveal reveal--right">
        <img src="{{ SiteContent::image('home.about_image') }}" alt="{{ SiteContent::text('home.about_image_alt') }}" loading="lazy" />
      </div>
    </div>
  </section>

  <section class="section section--alt" id="services-teaser">
    <div class="container">
      <div class="section-heading reveal">
        <p class="eyebrow">{{ SiteContent::text('home.services_eyebrow') }}</p>
        <h2>{{ SiteContent::text('home.services_title') }}</h2>
      </div>
      <div class="services-grid">
        @foreach ($homeServices as $service)
          <article class="service-card reveal">
            <div class="service-card__icon">{{ $service->icon }}</div>
            <h3>{{ $service->title }}</h3>
            <p>{{ $service->body }}</p>
          </article>
        @endforeach
      </div>
      <div class="hero__actions" style="justify-content:center;margin-top:40px;">
        <a class="btn btn--primary" href="{{ route('services') }}">{{ SiteContent::text('home.services_button') }}</a>
      </div>
    </div>
  </section>

  @if ($testimonials->isNotEmpty())
    <section class="section" id="testimonials">
      <div class="container">
        <div class="section-heading reveal">
          <p class="eyebrow">{{ SiteContent::text('home.testimonials_eyebrow') }}</p>
          <h2>{{ SiteContent::text('home.testimonials_title') }}</h2>
          <p class="testimonial-source">
            {{ SiteContent::text('home.testimonials_source') }}
            <a href="{{ SiteContent::text('home.testimonials_source_link_url') }}" target="_blank" rel="noopener noreferrer">{{ SiteContent::text('home.testimonials_source_link_label') }}</a>
          </p>
        </div>
        <div class="testimonial-slider reveal">
          @foreach ($testimonials as $testimonial)
            <article @class(['testimonial-card', 'active' => $loop->first])>
              @if ($testimonial->imageUrl())
                <img src="{{ $testimonial->imageUrl() }}" alt="{{ $testimonial->title }}" />
              @endif
              <p>&ldquo;{{ $testimonial->body }}&rdquo;</p>
              <strong>{{ $testimonial->title }}</strong>
              @if ($testimonial->subtitle)
                <span>{{ $testimonial->subtitle }}</span>
              @endif
              @if ($testimonial->href())
                <p class="testimonial-source-label">{{ SiteContent::text('home.testimonials_review_label') }}</p>
                <a class="testimonial-link" href="{{ $testimonial->href() }}" target="_blank" rel="noopener noreferrer">{{ SiteContent::text('home.testimonials_review_link_label') }}</a>
              @endif
            </article>
          @endforeach
          <div class="dots" aria-label="Testimonial navigation"></div>
        </div>
      </div>
    </section>
  @endif

  @if ($stats->isNotEmpty())
    <section class="section stats-section">
      <div class="container stats-grid">
        @foreach ($stats as $stat)
          <div class="stat reveal">
            <strong data-count="{{ $stat->number }}">0</strong>
            <span>{{ $stat->title }}</span>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  @include('public._cta')
@endsection
