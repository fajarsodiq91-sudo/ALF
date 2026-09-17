<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="@yield('description', 'PT Alfajar Logic Futura delivers IT consulting, data analytics, business intelligence, software development, AI, and corporate training solutions for modern enterprises.')" />
    <meta name="author" content="PT Alfajar Logic Futura" />
    <meta property="og:title" content="@yield('og-title', 'PT Alfajar Logic Futura')" />
    <meta property="og:description" content="@yield('description', 'Modern enterprise technology solutions and corporate training for transformation and growth.')" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <title>@yield('title', 'PT Alfajar Logic Futura')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/icons/alf.svg') }}" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}" />
  </head>
  <body>
    <div class="loader" aria-hidden="true">
      <div class="loader__ring"></div>
    </div>

    <header class="site-header" id="top">
      <div class="container nav-wrap">
        <a class="brand" href="{{ route('home') }}" aria-label="PT Alfajar Logic Futura home">
          <img class="brand__logo" src="{{ asset('assets/images/company-logo.svg') }}" alt="PT Alfajar Logic Futura logo" />
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
          <span></span>
          <span></span>
          <span></span>
        </button>

        @php
          $navItems = [
            'home' => ['label' => 'Home', 'url' => route('home')],
            'about' => ['label' => 'About', 'url' => route('about')],
            'services' => ['label' => 'Services', 'url' => route('services')],
            'portfolio' => ['label' => 'Portfolio', 'url' => route('portfolio')],
            'contact' => ['label' => 'Contact', 'url' => route('contact')],
          ];
        @endphp

        <nav class="site-nav" aria-label="Primary navigation">
          @foreach ($navItems as $routeName => $item)
            <a href="{{ $item['url'] }}" @class(['is-current' => request()->routeIs($routeName)])>{{ $item['label'] }}</a>
          @endforeach
          <a href="{{ route('login') }}">Login</a>
          <a class="btn btn--small btn--primary" href="{{ route('contact') }}">Consultation</a>
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
            <img class="brand__logo brand__logo--footer" src="{{ asset('assets/images/company-logo-footer.svg') }}" alt="PT Alfajar Logic Futura logo" />
          </a>
          <p>We build digital foundations for modern organizations through technology, training, and strategy.</p>
        </div>
        <div>
          <h3>Quick Links</h3>
          <ul>
            <li><a href="{{ route('about') }}">About</a></li>
            <li><a href="{{ route('services') }}">Services</a></li>
            <li><a href="{{ route('portfolio') }}">Portfolio</a></li>
            <li><a href="{{ route('contact') }}">Contact</a></li>
          </ul>
        </div>
        <div>
          <h3>Services</h3>
          <ul>
            <li><a href="{{ route('services') }}">BI &amp; Analytics</a></li>
            <li><a href="{{ route('services') }}">Software Development</a></li>
            <li><a href="{{ route('services') }}#training">Corporate Training</a></li>
            <li><a href="{{ route('services') }}">AI Solution</a></li>
          </ul>
        </div>
        <div>
          <h3>Contact</h3>
          <ul>
            <li><a href="mailto:admin@alfajarlogic.com">admin@alfajarlogic.com</a></li>
            <li><a href="https://wa.me/6282125298452">WhatsApp</a></li>
            <li><a href="https://linkedin.com/company/alfajarlogic">LinkedIn</a></li>
            <li><a href="https://instagram.com/alfajarlogic">Instagram</a></li>
          </ul>
        </div>
      </div>
      <div class="container footer-bottom">
        <p>&copy; {{ date('Y') }} PT Alfajar Logic Futura. All rights reserved.</p>
        <div class="social-links">
          <a href="https://linkedin.com/company/alfajarlogic" aria-label="LinkedIn">in</a>
          <a href="https://instagram.com/alfajarlogic" aria-label="Instagram">ig</a>
          <a href="mailto:admin@alfajarlogic.com" aria-label="Email">@</a>
        </div>
      </div>
    </footer>

    <button class="back-to-top" aria-label="Back to top">&uarr;</button>

    <script src="{{ asset('js/site.js') }}"></script>
  </body>
</html>
