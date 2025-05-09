<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    /**
     * Store a newly registered teacher.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id',
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'department' => 'nullable|string|max:100',
            'date_hired' => 'nullable|date',
            'contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:teachers,email',
            'address' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'pds' => 'nullable|file|mimes:pdf|max:20480',
        ]);

        // Save uploaded PDS file if provided
        $path = null;
        if ($request->hasFile('pds')) {
            $path = $request->file('pds')->store('teacher-pds', 'public');
        }

        // Create the teacher record
        Teacher::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'full_name' => $request->full_name, // ✅ still saving full_name separately
            'name_extension' => $request->name_extension,
            'employee_id' => $request->employee_id,
            'position' => $request->position,
            'birth_date' => $request->birth_date,
            'department' => $request->department,
            'date_hired' => $request->date_hired,
            'contact' => $request->contact,
            'email' => $request->email,
            'address' => $request->address,
            'remarks' => $request->remarks,
            'pds_file_path' => $path,
        ]);

        return back()->with('success', '✅ Teacher registered successfully.');
    }
}
