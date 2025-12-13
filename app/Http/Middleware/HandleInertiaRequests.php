<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Session;

use App\Services\CategorizationService;
use App\Models\SystemSetting;              // 👈 add
use Illuminate\Support\Facades\Storage;    // 👈 add

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
        // Load your single settings row (created by SettingsController@index/updateGeneral)
        $settings = SystemSetting::first(); // null-safe below

        $branding = [
            'school_name' => $settings?->school_name ?? 'Rizal Central School',
            'logo_url' => $settings?->logo_path ? Storage::url($settings->logo_path) : null,
        ];

        return array_merge(parent::share($request), [
            // Authenticated user data + Notifications
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'role' => $request->user()->role,
                    'photo_path' => $request->user()->photo_path,
                ] : null,

                // --- NEW: Pass unread notifications to frontend ---
                'notifications' => $request->user()
                    ? $request->user()->unreadNotifications
                    : [],
            ],

            // Any flash error/success messages
            'error' => Session::pull('error'),
            'success' => Session::pull('success'), // Added success just in case

            // Teacher document types (global)
            'teacherDocumentTypes' => (new CategorizationService)->getTeacherDocumentTypes(),

            // Global branding (school name + logo URL) for Header/Sidebar/etc.
            'branding' => $branding,
        ]);
    }
}