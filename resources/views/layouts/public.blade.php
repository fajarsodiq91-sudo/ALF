@use('App\Services\SiteContent')
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="@yield('description', 'PT Alfajar Logic Futura delivers IT consulting, data analytics, business intelligence, software development, AI, and corporate training solutions for modern enterprises.')" />
    <meta name="author" content="{{ SiteContent::text('global.company_name') }}" />
    <meta property="og:title" content="@yield('og-title', SiteContent::text('global.company_name'))" />
    <meta property="og:description" content="@yield('description', 'Modern enterprise technology solutions and corporate training for transformation and growth.')" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <title>@yield('title', SiteContent::text('global.company_name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ SiteContent::image('global.favicon') }}" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}" />
  </head>
  <body>
    <div class="loader" aria-hidden="true">
      <div class="loader__ring"></div>
    </div>

    <header class="site-header" id="top">
      <div class="container nav-wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ SiteContent::text('global.company_name') }} home">
          <img class="brand__logo" src="{{ SiteContent::image('global.brand_logo') }}" alt="{{ SiteContent::text('global.logo_alt') }}" />
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
          <span></span>
          <span></span>
          <span></span>
        </button>

        @php
          $navItems = [
            'home' => ['label' => SiteContent::text('global.nav_home'), 'url' => route('home')],
            'about' => ['label' => SiteContent::text('global.nav_about'), 'url' => route('about')],
            'services' => ['label' => SiteContent::text('global.nav_services'), 'url' => route('services')],
            'portfolio' => ['label' => SiteContent::text('global.nav_portfolio'), 'url' => route('portfolio')],
            'contact' => ['label' => SiteContent::text('global.nav_contact'), 'url' => route('contact')],
          ];
        @endphp

        <nav class="site-nav" aria-label="Primary navigation">
          @foreach ($navItems as $routeName => $item)
            <a href="{{ $item['url'] }}" @class(['is-current' => request()->routeIs($routeName)])>{{ $item['label'] }}</a>
          @endforeach
          <a href="{{ route('login') }}">{{ SiteContent::text('global.nav_staff_login') }}</a>
          <a class="btn btn--small btn--outline" href="{{ route('portal.login') }}">
            <svg class="btn__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {{ SiteContent::text('global.nav_customer_portal') }}
          </a>
          <a class="btn btn--small btn--primary" href="{{ route('contact') }}">{{ SiteContent::text('global.nav_consultation') }}</a>
        </nav>
      </div>
    </header>

    <main>
      @yield('content')
    </main>

    <footer class="site-footer" id="articles">
      <div class="container footer-grid">
        <div>
          <a class="brand brand--footer" href="{{ route('home') }}">
            <img class="brand__logo brand__logo--footer" src="{{ SiteContent::image('global.footer_logo') }}" alt="{{ SiteContent::text('global.logo_alt') }}" />
          </a>
          <p>{{ SiteContent::multiline('global.footer_tagline') }}</p>
        </div>
        <div>
          <h3>{{ SiteContent::text('global.footer_links_title') }}</h3>
          <ul>
            <li><a href="{{ route('about') }}">{{ SiteContent::text('global.nav_about') }}</a></li>
            <li><a href="{{ route('services') }}">{{ SiteContent::text('global.nav_services') }}</a></li>
            <li><a href="{{ route('portfolio') }}">{{ SiteContent::text('global.nav_portfolio') }}</a></li>
            <li><a href="{{ route('contact') }}">{{ SiteContent::text('global.nav_contact') }}</a></li>
            <li><a href="{{ route('portal.login') }}">{{ SiteContent::text('global.nav_customer_portal') }}</a></li>
          </ul>
        </div>
        <div>
          <h3>{{ SiteContent::text('global.footer_services_title') }}</h3>
          <ul>
            @foreach ($footerLinks as $link)
              <li><a href="{{ $link->href() }}">{{ $link->title }}</a></li>
            @endforeach
          </ul>
        </div>
        <div>
          <h3>{{ SiteContent::text('global.footer_contact_title') }}</h3>
          <ul>
            <li><a href="mailto:{{ SiteContent::text('global.contact_email') }}">{{ SiteContent::text('global.contact_email') }}</a></li>
            <li><a href="{{ SiteContent::whatsappUrl() }}">WhatsApp</a></li>
            <li><a href="{{ SiteContent::linkedinUrl() }}">LinkedIn</a></li>
            <li><a href="{{ SiteContent::instagramUrl() }}">Instagram</a></li>
          </ul>
        </div>
      </div>
      <div class="container footer-bottom">
        <p>&copy; {{ date('Y') }} {{ SiteContent::text('global.company_name') }}. {{ SiteContent::text('global.footer_rights') }}</p>
        <div class="social-links">
          <a href="{{ SiteContent::linkedinUrl() }}" aria-label="LinkedIn">in</a>
          <a href="{{ SiteContent::instagramUrl() }}" aria-label="Instagram">ig</a>
          <a href="mailto:{{ SiteContent::text('global.contact_email') }}" aria-label="Email">@</a>
        </div>
      </div>
    </footer>

    <button class="back-to-top" aria-label="Back to top">&uarr;</button>

    <script src="{{ asset('js/site.js') }}"></script>
  </body>
</html>
