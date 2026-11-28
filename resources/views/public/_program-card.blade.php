@use('App\Services\SiteContent')
{{-- A training program card linking to its public detail page. Prices are intentionally not shown. --}}
<a class="program-card reveal" href="{{ route('programs.show', $program) }}">
  <div class="program-card__media">
    @if ($image = $program->images->first())
      <img src="{{ $image->url() }}" alt="{{ $program->name }}" loading="lazy" />
    @else
      <span class="program-card__placeholder" aria-hidden="true">{{ mb_substr($program->name, 0, 1) }}</span>
    @endif
    <div class="program-card__tags">
      @if ($program->category)
        <span>{{ $program->category->name }}</span>
      @endif
      @if ($program->is_corporate)
        <span class="is-dark">{{ SiteContent::text('home.programs_corporate_label') }}</span>
      @endif
    </div>
  </div>
  <div class="program-card__body">
    <h3>{{ $program->name }}</h3>
    @if ($program->description)
      <p>{{ \Illuminate\Support\Str::limit(strip_tags($program->description), 120) }}</p>
    @endif
    <div class="program-card__meta">
      <span>{{ $program->duration_days }} {{ (int) $program->duration_days === 1 ? 'day' : 'days' }}</span>
      <span class="program-card__more">{{ SiteContent::text('home.programs_card_button') }} <span aria-hidden="true">&rarr;</span></span>
    </div>
  </div>
</a>
