@use('App\Services\SiteContent')
@extends('layouts.public')

@section('title', SiteContent::text('contact.meta_title'))
@section('description', SiteContent::text('contact.meta_description'))

@section('content')
  <section class="page-hero">
    <div class="container">
      <p class="eyebrow">{{ SiteContent::text('contact.hero_eyebrow') }}</p>
      <h1>{{ SiteContent::text('contact.hero_title') }}</h1>
      <p>{{ SiteContent::multiline('contact.hero_text') }}</p>
    </div>
  </section>

  <section class="section" id="contact">
    <div class="container contact-grid">
      <div class="contact-info reveal">
        <ul class="contact-list">
          <li><strong>Address:</strong> {{ SiteContent::text('global.contact_address') }}</li>
          <li><strong>Email:</strong> {{ SiteContent::text('global.contact_email') }}</li>
          <li><strong>WhatsApp:</strong> {{ SiteContent::text('global.contact_whatsapp') }}</li>
          <li><strong>LinkedIn:</strong> {{ SiteContent::text('global.contact_linkedin') }}</li>
          <li><strong>Instagram:</strong> {{ SiteContent::text('global.contact_instagram') }}</li>
        </ul>
        <div class="map-wrapper">
          <iframe
            class="map-frame"
            src="{{ SiteContent::mapEmbedUrl() }}"
            title="Location on Google Maps"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
          ></iframe>
          <a class="map-link" href="{{ SiteContent::text('global.map_link') }}" target="_blank" rel="noopener noreferrer">
            {{ SiteContent::text('contact.map_link_label') }}
          </a>
        </div>
      </div>
      <form
        class="contact-form reveal reveal--right"
        id="contactForm"
        action="https://formsubmit.co/{{ SiteContent::text('global.form_email') }}"
        method="POST"
        accept-charset="UTF-8"
      >
        <input type="hidden" name="_subject" value="{{ SiteContent::text('contact.form_subject') }}" />
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
        <button class="btn btn--primary" type="submit">{{ SiteContent::text('contact.form_button') }}</button>
        <p class="form-status" role="status" aria-live="polite"></p>
      </form>
    </div>
  </section>
@endsection
