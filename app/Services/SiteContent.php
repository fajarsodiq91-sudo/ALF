<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Every fixed piece of text, link and image on the public company site, editable from Master Data → Website.
 * These definitions drive the admin form, its validation and the defaults the pages fall back to, so the site
 * is never blank. Values live in the settings table as `site.{page}.{field}`; an empty value means "use the default".
 * Repeating content (service cards, testimonials, …) is not here — see App\Models\SiteItem.
 */
class SiteContent
{
    /**
     * `type` is text | textarea | email | url | image; `default` is the text shown until it is edited
     * (for images, a path under public/); `max` overrides the default length limit.
     *
     * @var array<string, array{label: string, description: string, sections: array<string, array<string, array<string, mixed>>>, lists?: list<string>}>
     */
    public const PAGES = [
        'global' => [
            'label' => 'Site-wide',
            'description' => 'Logos, menu, footer, contact details and the call-to-action banner that appear on every page.',
            'sections' => [
                'Logos & icon' => [
                    'brand_logo' => ['type' => 'image', 'label' => 'Header logo', 'default' => 'assets/images/company-logo.png'],
                    'footer_logo' => ['type' => 'image', 'label' => 'Footer logo', 'default' => 'assets/images/company-logo-footer.png'],
                    'favicon' => ['type' => 'image', 'label' => 'Browser tab icon (favicon)', 'default' => 'assets/icons/alf.png', 'hint' => 'A square PNG works best.'],
                    'logo_alt' => ['type' => 'text', 'label' => 'Logo description (alt text)', 'default' => 'PT Alfajar Logic Futura logo'],
                ],
                'Company' => [
                    'company_name' => ['type' => 'text', 'label' => 'Company name', 'default' => 'PT Alfajar Logic Futura', 'hint' => 'Shown in the footer copyright line and page metadata.'],
                    'footer_tagline' => ['type' => 'textarea', 'label' => 'Footer tagline', 'default' => 'We build digital foundations for modern organizations through technology, training, and strategy.'],
                    'footer_rights' => ['type' => 'text', 'label' => 'Copyright text', 'default' => 'All rights reserved.', 'hint' => 'Follows "© year company name."'],
                ],
                'Menu' => [
                    'nav_home' => ['type' => 'text', 'label' => 'Home', 'default' => 'Home', 'max' => 40],
                    'nav_about' => ['type' => 'text', 'label' => 'About', 'default' => 'About', 'max' => 40],
                    'nav_services' => ['type' => 'text', 'label' => 'Services', 'default' => 'Services', 'max' => 40],
                    'nav_portfolio' => ['type' => 'text', 'label' => 'Portfolio', 'default' => 'Portfolio', 'max' => 40],
                    'nav_contact' => ['type' => 'text', 'label' => 'Contact', 'default' => 'Contact', 'max' => 40],
                    'nav_staff_login' => ['type' => 'text', 'label' => 'Staff login', 'default' => 'Staff Login', 'max' => 40],
                    'nav_customer_portal' => ['type' => 'text', 'label' => 'Customer portal button', 'default' => 'Customer Portal', 'max' => 40],
                    'nav_consultation' => ['type' => 'text', 'label' => 'Consultation button', 'default' => 'Consultation', 'max' => 40],
                ],
                'Footer headings' => [
                    'footer_links_title' => ['type' => 'text', 'label' => 'Quick links heading', 'default' => 'Quick Links', 'max' => 60],
                    'footer_services_title' => ['type' => 'text', 'label' => 'Services heading', 'default' => 'Services', 'max' => 60, 'hint' => 'The links under it are managed in Website lists → Footer Links.'],
                    'footer_contact_title' => ['type' => 'text', 'label' => 'Contact heading', 'default' => 'Contact', 'max' => 60],
                ],
                'Call-to-action banner' => [
                    'cta_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Ready to Move Forward?'],
                    'cta_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Ready to Transform Your Business?'],
                    'cta_button' => ['type' => 'text', 'label' => 'Button label', 'default' => 'Contact Us', 'max' => 60],
                ],
                'Contact details' => [
                    'contact_address' => ['type' => 'text', 'label' => 'Address', 'default' => 'Karawang, Indonesia'],
                    'contact_email' => ['type' => 'email', 'label' => 'Email', 'default' => 'admin@alfajarlogic.com'],
                    'contact_whatsapp' => ['type' => 'text', 'label' => 'WhatsApp number', 'default' => '+62 821-2529-8452', 'max' => 40, 'hint' => 'With country code, e.g. +62 812-3456-7890.'],
                    'contact_linkedin' => ['type' => 'text', 'label' => 'LinkedIn', 'default' => 'linkedin.com/company/alfajarlogic', 'hint' => 'Without https://.'],
                    'contact_instagram' => ['type' => 'text', 'label' => 'Instagram', 'default' => '@alfajarlogic', 'hint' => 'The @handle.'],
                    'map_query' => ['type' => 'text', 'label' => 'Map location', 'default' => 'Karawang Indonesia', 'hint' => 'What the embedded Google map searches for, e.g. a place name or address.'],
                    'map_link' => ['type' => 'url', 'label' => 'Google Maps link', 'default' => 'https://maps.app.goo.gl/jYmt5gCG6o9EQJj19'],
                    'form_email' => ['type' => 'email', 'label' => 'Contact form recipient', 'default' => 'admin@alfajarlogic.com', 'hint' => 'Where messages from the contact form are sent. formsubmit.co asks you to confirm a new address by email before it starts delivering.'],
                ],
            ],
        ],

        'home' => [
            'label' => 'Home',
            'description' => 'The landing page.',
            'lists' => ['service', 'testimonial', 'stat'],
            'sections' => [
                'Search engines & browser tab' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'PT Alfajar Logic Futura | Empowering Business Through Technology'],
                    'og_title' => ['type' => 'text', 'label' => 'Title when shared on social media', 'default' => 'PT Alfajar Logic Futura | Technology for Business Growth'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'PT Alfajar Logic Futura helps organizations transform through data analytics, software development, artificial intelligence, and strategic corporate training.', 'max' => 300],
                ],
                'Hero' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Trusted Technology Partner'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Empowering Business Through Technology'],
                    'hero_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'PT Alfajar Logic Futura helps organizations transform through data analytics, software development, artificial intelligence, and strategic corporate training.'],
                    'hero_primary_button' => ['type' => 'text', 'label' => 'Main button', 'default' => 'Get Started', 'max' => 60],
                    'hero_secondary_button' => ['type' => 'text', 'label' => 'Second button', 'default' => 'Our Services', 'max' => 60],
                    'hero_badge_1' => ['type' => 'text', 'label' => 'Badge 1', 'default' => 'IT Consulting', 'max' => 60],
                    'hero_badge_2' => ['type' => 'text', 'label' => 'Badge 2', 'default' => 'BI & Analytics', 'max' => 60],
                    'hero_badge_3' => ['type' => 'text', 'label' => 'Badge 3', 'default' => 'AI Solutions', 'max' => 60],
                    'hero_image' => ['type' => 'image', 'label' => 'Illustration', 'default' => 'assets/images/hero-illustration.png'],
                    'hero_image_alt' => ['type' => 'text', 'label' => 'Illustration description (alt text)', 'default' => 'Technology dashboard illustration'],
                ],
                'About teaser' => [
                    'about_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'About Company'],
                    'about_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Strategic technology and human capability development for the modern enterprise.'],
                    'about_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'PT Alfajar Logic Futura combines deep technical expertise with practical business understanding to accelerate digital maturity for private companies, state-owned enterprises, government agencies, schools, universities, SMEs, and manufacturing industries.'],
                    'about_button' => ['type' => 'text', 'label' => 'Button label', 'default' => 'Learn More About Us', 'max' => 60],
                    'about_image' => ['type' => 'image', 'label' => 'Illustration', 'default' => 'assets/images/about-illustration.png'],
                    'about_image_alt' => ['type' => 'text', 'label' => 'Illustration description (alt text)', 'default' => 'Technology innovation illustration'],
                ],
                'Services section' => [
                    'services_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Services'],
                    'services_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'End-to-end digital capability for growth and transformation.'],
                    'services_button' => ['type' => 'text', 'label' => 'Button label', 'default' => 'View All Services', 'max' => 60],
                ],
                'Testimonials section' => [
                    'testimonials_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Testimonials'],
                    'testimonials_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'What clients say about our partnership.'],
                    'testimonials_source' => ['type' => 'text', 'label' => 'Source note', 'default' => 'Connected to Google Maps reviews.'],
                    'testimonials_source_link_label' => ['type' => 'text', 'label' => 'Source link label', 'default' => 'Open reviews on Google Maps', 'max' => 80],
                    'testimonials_source_link_url' => ['type' => 'url', 'label' => 'Source link', 'default' => 'https://maps.app.goo.gl/jYmt5gCG6o9EQJj19'],
                    'testimonials_review_label' => ['type' => 'text', 'label' => 'Label under each review', 'default' => 'Google Maps Review', 'max' => 80],
                    'testimonials_review_link_label' => ['type' => 'text', 'label' => 'Link label under each review', 'default' => 'View on Google Maps', 'max' => 80],
                ],
            ],
        ],

        'about' => [
            'label' => 'About',
            'description' => 'Company profile, vision, mission, values and timeline.',
            'lists' => ['timeline'],
            'sections' => [
                'Search engines & browser tab' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'About Us | PT Alfajar Logic Futura'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description', 'default' => "PT Alfajar Logic Futura's vision, mission, values, and company timeline.", 'max' => 300],
                ],
                'Hero' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'About Company'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Strategic technology and human capability development for the modern enterprise.'],
                    'hero_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'We combine deep technical expertise with practical business understanding to accelerate digital maturity for private companies, state-owned enterprises, government agencies, schools, universities, SMEs, and manufacturing industries.'],
                ],
                'Vision, mission & values' => [
                    'vision_title' => ['type' => 'text', 'label' => 'Vision — title', 'default' => 'Vision', 'max' => 60],
                    'vision_text' => ['type' => 'textarea', 'label' => 'Vision — text', 'default' => 'To become a trusted innovation partner that transforms organizations into intelligent, data-driven enterprises.'],
                    'mission_title' => ['type' => 'text', 'label' => 'Mission — title', 'default' => 'Mission', 'max' => 60],
                    'mission_text' => ['type' => 'textarea', 'label' => 'Mission — text', 'default' => 'To deliver secure, scalable, and measurable digital solutions that improve decision-making and performance.'],
                    'values_title' => ['type' => 'text', 'label' => 'Values — title', 'default' => 'Values', 'max' => 60],
                    'values_text' => ['type' => 'textarea', 'label' => 'Values — text', 'default' => 'Integrity, innovation, excellence, collaboration, and continuous learning guide every engagement.'],
                ],
                'Illustration & timeline' => [
                    'image' => ['type' => 'image', 'label' => 'Illustration', 'default' => 'assets/images/about-illustration.png'],
                    'image_alt' => ['type' => 'text', 'label' => 'Illustration description (alt text)', 'default' => 'Technology innovation illustration'],
                    'timeline_title' => ['type' => 'text', 'label' => 'Timeline heading', 'default' => 'Company Timeline', 'max' => 80],
                ],
            ],
        ],

        'services' => [
            'label' => 'Services',
            'description' => 'Everything the company offers, plus the corporate training programs.',
            'lists' => ['service', 'training'],
            'sections' => [
                'Search engines & browser tab' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'Services | PT Alfajar Logic Futura'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Business Intelligence, software development, AI, ERP, cloud, and corporate training services from PT Alfajar Logic Futura.', 'max' => 300],
                ],
                'Hero' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Services'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'End-to-end digital capability for growth and transformation.'],
                    'hero_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'From strategy to implementation, we cover the full stack of enterprise technology needs.'],
                ],
                'Corporate training section' => [
                    'training_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Corporate Training'],
                    'training_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Practical programs to build high-impact digital skills.'],
                    'training_button' => ['type' => 'text', 'label' => 'Button label on each program', 'default' => 'Learn More', 'max' => 60],
                ],
            ],
        ],

        'portfolio' => [
            'label' => 'Portfolio',
            'description' => 'Selected work. The filter buttons come from the Portfolio Category dropdown in Master Data.',
            'lists' => ['portfolio'],
            'sections' => [
                'Search engines & browser tab' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'Portfolio | PT Alfajar Logic Futura'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Selected analytics, development, and training work delivered by PT Alfajar Logic Futura.', 'max' => 300],
                ],
                'Hero' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Portfolio'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Selected work that reflects our delivery quality.'],
                    'hero_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'A sample of engagements across analytics, development, and training.'],
                ],
                'Filter' => [
                    'filter_all' => ['type' => 'text', 'label' => '"Show everything" button', 'default' => 'All', 'max' => 40],
                ],
            ],
        ],

        'contact' => [
            'label' => 'Contact',
            'description' => 'The contact page. Address, email, social links and the map are edited under Site-wide.',
            'sections' => [
                'Search engines & browser tab' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'Contact | PT Alfajar Logic Futura'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description', 'default' => 'Get in touch with PT Alfajar Logic Futura for consulting, analytics, development, or training needs.', 'max' => 300],
                ],
                'Hero' => [
                    'hero_eyebrow' => ['type' => 'text', 'label' => 'Small heading', 'default' => 'Contact'],
                    'hero_title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Let’s build something extraordinary together.'],
                    'hero_text' => ['type' => 'textarea', 'label' => 'Paragraph', 'default' => 'We are ready to support your next phase of innovation, capability growth, and digital excellence.'],
                ],
                'Form & map' => [
                    'form_button' => ['type' => 'text', 'label' => 'Send button label', 'default' => 'Send Message', 'max' => 60],
                    'form_subject' => ['type' => 'text', 'label' => 'Email subject of a new message', 'default' => 'New inquiry from alfajarlogic.com'],
                    'map_link_label' => ['type' => 'text', 'label' => 'Map link label', 'default' => 'Open in Google Maps', 'max' => 80],
                ],
            ],
        ],

        'thank-you' => [
            'label' => 'Thank You',
            'description' => 'Shown after someone sends the contact form.',
            'sections' => [
                'Page' => [
                    'meta_title' => ['type' => 'text', 'label' => 'Page title', 'default' => 'Thank You | PT Alfajar Logic Futura'],
                    'title' => ['type' => 'text', 'label' => 'Title', 'default' => 'Thank You!'],
                    'message' => ['type' => 'textarea', 'label' => 'Message', 'default' => 'Your message has been sent successfully. We will get back to you shortly.'],
                    'link_label' => ['type' => 'text', 'label' => 'Link back to the homepage', 'default' => 'Back to homepage', 'max' => 80],
                ],
            ],
        ],
    ];

    /** @return list<string> */
    public static function pageKeys(): array
    {
        return array_keys(self::PAGES);
    }

    /**
     * Every field of a page, flattened out of its sections.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(string $page): array
    {
        return array_merge(...array_values(self::PAGES[$page]['sections'] ?? []));
    }

    /** The text for a `page.field` path: what was saved, else the built-in default. */
    public static function text(string $path): string
    {
        return Setting::get(self::key($path), self::definition($path)['default'] ?? '') ?? '';
    }

    /** Plain text whose line breaks are kept. Escaped first, so nothing typed in the admin can inject markup. */
    public static function multiline(string $path): HtmlString
    {
        return new HtmlString(nl2br(e(self::text($path))));
    }

    /** The URL of an image: the uploaded file, else the built-in default. */
    public static function image(string $path): string
    {
        $stored = Setting::get(self::key($path));

        return $stored ? asset('storage/'.$stored) : asset(self::definition($path)['default']);
    }

    /** The stored path of an uploaded image, or null while the default is in use. */
    public static function uploadedImage(string $path): ?string
    {
        return Setting::get(self::key($path));
    }

    public static function whatsappUrl(): string
    {
        return 'https://wa.me/'.preg_replace('/\D+/', '', self::text('global.contact_whatsapp'));
    }

    public static function linkedinUrl(): string
    {
        return self::externalUrl(self::text('global.contact_linkedin'));
    }

    public static function instagramUrl(): string
    {
        $handle = self::text('global.contact_instagram');

        return str_starts_with($handle, 'http') ? $handle : 'https://instagram.com/'.ltrim($handle, '@');
    }

    public static function mapEmbedUrl(): string
    {
        return 'https://www.google.com/maps?q='.rawurlencode(self::text('global.map_query')).'&z=13&output=embed';
    }

    public static function key(string $path): string
    {
        return 'site.'.$path;
    }

    private static function externalUrl(string $value): string
    {
        return str_starts_with($value, 'http') ? $value : 'https://'.$value;
    }

    /** @return array<string, mixed> */
    private static function definition(string $path): array
    {
        [$page, $field] = array_pad(explode('.', $path, 2), 2, '');

        return self::fields($page)[$field] ?? throw new InvalidArgumentException("Unknown site content field [{$path}].");
    }
}
