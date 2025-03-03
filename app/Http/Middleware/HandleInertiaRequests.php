<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'role' => $request->user()->role, // ✅ Ensures role is explicitly passed
                ] : null,
            ],
            'error' => Session::pull('error'), // ✅ Retrieves and clears session error
        ];
    }
}
