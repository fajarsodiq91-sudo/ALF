@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', SiteContent::text('services.meta_title'))
@section('description', SiteContent::text('services.meta_description'))

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">{{ SiteContent::text('services.hero_eyebrow') }}</p>
      <h1>{{ SiteContent::text('services.hero_title') }}</h1>
      <p>{{ SiteContent::multiline('services.hero_text') }}</p>
    </div>
  </section>

  <section class="section" id="services">
    <div class="container">
      <div class="services-grid">
        @foreach ($services as $service)
          <article class="service-card reveal">
            <div class="service-card__icon">{{ $service->icon }}</div>
            <h3>{{ $service->title }}</h3>
            <p>{{ $service->body }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="section section--alt" id="training">
    <div class="container">
      <div class="section-heading reveal">
        <p class="eyebrow">{{ SiteContent::text('services.training_eyebrow') }}</p>
        <h2>{{ SiteContent::text('services.training_title') }}</h2>
      </div>
      <div class="training-grid">
        @foreach ($trainings as $training)
          <article class="training-card reveal">
            <h3>{{ $training->title }}</h3>
            <p>{{ $training->body }}</p>
            <a href="{{ route('contact') }}" class="link-btn">{{ SiteContent::text('services.training_button') }}</a>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  @include('public._cta')
@endsection
