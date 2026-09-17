<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSiteTest extends TestCase
{
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
}
