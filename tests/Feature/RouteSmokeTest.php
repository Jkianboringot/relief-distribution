<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Hits every GET route that has no {param} and reports any 404 / 500.
 * Run: php artisan test --filter=RouteSmokeTest
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function routes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods()))
            ->filter(fn ($r) => ! str_contains($r->uri(), '{'))
            ->filter(fn ($r) => in_array('auth', $r->gatherMiddleware()))
            ->map(fn ($r) => '/' . ltrim($r->uri(), '/'))
            ->unique()
            ->values()
            ->all();
    }

    private function check(string $role, bool $allow403): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        $bad = [];

        foreach ($this->routes() as $uri) {
            $status = $this->get($uri)->getStatusCode();

            $ok = $status < 400 || ($allow403 && $status === 403);

            if (! $ok) {
                $bad[] = "{$uri} => {$status}";
            }
        }

        $this->assertEmpty($bad, "Failing routes for {$role}:\n" . implode("\n", $bad));
    }

    /** Admin should reach everything, so a 403 counts as a failure too. */
    public function test_admin_routes(): void
    {
        $this->check('lgustaff', false);
    }

    /** Barangay official is blocked from some pages on purpose, so 403 is fine. */
    public function test_barangay_routes(): void
    {
        $this->check('barangayofficial', true);
    }
}