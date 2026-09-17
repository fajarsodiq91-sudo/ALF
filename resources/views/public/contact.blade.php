@extends('layouts.public')

@section('title', 'Contact | PT Alfajar Logic Futura')
@section('description', 'Get in touch with PT Alfajar Logic Futura for consulting, analytics, development, or training needs.')

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">Contact</p>
      <h1>Let&rsquo;s build something extraordinary together.</h1>
      <p>We are ready to support your next phase of innovation, capability growth, and digital excellence.</p>
    </div>
  </section>

  <section class="section" id="contact">
    <div class="container contact-grid">
      <div class="contact-info reveal">
        <ul class="contact-list">
          <li><strong>Address:</strong> Karawang, Indonesia</li>
          <li><strong>Email:</strong> admin@alfajarlogic.com</li>
          <li><strong>WhatsApp:</strong> +62 821-2529-8452</li>
          <li><strong>LinkedIn:</strong> linkedin.com/company/alfajarlogic</li>
          <li><strong>Instagram:</strong> @alfajarlogic</li>
        </ul>
        <div class="map-wrapper">
          <iframe
            class="map-frame"
            src="https://www.google.com/maps?q=Karawang%20Indonesia&z=13&output=embed"
            title="Location on Google Maps"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
          ></iframe>
          <a class="map-link" href="https://maps.app.goo.gl/jYmt5gCG6o9EQJj19" target="_blank" rel="noopener noreferrer">
            Open in Google Maps
          </a>
        </div>
      </div>
      <form
        class="contact-form reveal reveal--right"
        id="contactForm"
        action="https://formsubmit.co/admin@alfajarlogic.com"
        method="POST"
        accept-charset="UTF-8"
      >
        <input type="hidden" name="_subject" value="New inquiry from alfajarlogic.com" />
        <input type="hidden" name="_captcha" value="0" />
        <input type="hidden" name="_template" value="table" />
        <input type="hidden" name="_next" value="{{ route('thank-you') }}" />
        <label for="contact-name">
          Name
          <input id="contact-name" type="text" name="name" placeholder="Your name" required />
        </label>
        <label for="contact-email">
          Email
          <input id="contact-email" type="email" name="email" placeholder="you@example.com" required />
        </label>
        <label for="contact-phone">
          Phone Number
          <input id="contact-phone" type="tel" name="phone" placeholder="08xxxxxxxxxx" />
        </label>
        <label for="contact-company">
          Company
          <input id="contact-company" type="text" name="company" placeholder="Your company" />
        </label>
        <label for="contact-message">
          Message
          <textarea id="contact-message" name="message" rows="5" placeholder="Tell us about your goals" required></textarea>
        </label>
        <button class="btn btn--primary" type="submit">Send Message</button>
        <p class="form-status" role="status" aria-live="polite"></p>
      </form>
    </div>
  </section>
@endsection
