<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    // ✅ Render Users Page
    public function index()
    {
        return Inertia::render('Users');
    }

    // ✅ API: Return User List as JSON
    public function getUsers()
    {
        return response()->json(User::select('id', 'first_name', 'last_name', 'role', 'email', 'status')->get());
    }
}


