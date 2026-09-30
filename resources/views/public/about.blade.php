@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', SiteContent::text('about.meta_title'))
@section('description', SiteContent::text('about.meta_description'))

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">{{ SiteContent::text('about.hero_eyebrow') }}</p>
      <h1>{{ SiteContent::text('about.hero_title') }}</h1>
      <p>{{ SiteContent::multiline('about.hero_text') }}</p>
    </div>
  </section>

  <section class="section">
    <div class="container about__grid">
      <div class="about__content reveal">
        <div class="about__cards">
          <article>
            <h3>{{ SiteContent::text('about.vision_title') }}</h3>
            <p>{{ SiteContent::multiline('about.vision_text') }}</p>
          </article>
          <article>
            <h3>{{ SiteContent::text('about.mission_title') }}</h3>
            <p>{{ SiteContent::multiline('about.mission_text') }}</p>
          </article>
          <article>
            <h3>{{ SiteContent::text('about.values_title') }}</h3>
            <p>{{ SiteContent::multiline('about.values_text') }}</p>
          </article>
        </div>
      </div>
      <div class="about__sidebar reveal reveal--right">
        <img src="{{ SiteContent::image('about.image') }}" alt="{{ SiteContent::text('about.image_alt') }}" loading="lazy" />
        @if ($timeline->isNotEmpty())
          <div class="timeline">
            <h3>{{ SiteContent::text('about.timeline_title') }}</h3>
            <ul>
              @foreach ($timeline as $milestone)
                <li><strong>{{ $milestone->title }}</strong> {{ $milestone->body }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>
    </div>
  </section>

  @include('public._cta')
@endsection
