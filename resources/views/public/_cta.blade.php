@use('App\Services\SiteContent')
<section class="cta">
  <div class="container cta__wrap reveal">
    <div>
      <p class="eyebrow eyebrow--light">{{ SiteContent::text('global.cta_eyebrow') }}</p>
      <h2>{{ SiteContent::text('global.cta_title') }}</h2>
    </div>
    <a class="btn btn--light" href="{{ route('contact') }}">{{ SiteContent::text('global.cta_button') }}</a>
  </div>
</section>
