<?php

namespace Tests\Feature;

use App\Models\MasterDataItem;
use App\Models\SiteItem;
use App\Models\User;
use App\Services\MasterData;
use App\Services\SiteContent;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WebsiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Storage::fake('public');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $this->actingAs($user)->app['auth']->user();
    }

    /** A real 1x1 PNG: GD is not needed to upload one, and the app stores it untouched. */
    private function image(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        ));
    }

    /** Saves a page the way the admin form does: every field is posted, some overridden. */
    private function savePage(string $page, array $content = [], array $extra = [])
    {
        $fields = collect(SiteContent::fields($page))->reject(fn ($field) => $field['type'] === 'image')
            ->map(fn ($field, $name) => SiteContent::text("$page.$name"))->all();

        return $this->put(route('masterdata.site.page.update', $page), [...$extra, 'content' => [...$fields, ...$content]]);
    }

    // ---- Phase 1: fixed text, images, contact, footer, SEO -------------------------------------------------

    public function test_public_pages_show_the_original_content_until_it_is_edited(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Empowering Business Through Technology')
            ->assertSee('Trusted Technology Partner')
            ->assertSee('assets/images/hero-illustration.png', false)
            ->assertSee('assets/images/company-logo.png', false)
            ->assertSee('Business Intelligence')
            ->assertSee('Indra Aliyudin.')
            ->assertSee('data-count="250"', false)
            ->assertSee('Ready to Transform Your Business?')
            ->assertSee('BI &amp; Analytics', false)
            ->assertSee('https://wa.me/6282125298452', false)
            ->assertSee('https://instagram.com/alfajarlogic', false)
            ->assertSee('https://linkedin.com/company/alfajarlogic', false);

        $this->get(route('about'))->assertOk()->assertSee('Company Timeline')->assertSee('Founded with a focus on analytics and enterprise solutions.');
        $this->get(route('services'))->assertOk()->assertSee('Power BI Dashboard')->assertSee('Microsoft Copilot');
        $this->get(route('portfolio'))->assertOk()->assertSee('Enterprise BI Platform')->assertSee('data-filter="analytics"', false);
        $this->get(route('contact'))->assertOk()->assertSee('Karawang, Indonesia')
            ->assertSee('https://formsubmit.co/admin@alfajarlogic.com', false)
            ->assertSee('https://www.google.com/maps?q=Karawang%20Indonesia&amp;z=13&amp;output=embed', false);
        $this->get(route('thank-you'))->assertOk()->assertSee('Your message has been sent successfully.');
    }

    public function test_every_admin_page_and_list_renders(): void
    {
        $this->admin();

        foreach (SiteContent::pageKeys() as $page) {
            $this->get(route('masterdata.site.page.edit', $page))->assertOk()->assertSee(SiteContent::PAGES[$page]['label']);
        }

        foreach (SiteItem::types() as $type) {
            $this->get(route('masterdata.site.items.index', $type))->assertOk()->assertSee(SiteItem::TYPES[$type]['label']);
            $this->get(route('masterdata.site.items.create', $type))->assertOk();
            $this->get(route('masterdata.site.items.edit', [$type, SiteItem::where('type', $type)->firstOrFail()]))->assertOk();
        }
    }

    public function test_admin_edits_text_and_it_shows_on_the_public_page_and_can_be_reset(): void
    {
        $this->admin();

        $this->savePage('home', ['hero_title' => 'A brand new headline', 'meta_title' => 'Custom tab title'])
            ->assertRedirect(route('masterdata.site.page.edit', 'home'));

        $this->get(route('home'))->assertOk()
            ->assertSee('A brand new headline')
            ->assertSee('<title>Custom tab title</title>', false)
            ->assertDontSee('Empowering Business Through Technology');

        $this->savePage('home', ['hero_title' => null, 'meta_title' => null])->assertSessionHasNoErrors();

        $this->get(route('home'))->assertSee('Empowering Business Through Technology');
    }

    public function test_text_is_plain_with_line_breaks_kept_and_markup_escaped(): void
    {
        $this->admin();

        $this->savePage('about', ['vision_text' => "First line\r\nSecond <b>line</b>"]);

        $this->get(route('about'))->assertOk()
            ->assertSee('First line<br />', false)
            ->assertSee('Second &lt;b&gt;line&lt;/b&gt;', false)
            ->assertDontSee('<b>line</b>', false);
    }

    public function test_site_wide_details_flow_to_the_footer_and_the_contact_page(): void
    {
        $this->admin();

        $this->savePage('global', [
            'company_name' => 'PT Baru Jaya',
            'contact_email' => 'halo@barujaya.test',
            'form_email' => 'inbox@barujaya.test',
            'contact_whatsapp' => '+62 812-3456-7890',
            'contact_instagram' => '@barujaya',
            'contact_linkedin' => 'linkedin.com/company/barujaya',
            'map_query' => 'Bandung Indonesia',
            'nav_about' => 'Tentang',
            'cta_title' => 'Talk to us today',
            'footer_tagline' => "Line one\nLine two",
        ])->assertSessionHasNoErrors();

        $home = $this->get(route('home'))->assertOk();
        $home->assertSee('mailto:halo@barujaya.test', false)
            ->assertSee('https://wa.me/628123456789'.'0', false)
            ->assertSee('https://instagram.com/barujaya', false)
            ->assertSee('https://linkedin.com/company/barujaya', false)
            ->assertSee('PT Baru Jaya. All rights reserved.', false)
            ->assertSee('>Tentang</a>', false)
            ->assertSee('Talk to us today')
            ->assertSee('Line one<br />', false);

        $this->get(route('contact'))->assertOk()
            ->assertSee('https://formsubmit.co/inbox@barujaya.test', false)
            ->assertSee('q=Bandung%20Indonesia', false);
    }

    public function test_images_can_be_uploaded_replaced_and_reset_to_the_default(): void
    {
        $this->admin();

        $this->savePage('home', [], ['images' => ['hero_image' => $this->image('hero.png')]])->assertSessionHasNoErrors();

        $first = SiteContent::uploadedImage('home.hero_image');
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);
        $this->get(route('home'))->assertSee('storage/'.$first, false)->assertDontSee('assets/images/hero-illustration.png', false);

        $this->savePage('home', [], ['images' => ['hero_image' => $this->image('second.png')]]);
        $second = SiteContent::uploadedImage('home.hero_image');
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->savePage('home', [], ['remove' => ['hero_image' => '1']]);
        $this->assertNull(SiteContent::uploadedImage('home.hero_image'));
        Storage::disk('public')->assertMissing($second);
        $this->get(route('home'))->assertSee('assets/images/hero-illustration.png', false);
    }

    public function test_saving_without_a_new_file_keeps_the_uploaded_image(): void
    {
        $this->admin();

        $this->savePage('global', [], ['images' => ['brand_logo' => $this->image('logo.png')]]);
        $path = SiteContent::uploadedImage('global.brand_logo');

        $this->savePage('global', ['company_name' => 'Renamed Co']);

        $this->assertSame($path, SiteContent::uploadedImage('global.brand_logo'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_invalid_site_content_is_rejected(): void
    {
        $this->admin();

        $this->savePage('global', ['contact_email' => 'not-an-email', 'map_link' => 'javascript:alert(1)', 'nav_home' => str_repeat('x', 41)])
            ->assertSessionHasErrors(['content.contact_email', 'content.map_link', 'content.nav_home']);

        $this->savePage('home', [], ['images' => ['hero_image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]])
            ->assertSessionHasErrors('images.hero_image');
        $this->savePage('home', [], ['images' => ['hero_image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('images.hero_image');
        $this->savePage('home', [], ['images' => ['hero_image' => UploadedFile::fake()->create('huge.png', 5000, 'image/png')]])
            ->assertSessionHasErrors('images.hero_image');

        $this->assertSame('Karawang, Indonesia', SiteContent::text('global.contact_address'));
    }

    public function test_unknown_pages_are_not_found_and_only_managers_can_edit(): void
    {
        $this->admin();
        $this->get('/erp/master-data/website/pages/nope')->assertNotFound();
        $this->get('/erp/master-data/website/lists/nope')->assertNotFound();

        auth()->logout();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['access-erp']);

        $this->actingAs($viewer)->get(route('masterdata.site.page.edit', 'home'))->assertForbidden();
        $this->actingAs($viewer)->put(route('masterdata.site.page.update', 'home'), ['content' => ['hero_title' => 'Hacked']])->assertForbidden();
        $this->actingAs($viewer)->get(route('masterdata.site.items.index', 'service'))->assertForbidden();
        $this->actingAs($viewer)->post(route('masterdata.site.items.store', 'service'), ['title' => 'x', 'body' => 'y'])->assertForbidden();
        $this->actingAs($viewer)->delete(route('masterdata.site.items.destroy', ['service', SiteItem::where('type', 'service')->first()]))->assertForbidden();

        $this->assertSame('Empowering Business Through Technology', SiteContent::text('home.hero_title'));
    }

    public function test_unknown_content_paths_fail_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SiteContent::text('home.no_such_field');
    }

    // ---- Phase 2: repeating cards (services, training, statistics, timeline, footer links) -----------------

    public function test_the_current_site_content_is_seeded_by_migration(): void
    {
        $counts = SiteItem::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type')->all();

        $this->assertEquals([
            'home_service' => 4, 'service' => 12, 'training' => 12, 'stat' => 4,
            'timeline' => 3, 'testimonial' => 3, 'portfolio' => 4, 'footer_link' => 4,
        ], $counts);
        $this->assertSame(['analytics', 'development', 'training'], MasterData::codes('portfolio_category'));
    }

    public function test_public_pages_still_render_when_cached_items_are_read_back_from_a_serializing_store(): void
    {
        // The app's cache does not unserialize classes (cache.serializable_classes), so cached models must not be cached as objects.
        config(['cache.stores.array.serialize' => true, 'cache.serializable_classes' => false]);
        Cache::purge('array');

        foreach (['home', 'about', 'services', 'portfolio', 'contact'] as $page) {
            $this->get(route($page))->assertOk();
            $this->get(route($page))->assertOk(); // second visit is served from the cache
        }

        $this->assertInstanceOf(SiteItem::class, SiteItem::active('service')->first());
    }

    public function test_admin_adds_edits_hides_and_deletes_a_service_card(): void
    {
        $this->admin();

        $this->post(route('masterdata.site.items.store', 'service'), ['icon' => '🚀', 'title' => 'Rocket Consulting', 'body' => 'We launch things.', 'is_active' => '1'])
            ->assertRedirect(route('masterdata.site.items.index', 'service'));

        $item = SiteItem::where('title', 'Rocket Consulting')->firstOrFail();
        $this->assertSame(13, $item->sort_order);
        $this->get(route('services'))->assertSee('Rocket Consulting')->assertSee('We launch things.');

        $this->put(route('masterdata.site.items.update', ['service', $item]), ['icon' => '🚀', 'title' => 'Rocket Advisory', 'body' => 'We launch things.', 'sort_order' => 0, 'is_active' => '1']);
        $services = $this->get(route('services'))->assertSee('Rocket Advisory')->assertDontSee('Rocket Consulting');
        $this->assertLessThan(strpos($services->getContent(), '<h3>Business Intelligence</h3>'), strpos($services->getContent(), '<h3>Rocket Advisory</h3>'));

        $this->put(route('masterdata.site.items.update', ['service', $item]), ['title' => 'Rocket Advisory', 'body' => 'We launch things.', 'is_active' => '0']);
        $this->get(route('services'))->assertDontSee('Rocket Advisory');
        $this->assertFalse($item->fresh()->is_active);

        $this->delete(route('masterdata.site.items.destroy', ['service', $item]))->assertRedirect(route('masterdata.site.items.index', 'service'));
        $this->assertModelMissing($item);
    }

    public function test_statistics_timeline_training_and_footer_links_drive_their_sections(): void
    {
        $this->admin();

        $this->post(route('masterdata.site.items.store', 'stat'), ['title' => 'Countries', 'number' => 7, 'is_active' => '1']);
        $this->post(route('masterdata.site.items.store', 'timeline'), ['title' => '2026', 'body' => 'Opened a second office.', 'is_active' => '1']);
        $this->post(route('masterdata.site.items.store', 'training'), ['title' => 'Tableau', 'body' => 'Visual analytics.', 'is_active' => '1']);
        $this->post(route('masterdata.site.items.store', 'footer_link'), ['title' => 'Our Blog', 'link_url' => '/portfolio', 'is_active' => '1']);
        $this->post(route('masterdata.site.items.store', 'footer_link'), ['title' => 'Elsewhere', 'link_url' => 'https://example.com/x', 'is_active' => '1']);

        $this->get(route('home'))->assertSee('data-count="7"', false)->assertSee('Countries')
            ->assertSee('href="'.url('/portfolio').'">Our Blog</a>', false)
            ->assertSee('href="https://example.com/x">Elsewhere</a>', false);
        $this->get(route('about'))->assertSee('2026')->assertSee('Opened a second office.');
        $this->get(route('services'))->assertSee('Tableau')->assertSee('Visual analytics.');
    }

    public function test_item_validation(): void
    {
        $this->admin();
        $store = fn (string $type, array $data) => $this->post(route('masterdata.site.items.store', $type), $data);

        $store('service', ['title' => '', 'body' => ''])->assertSessionHasErrors(['title', 'body']);
        $store('stat', ['title' => 'Projects'])->assertSessionHasErrors('number');
        $store('stat', ['title' => 'Projects', 'number' => -1])->assertSessionHasErrors('number');
        $store('footer_link', ['title' => 'X', 'link_url' => 'javascript:alert(1)'])->assertSessionHasErrors('link_url');
        $store('footer_link', ['title' => 'X', 'link_url' => '//evil.example'])->assertSessionHasErrors('link_url');
        $store('footer_link', ['title' => 'X', 'link_url' => 'services'])->assertSessionHasErrors('link_url');
        $store('footer_link', ['title' => 'X', 'link_url' => 'mailto:hi@example.com'])->assertSessionHasNoErrors();
        $store('testimonial', ['title' => 'A', 'body' => 'B', 'link_url' => 'not a url'])->assertSessionHasErrors('link_url');

        $this->assertSame(0, SiteItem::where('title', 'Projects')->where('number', -1)->count());
    }

    public function test_fields_outside_the_type_are_ignored_and_items_stay_in_their_own_list(): void
    {
        $this->admin();

        $this->post(route('masterdata.site.items.store', 'training'), ['title' => 'Only Title', 'body' => 'Body', 'number' => 99, 'category' => 'analytics', 'link_url' => '/x', 'type' => 'stat']);

        $item = SiteItem::where('title', 'Only Title')->firstOrFail();
        $this->assertSame('training', $item->type);
        $this->assertNull($item->number);
        $this->assertNull($item->category);
        $this->assertNull($item->link_url);

        $service = SiteItem::where('type', 'service')->first();
        $this->get(route('masterdata.site.items.edit', ['training', $service]))->assertNotFound();
        $this->put(route('masterdata.site.items.update', ['training', $service]), ['title' => 'Moved', 'body' => 'x'])->assertNotFound();
        $this->delete(route('masterdata.site.items.destroy', ['training', $service]))->assertNotFound();
        $this->assertNotSame('Moved', $service->fresh()->title);
    }

    // ---- Phase 3: testimonials and portfolio ---------------------------------------------------------------

    public function test_testimonials_rotate_on_the_home_page_and_the_section_hides_when_empty(): void
    {
        $this->admin();

        $this->post(route('masterdata.site.items.store', 'testimonial'), [
            'title' => 'Dewi Lestari', 'subtitle' => 'Data Analyst', 'body' => 'Sangat membantu.', 'link_url' => 'https://maps.app.goo.gl/abc123',
            'sort_order' => 0, 'is_active' => '1', 'image' => $this->image('dewi.png'),
        ])->assertSessionHasNoErrors();

        $item = SiteItem::where('title', 'Dewi Lestari')->firstOrFail();
        Storage::disk('public')->assertExists($item->image_path);

        $home = $this->get(route('home'))->assertOk()
            ->assertSee('Sangat membantu.')->assertSee('Data Analyst')
            ->assertSee('storage/'.$item->image_path, false)
            ->assertSee('https://maps.app.goo.gl/abc123', false);
        $this->assertSame(1, substr_count($home->getContent(), 'testimonial-card active'));
        $this->assertLessThan(strpos($home->getContent(), 'Indra Aliyudin.'), strpos($home->getContent(), 'Dewi Lestari'));

        $this->put(route('masterdata.site.items.update', ['testimonial', $item]), [
            'title' => 'Dewi Lestari', 'body' => 'Sangat membantu.', 'is_active' => '1', 'remove_image' => '1',
        ]);
        Storage::disk('public')->assertMissing($item->image_path);
        $this->assertNull($item->fresh()->imageUrl());

        SiteItem::where('type', 'testimonial')->update(['is_active' => false]);
        \Cache::forget('site.items.testimonial');
        $this->get(route('home'))->assertOk()->assertDontSee('id="testimonials"', false);
    }

    public function test_deleting_an_item_removes_its_uploaded_image(): void
    {
        $this->admin();

        $this->post(route('masterdata.site.items.store', 'portfolio'), [
            'title' => 'Temp', 'body' => 'Body', 'category' => 'analytics', 'is_active' => '1', 'image' => $this->image('temp.png'),
        ])->assertSessionHasNoErrors();
        $item = SiteItem::where('title', 'Temp')->firstOrFail();
        Storage::disk('public')->assertExists($item->image_path);

        $this->delete(route('masterdata.site.items.destroy', ['portfolio', $item]));

        Storage::disk('public')->assertMissing($item->image_path);
    }

    public function test_portfolio_filters_follow_the_master_data_categories(): void
    {
        $admin = $this->admin();

        $this->post(route('masterdata.store'), ['group' => 'portfolio_category', 'label' => 'Mobile Apps'])->assertSessionHasNoErrors();
        $this->assertContains('mobile_apps', MasterData::codes('portfolio_category'));

        // A category with no project yet gets no filter button.
        $this->get(route('portfolio'))->assertDontSee('data-filter="mobile_apps"', false);

        $this->post(route('masterdata.site.items.store', 'portfolio'), [
            'title' => 'Field Sales App', 'body' => 'Offline-first.', 'category' => 'mobile_apps', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->get(route('portfolio'))->assertOk()
            ->assertSee('data-filter="mobile_apps"', false)->assertSee('>Mobile Apps</button>', false)
            ->assertSee('data-category="mobile_apps"', false)->assertSee('Field Sales App');

        // Unknown categories are refused, and one in use can be neither deleted…
        $this->post(route('masterdata.site.items.store', 'portfolio'), ['title' => 'X', 'body' => 'Y', 'category' => 'nope'])->assertSessionHasErrors('category');
        $option = MasterDataItem::where('group', 'portfolio_category')->where('code', 'mobile_apps')->firstOrFail();
        $this->delete(route('masterdata.destroy', $option))->assertSessionHas('error');
        $this->assertModelExists($option);

        // …while a deactivated one no longer shows in the picker for new projects.
        $this->put(route('masterdata.update', $option), ['label' => 'Mobile Apps', 'sort_order' => 9, 'is_active' => '0']);
        $this->post(route('masterdata.site.items.store', 'portfolio'), ['title' => 'Z', 'body' => 'Y', 'category' => 'mobile_apps'])->assertSessionHasErrors('category');
        $this->get(route('masterdata.site.items.create', 'portfolio'))->assertDontSee('Mobile Apps');
        $this->assertSame($admin->id, auth()->id());
    }

    public function test_seeded_portfolio_items_keep_their_bundled_images_and_can_be_given_a_new_one(): void
    {
        $this->admin();

        $this->get(route('portfolio'))->assertSee('assets/images/portfolio-1.png', false);

        $item = SiteItem::where('title', 'Enterprise BI Platform')->firstOrFail();
        $this->put(route('masterdata.site.items.update', ['portfolio', $item]), [
            'title' => $item->title, 'body' => $item->body, 'category' => 'analytics', 'is_active' => '1',
            'image' => $this->image('new.png'),
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->get(route('portfolio'))->assertSee('storage/'.$item->image_path, false)->assertDontSee('assets/images/portfolio-1.png', false);
    }
}
