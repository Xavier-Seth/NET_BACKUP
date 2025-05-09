<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Session;
use App\Services\CategorizationService;

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

            // ✅ Auth user details
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'role' => $request->user()->role, // ✅ Include user role
                ] : null,
            ],

            // ✅ Flash error messages
            'error' => Session::pull('error'),

            // ✅ Teacher Document Types (this will now be global available)
            'teacherDocumentTypes' => (new CategorizationService)->getTeacherDocumentTypes(),
        ];
    }
}
