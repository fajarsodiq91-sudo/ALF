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
          <a class="nav-staff" href="{{ route('login') }}" title="{{ SiteContent::text('global.nav_staff_login') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            {{ SiteContent::text('global.nav_staff_login') }}
          </a>
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
            <li><a href="{{ route('login') }}">{{ SiteContent::text('global.nav_staff_login') }}</a></li>
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

    <a class="wa-float" href="{{ SiteContent::whatsappUrl() }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp: {{ SiteContent::text('global.whatsapp_float_label') }}">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.45 9.45 0 01-4.82-1.32l-.35-.21-3.58.94.96-3.49-.23-.36a9.43 9.43 0 01-1.45-5.03c0-5.22 4.25-9.47 9.48-9.47 2.53 0 4.9.99 6.7 2.78a9.4 9.4 0 012.77 6.7c0 5.22-4.25 9.46-9.47 9.46zm8.06-17.53A11.33 11.33 0 0012.04.62C5.76.62.65 5.73.65 12.01c0 2.01.52 3.97 1.52 5.69L.55 23.6l6.04-1.58a11.35 11.35 0 005.44 1.39h.01c6.28 0 11.39-5.11 11.39-11.39 0-3.04-1.18-5.9-3.33-8.05z"/></svg>
      <span>{{ SiteContent::text('global.whatsapp_float_label') }}</span>
    </a>

    <button class="back-to-top" aria-label="Back to top">&uarr;</button>

    <script src="{{ asset('js/site.js') }}"></script>
  </body>
</html>
