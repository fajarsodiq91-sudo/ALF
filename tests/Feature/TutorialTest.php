<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Tutorial;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TutorialTest extends TestCase
{
    use RefreshDatabase;

    /** Pages that are not part of the ERP or the customer-facing flows, so the tutorial does not explain them. */
    private const NOT_DOCUMENTED = [
        'home', 'about', 'services', 'portfolio', 'contact', 'thank-you', // public company website
        'login', 'register', 'password.*', 'verification.*', // sign-in screens
        'storage.*',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('tutorial.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_access_erp_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get(route('tutorial.index'))->assertForbidden();
    }

    public function test_every_erp_user_sees_the_tutorial_menu_and_overview(): void
    {
        $staff = $this->user('Staff');

        $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertSee(route('tutorial.index'), false);
        $this->actingAs($staff)->get(route('tutorial.index'))->assertOk()->assertSee('Panduan menggunakan ERP');
    }

    public function test_overview_only_lists_topics_the_user_may_read(): void
    {
        $this->actingAs($this->user('Staff'))->get(route('tutorial.index'))
            ->assertOk()
            ->assertSee(route('tutorial.show', 'getting-started'), false)
            ->assertDontSee(route('tutorial.show', 'finance-tax'), false)
            ->assertDontSee(route('tutorial.show', 'settings'), false);

        $this->actingAs($this->user('Finance'))->get(route('tutorial.index'))
            ->assertSee(route('tutorial.show', 'finance-tax'), false)
            ->assertSee(route('tutorial.show', 'hr-payroll'), false)
            ->assertDontSee(route('tutorial.show', 'settings'), false);
    }

    public function test_a_topic_needs_the_permission_of_its_module(): void
    {
        $this->actingAs($this->user('Staff'))->get(route('tutorial.show', 'finance-tax'))->assertForbidden();
        $this->actingAs($this->user('Finance'))->get(route('tutorial.show', 'finance-tax'))->assertOk();
        $this->actingAs($this->user('Finance'))->get(route('tutorial.show', 'settings'))->assertForbidden();
    }

    public function test_unknown_topic_is_not_found(): void
    {
        $this->actingAs($this->user('Super Admin'))->get('/erp/tutorial/does-not-exist')->assertNotFound();
    }

    public function test_every_topic_renders_for_a_super_admin(): void
    {
        $admin = $this->user('Super Admin');

        foreach (Tutorial::slugs() as $slug) {
            $this->actingAs($admin)->get(route('tutorial.show', $slug))
                ->assertOk()
                ->assertSee(Tutorial::TOPICS[$slug]['title']);
        }
    }

    public function test_registry_is_consistent(): void
    {
        $viewFiles = collect(glob(resource_path('views/erp/tutorial/topics/*.blade.php')))
            ->map(fn (string $path) => basename($path, '.blade.php'))
            ->sort()->values()->all();

        $this->assertSame(collect(Tutorial::slugs())->sort()->values()->all(), $viewFiles, 'Every topic needs a view in resources/views/erp/tutorial/topics, and every view a topic in App\Services\Tutorial.');

        foreach (Tutorial::TOPICS as $slug => $topic) {
            $this->assertContains($topic['group'], Tutorial::GROUPS, "Topic {$slug} uses an unknown group.");

            if ($topic['route']) {
                $this->assertTrue(Route::has($topic['route']), "Topic {$slug} points at the missing route {$topic['route']}.");
            }

            foreach ((array) $topic['permission'] as $permission) {
                $this->assertNotNull(Permission::where('name', $permission)->first(), "Topic {$slug} needs the unknown permission {$permission}.");
            }
        }
    }

    public function test_topics_only_cover_routes_that_exist(): void
    {
        $names = $this->routeNames();

        foreach (Tutorial::TOPICS as $slug => $topic) {
            foreach ($topic['covers'] as $pattern) {
                $this->assertTrue(
                    $names->contains(fn (string $name) => Str::is($pattern, $name)),
                    "Topic {$slug} covers {$pattern}, but no such route exists any more. Update or remove it in App\Services\Tutorial and the topic view.",
                );
            }
        }
    }

    public function test_every_page_of_the_app_is_covered_by_a_tutorial_topic(): void
    {
        $missing = $this->routeNames('GET')
            ->reject(fn (string $name) => Str::is(self::NOT_DOCUMENTED, $name))
            ->reject(fn (string $name) => Tutorial::documents($name))
            ->values()->all();

        $this->assertSame([], $missing, 'These pages are not explained in the tutorial. Document them in a topic view (resources/views/erp/tutorial/topics) and list the route in that topic\'s "covers" in App\Services\Tutorial: '.implode(', ', $missing));
    }

    /** @return Collection<int, string> */
    private function routeNames(?string $method = null): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getName() && ($method === null || in_array($method, $route->methods(), true)))
            ->map(fn ($route) => $route->getName())
            ->unique()->values();
    }
}
