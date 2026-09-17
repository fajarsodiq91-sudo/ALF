@extends('layouts.public')

@section('title', 'Services | PT Alfajar Logic Futura')
@section('description', 'Business Intelligence, software development, AI, ERP, cloud, and corporate training services from PT Alfajar Logic Futura.')

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">Services</p>
      <h1>End-to-end digital capability for growth and transformation.</h1>
      <p>From strategy to implementation, we cover the full stack of enterprise technology needs.</p>
    </div>
  </section>

  <section class="section" id="services">
    <div class="container">
      <div class="services-grid">
        <article class="service-card reveal">
          <div class="service-card__icon">📊</div>
          <h3>Business Intelligence</h3>
          <p>Executive dashboards, KPI design, strategic insights, and operational reporting.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">📈</div>
          <h3>Power BI Dashboard</h3>
          <p>Interactive analytics solutions for modern business performance monitoring.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🧠</div>
          <h3>Data Analytics</h3>
          <p>Transform raw data into stories, decisions, and measurable business outcomes.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🎓</div>
          <h3>Corporate Training</h3>
          <p>Hands-on capability building across Excel, Power BI, SQL, Python, AI, and more.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">💻</div>
          <h3>Website Development</h3>
          <p>Premium digital experiences tailored for growth, trust, and conversion.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🧩</div>
          <h3>Web Application</h3>
          <p>Custom web products designed for productivity, workflows, and scale.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🏢</div>
          <h3>ERP System</h3>
          <p>Future-ready enterprise architecture for operational excellence and integration.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">✨</div>
          <h3>AI Solution</h3>
          <p>Intelligent automation, assistants, and analytics platforms for modern teams.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">📱</div>
          <h3>Mobile Application</h3>
          <p>Cross-platform mobile solutions built for speed, reliability, and usability.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🗄️</div>
          <h3>Database Development</h3>
          <p>Reliable data models, storage architecture, and high-performance access.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">☁️</div>
          <h3>Cloud Solution</h3>
          <p>Scalable cloud environments for resilience, collaboration, and modernization.</p>
        </article>
        <article class="service-card reveal">
          <div class="service-card__icon">🛠️</div>
          <h3>IT Consulting</h3>
          <p>Technology roadmaps and advisory aligned with strategy, compliance, and growth.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section section--alt" id="training">
    <div class="container">
      <div class="section-heading reveal">
        <p class="eyebrow">Corporate Training</p>
        <h2>Practical programs to build high-impact digital skills.</h2>
      </div>
      <div class="training-grid">
        <article class="training-card reveal">
          <h3>Microsoft Excel</h3>
          <p>Advanced formulas, dashboards, and business productivity.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Microsoft Power BI</h3>
          <p>Analytics and visualization for enterprise intelligence.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Power Query</h3>
          <p>Automate and prepare data from multiple systems.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Power Pivot</h3>
          <p>Model and analyze large datasets with confidence.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>SQL</h3>
          <p>Query design, data modeling, and reporting workflows.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Python</h3>
          <p>Automation, scripting, analysis, and advanced workflows.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>AI for Office</h3>
          <p>Practical AI use cases for productivity and collaboration.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>ChatGPT</h3>
          <p>Adopt generative AI safely and effectively.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Microsoft Copilot</h3>
          <p>Boost team productivity with AI-enabled work patterns.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Google Workspace</h3>
          <p>Modern collaboration and secure cloud workplace practices.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Power Automate</h3>
          <p>Automate repetitive work and accelerate service delivery.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
        </article>
        <article class="training-card reveal">
          <h3>Business Intelligence</h3>
          <p>Turn data into strategic action with confidence.</p>
          <a href="{{ route('contact') }}" class="link-btn">Learn More</a>
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
