<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Session;
use App\Services\CategorizationService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Authenticated user — now including photo_path
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'role' => $request->user()->role,
                    'photo_path' => $request->user()->photo_path, // ← add this
                ] : null,
            ],

            // Any flash error messages
            'error' => Session::pull('error'),

            // Teacher document types (global)
            'teacherDocumentTypes' => (new CategorizationService)->getTeacherDocumentTypes(),
        ]);
    }
}
