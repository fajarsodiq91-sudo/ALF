@extends('layouts.public')

@section('title', 'PT Alfajar Logic Futura | Empowering Business Through Technology')
@section('og-title', 'PT Alfajar Logic Futura | Technology for Business Growth')
@section('description', 'PT Alfajar Logic Futura helps organizations transform through data analytics, software development, artificial intelligence, and strategic corporate training.')

@section('content')
  <section class="hero" id="home">
    <div class="container hero__grid">
      <div class="hero__content reveal">
        <p class="eyebrow">Trusted Technology Partner</p>
        <h1>Empowering Business Through Technology</h1>
        <p class="hero__text">
          PT Alfajar Logic Futura helps organizations transform through data analytics,
          software development, artificial intelligence, and strategic corporate training.
        </p>
        <div class="hero__actions">
          <a class="btn btn--primary" href="{{ route('contact') }}">Get Started</a>
          <a class="btn btn--secondary" href="{{ route('services') }}">Our Services</a>
        </div>
        <div class="hero__badges">
          <span>IT Consulting</span>
          <span>BI & Analytics</span>
          <span>AI Solutions</span>
        </div>
      </div>
      <div class="hero__visual reveal reveal--right">
        <img src="{{ asset('assets/images/hero-illustration.svg') }}" alt="Technology dashboard illustration" loading="lazy" />
      </div>
    </div>
  </section>

  <section class="section" id="about-teaser">
    <div class="container about__grid">
      <div class="about__content reveal">
        <p class="eyebrow">About Company</p>
        <h2>Strategic technology and human capability development for the modern enterprise.</h2>
        <p>
          PT Alfajar Logic Futura combines deep technical expertise with practical business understanding
          to accelerate digital maturity for private companies, state-owned enterprises, government agencies,
          schools, universities, SMEs, and manufacturing industries.
        </p>
        <div class="hero__actions">
          <a class="btn btn--secondary" href="{{ route('about') }}">Learn More About Us</a>
        </div>
      </div>
      <div class="about__sidebar reveal reveal--right">
        <img src="{{ asset('assets/images/about-illustration.svg') }}" alt="Technology innovation illustration" loading="lazy" />
      </div>
    </div>
  </section>

  <section class="section section--alt" id="services-teaser">
    <div class="container">
      <div class="section-heading reveal">
        <p class="eyebrow">Services</p>
        <h2>End-to-end digital capability for growth and transformation.</h2>
      </div>
      <div class="services-grid">
        <article class="service-card reveal">
          <div class="service-card__icon">📊</div>
          <h3>Business Intelligence</h3>
          <p>Executive dashboards, KPI design, strategic insights, and operational reporting.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">💻</div>
          <h3>Website &amp; Web Application</h3>
          <p>Premium digital experiences and custom web products built for scale.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🏢</div>
          <h3>ERP System</h3>
          <p>Future-ready enterprise architecture for operational excellence.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🎓</div>
          <h3>Corporate Training</h3>
          <p>Hands-on capability building across Excel, Power BI, SQL, Python, AI, and more.</p>
        </article>
      </div>
      <div class="hero__actions" style="justify-content:center;margin-top:40px;">
        <a class="btn btn--primary" href="{{ route('services') }}">View All Services</a>
      </div>
    </div>
  </section>

  <section class="section" id="testimonials">
    <div class="container">
      <div class="section-heading reveal">
        <p class="eyebrow">Testimonials</p>
        <h2>What clients say about our partnership.</h2>
        <p class="testimonial-source">
          Connected to Google Maps reviews.
          <a href="https://maps.app.goo.gl/jYmt5gCG6o9EQJj19" target="_blank" rel="noopener noreferrer">Open reviews on Google Maps</a>
        </p>
      </div>
      <div class="testimonial-slider reveal">
        <article class="testimonial-card active">
          <img src="https://lh3.googleusercontent.com/a-/ALV-UjX7qHWb3A5tYZszMhh1erWa7yWbkIGUpraIdruy5zMB_okwwaT7=w36-h36-p-rp-mo-br100" alt="Client portrait" />
          <p>&ldquo;Pengalaman mengikuti jasa les privat Excel dan Power BI sungguh luar biasa. Pengajar memiliki kemampuan yang mengagumkan dalam mengajar konsep-konsep yang kompleks menjadi lebih mudah dipahami. Saya merasa lebih mahir dalam menggunakan rumus-rumus Excel dan mampu membuat laporan interaktif yang menarik menggunakan Power BI berkat bimbingan yang diberikan. Terima kasih atas kesabaran dan dedikasinya dalam membantu perkembangan kemampuan saya&rdquo;</p>
          <strong>Indra Aliyudin.</strong>
          <span>Maintenance Technician</span>
          <p class="testimonial-source-label">Google Maps Review</p>
          <a class="testimonial-link" href="https://maps.app.goo.gl/BFpx2DvcWUw64Amu5" target="_blank" rel="noopener noreferrer">View on Google Maps</a>
        </article>
        <article class="testimonial-card">
          <img src="https://lh3.googleusercontent.com/a-/ALV-UjVySej1VytPaT0lUQrjSzP5TB7PGscaRPCY2XNeHDsqNlb2B1Ec=w72-h72-p-rp-mo-br100" alt="Client portrait" />
          <p>&ldquo;ma sya Allah bener bener worth it kursus di alfajar,terimakasih bapak sudah membimbing dengan sangat baik dan sangat sangat sabar semoga ilmu nya bisa saya manfaatkan dengan baik dan semoga rezeki bapak lancar selalu.&rdquo;</p>
          <strong>Susi Diah Lestari</strong>
          <span>Digital Transformation</span>
          <p class="testimonial-source-label">Google Maps Review</p>
          <a class="testimonial-link" href="https://maps.app.goo.gl/criULXFh8dK5SqVe9" target="_blank" rel="noopener noreferrer">View on Google Maps</a>
        </article>
        <article class="testimonial-card">
          <img src="https://lh3.googleusercontent.com/a/ACg8ocJqmcBdSxSOxGCGLXBuwitvnOyaynodxLJ3YcnQE9pYwBsIXw=w36-h36-p-rp-mo-br100" alt="Client portrait" />
          <p>&ldquo;Terima kasih, ilmunya pasti bermanfaat karena saya yg awalnya nol banget dan sekarang sudah bisa mandiri dalam mengolah data di excel hingga mampu membuat visualisasi yg keren. Mantabbbbbbb&rdquo;</p>
          <strong>Flash Celia</strong>
          <span>Bisnis Digital</span>
          <p class="testimonial-source-label">Google Maps Review</p>
          <a class="testimonial-link" href="https://maps.app.goo.gl/9PLfSpvcUr28K27n9" target="_blank" rel="noopener noreferrer">View on Google Maps</a>
        </article>
        <div class="dots" aria-label="Testimonial navigation"></div>
      </div>
    </div>
  </section>

  <section class="section stats-section">
    <div class="container stats-grid">
      <div class="stat reveal">
        <strong data-count="250">0</strong>
        <span>Training</span>
      </div>
      <div class="stat reveal">
        <strong data-count="120">0</strong>
        <span>Projects</span>
      </div>
      <div class="stat reveal">
        <strong data-count="95">0</strong>
        <span>Client Satisfaction</span>
      </div>
      <div class="stat reveal">
        <strong data-count="15">0</strong>
        <span>Professional Trainers</span>
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
