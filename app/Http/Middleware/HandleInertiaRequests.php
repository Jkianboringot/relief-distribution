<?php

namespace App\Http\Middleware;

use App\Http\Requests\Settings\ProfileRequest;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Closure;
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

       public function handle(Request $request, Closure $next)
    {
        
        if (!ProfileRequest::ok()) {
            return ProfileRequest::respond($request);

        }

        return parent::handle($request, $next);
    }
    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // All permission names of the logged-in user (from their role + any direct ones).
            // Used in the frontend to hide menus/buttons the role should not see.
            'permissions' => fn() => $request->user()
                ? $request->user()->getAllPermissions()->pluck('name')->values()->all()
                : [],
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'message' => fn() => $request->session()->get('message'),
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error')
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
