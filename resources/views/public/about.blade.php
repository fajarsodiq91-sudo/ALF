@extends('layouts.public')

@section('title', 'About Us | PT Alfajar Logic Futura')
@section('description', 'PT Alfajar Logic Futura\'s vision, mission, values, and company timeline.')

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">About Company</p>
      <h1>Strategic technology and human capability development for the modern enterprise.</h1>
      <p>
        We combine deep technical expertise with practical business understanding to accelerate
        digital maturity for private companies, state-owned enterprises, government agencies,
        schools, universities, SMEs, and manufacturing industries.
      </p>
    </div>
  </section>

  <section class="section">
    <div class="container about__grid">
      <div class="about__content reveal">
        <div class="about__cards">
          <article>
            <h3>Vision</h3>
            <p>To become a trusted innovation partner that transforms organizations into intelligent, data-driven enterprises.</p>
          </article>
          <article>
            <h3>Mission</h3>
            <p>To deliver secure, scalable, and measurable digital solutions that improve decision-making and performance.</p>
          </article>
          <article>
            <h3>Values</h3>
            <p>Integrity, innovation, excellence, collaboration, and continuous learning guide every engagement.</p>
          </article>
        </div>
      </div>
      <div class="about__sidebar reveal reveal--right">
        <img src="{{ asset('assets/images/about-illustration.svg') }}" alt="Technology innovation illustration" loading="lazy" />
        <div class="timeline">
          <h3>Company Timeline</h3>
          <ul>
            <li><strong>2018</strong> Founded with a focus on analytics and enterprise solutions.</li>
            <li><strong>2020</strong> Expanded into training and AI-powered consulting.</li>
            <li><strong>2024</strong> Delivered enterprise-grade digital transformation programs.</li>
          </ul>
        </div>
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
