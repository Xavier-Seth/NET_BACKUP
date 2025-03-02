<?php

namespace App\Http\Controllers; // ✅ Add this namespace

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function __construct()
    {
        Inertia::share([
            'flash' => function () {
                return session()->get('flash');
            },
        ]);
    }
}
