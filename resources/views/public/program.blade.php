@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', $program->name.' | '.SiteContent::text('global.company_name'))
@section('og-title', $program->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $program->description), 160))

@section('content')
  @php
    $images = $program->images;
    $whatsapp = SiteContent::whatsappUrl().'?text='.rawurlencode(SiteContent::text('home.program_whatsapp_message').' '.$program->name);
  @endphp

  <section class="program-hero">
    <div class="container">
      <a class="program-back" href="{{ route('home') }}#programs"><span aria-hidden="true">&larr;</span> {{ SiteContent::text('home.program_back') }}</a>
      <div class="program-hero__tags">
        @if ($program->category)
          <span>{{ $program->category->name }}</span>
        @endif
        @if ($program->is_corporate)
          <span class="is-dark">{{ SiteContent::text('home.programs_corporate_label') }}</span>
        @endif
      </div>
      <h1>{{ $program->name }}</h1>
    </div>
  </section>

  <section class="section program-detail">
    <div class="container program-detail__grid">
      <div class="program-detail__main">
        @if ($images->isNotEmpty())
          <div class="program-gallery" data-gallery>
            <img class="program-gallery__main" src="{{ $images->first()->url() }}" alt="{{ $program->name }}" fetchpriority="high" data-gallery-main />
            @if ($images->count() > 1)
              <div class="program-gallery__thumbs">
                @foreach ($images as $image)
                  <button type="button" @class(['is-active' => $loop->first]) data-gallery-thumb="{{ $image->url() }}" aria-label="{{ $program->name }} image {{ $loop->iteration }}">
                    <img src="{{ $image->url() }}" alt="" loading="lazy" />
                  </button>
                @endforeach
              </div>
            @endif
          </div>
        @endif

        <h2>{{ SiteContent::text('home.program_about_title') }}</h2>
        <div class="program-detail__text">
          @if ($program->description)
            {!! nl2br(e($program->description)) !!}
          @else
            <p>{{ SiteContent::multiline('home.program_price_note') }}</p>
          @endif
        </div>
      </div>

      <aside class="program-aside">
        <dl class="program-facts">
          <div>
            <dt>{{ SiteContent::text('home.program_duration_label') }}</dt>
            <dd>{{ $program->duration_days }} {{ (int) $program->duration_days === 1 ? 'day' : 'days' }}</dd>
          </div>
          @if ($program->session_minutes)
            <div>
              <dt>{{ SiteContent::text('home.program_session_label') }}</dt>
              <dd>{{ intdiv($program->session_minutes, 60) ? intdiv($program->session_minutes, 60).' h ' : '' }}{{ $program->session_minutes % 60 ? ($program->session_minutes % 60).' min' : '' }}</dd>
            </div>
          @endif
        </dl>

        <div class="program-ask">
          <h3>{{ SiteContent::text('home.program_price_title') }}</h3>
          <p>{{ SiteContent::multiline('home.program_price_note') }}</p>
          <a class="btn btn--primary" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">{{ SiteContent::text('home.program_whatsapp_button') }}</a>
          <a class="btn btn--secondary" href="{{ route('contact') }}">{{ SiteContent::text('home.program_contact_button') }}</a>
        </div>
      </aside>
    </div>
  </section>

  @if ($others->isNotEmpty())
    <section class="section section--alt">
      <div class="container">
        <div class="section-heading reveal">
          <p class="eyebrow">{{ SiteContent::text('home.programs_eyebrow') }}</p>
        </div>
        <div class="program-grid">
          @foreach ($others as $program)
            @include('public._program-card')
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @include('public._cta')

  <script>
    document.querySelectorAll('[data-gallery]').forEach((gallery) => {
      const main = gallery.querySelector('[data-gallery-main]');
      gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
          main.src = thumb.dataset.galleryThumb;
          gallery.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.classList.toggle('is-active', t === thumb));
        });
      });
    });
  </script>
@endsection
