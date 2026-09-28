<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('PT Alfajar Logic Futura', false);
        $response->assertSee('Empowering Business Through Technology');
    }

    public function test_about_page_loads(): void
    {
        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('Vision');
        $response->assertSee('Mission');
    }

    public function test_services_page_loads(): void
    {
        $response = $this->get('/services');

        $response->assertOk();
        $response->assertSee('Business Intelligence');
        $response->assertSee('Corporate Training');
    }

    public function test_portfolio_page_loads(): void
    {
        $response = $this->get('/portfolio');

        $response->assertOk();
        $response->assertSee('Enterprise BI Platform');
    }

    public function test_contact_page_loads(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('admin@alfajarlogic.com');
    }

    public function test_thank_you_page_loads(): void
    {
        $response = $this->get('/thank-you');

        $response->assertOk();
        $response->assertSee('Thank You');
    }

    public function test_public_pages_share_the_same_navigation(): void
    {
        foreach (['/', '/about', '/services', '/portfolio', '/contact'] as $path) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee('href="'.route('login').'"', false);
        }
    }

    public function test_every_public_page_has_a_customer_portal_button_in_the_header_and_footer(): void
    {
        foreach (['/', '/about', '/services', '/portfolio', '/contact'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            // Header: an outline button next to the staff login and the consultation button.
            $this->assertMatchesRegularExpression('#<a class="btn btn--small btn--outline" href="'.preg_quote(route('portal.login'), '#').'">\s*<svg.*?Customer Portal\s*</a>#s', $html, $path);
            // Footer: a plain quick link.
            $this->assertStringContainsString('<li><a href="'.route('portal.login').'">Customer Portal</a></li>', $html, $path);
        }
    }

    public function test_the_staff_login_link_is_labelled_so_it_is_not_confused_with_the_customer_portal(): void
    {
        $this->get('/')
            ->assertSee('<a href="'.route('login').'">Staff Login</a>', false)
            ->assertDontSee('<a href="'.route('login').'">Login</a>', false);
    }

    public function test_the_button_leads_to_the_customer_login_page(): void
    {
        $this->get(route('portal.login'))->assertOk()->assertSee('Customer Portal')->assertSee('Customer ID');
    }

    public function test_the_button_takes_a_signed_in_customer_straight_to_their_portal(): void
    {
        $customer = Customer::factory()->withPortalAccess('rahasia123')->create();
        $this->post(route('portal.login.store'), ['customer_code' => $customer->customer_code, 'password' => 'rahasia123']);

        $this->get(route('portal.login'))->assertRedirect(route('portal.dashboard'));
    }

    public function test_the_outline_button_style_exists_in_the_site_stylesheet(): void
    {
        $css = file_get_contents(public_path('css/site.css'));

        $this->assertStringContainsString('.btn--outline {', $css);
        $this->assertStringContainsString('.site-nav a.btn--outline', $css);
    }
}
