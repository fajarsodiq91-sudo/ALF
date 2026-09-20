@extends('layouts.public')

@section('title', 'Portfolio | PT Alfajar Logic Futura')
@section('description', 'Selected analytics, development, and training work delivered by PT Alfajar Logic Futura.')

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">Portfolio</p>
      <h1>Selected work that reflects our delivery quality.</h1>
      <p>A sample of engagements across analytics, development, and training.</p>
    </div>
  </section>

  <section class="section" id="portfolio">
    <div class="container">
      <div class="filter-bar reveal" role="tablist" aria-label="Portfolio filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="analytics">Analytics</button>
        <button class="filter-btn" data-filter="development">Development</button>
        <button class="filter-btn" data-filter="training">Training</button>
      </div>
      <div class="portfolio-grid">
        <article class="portfolio-card reveal" data-category="analytics">
          <img src="{{ asset('assets/images/portfolio-1.png') }}" alt="Business intelligence dashboard project" loading="lazy" />
          <div class="portfolio-card__content">
            <h3>Enterprise BI Platform</h3>
            <p>Modern dashboards and performance tracking for manufacturing operations.</p>
          </div>
        </article>
        <article class="portfolio-card reveal" data-category="development">
          <img src="{{ asset('assets/images/portfolio-2.png') }}" alt="Custom web application project" loading="lazy" />
          <div class="portfolio-card__content">
            <h3>Client Portal Web App</h3>
            <p>Secure portal for service workflows, reporting, and user collaboration.</p>
          </div>
        </article>
        <article class="portfolio-card reveal" data-category="training">
          <img src="{{ asset('assets/images/portfolio-3.png') }}" alt="Corporate training program project" loading="lazy" />
          <div class="portfolio-card__content">
            <h3>Power BI Upskilling Program</h3>
            <p>Training initiative designed to accelerate analyst adoption across teams.</p>
          </div>
        </article>
        <article class="portfolio-card reveal" data-category="development">
          <img src="{{ asset('assets/images/portfolio-4.png') }}" alt="ERP system interface project" loading="lazy" />
          <div class="portfolio-card__content">
            <h3>ERP Architecture Design</h3>
            <p>Scalable architecture plan for future-ready enterprise operations.</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="container cta__wrap reveal">
      <div>
        <p class="eyebrow eyebrow--light">Ready to Move Forward?</p>
        <h2>Ready to Transform Your Business?</h2>
      </div>
      <a class="btn btn--light" href="{{ route('contact') }}">Contact Us</a>
    </div>
  </section>
@endsection
