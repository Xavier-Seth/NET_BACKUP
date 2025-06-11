<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Category;
use App\Models\User;
use App\Services\DocumentUploadService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    /**
     * Store a newly registered teacher and upload their PDS via DocumentUploadService.
     */
    public function store(Request $request, DocumentUploadService $uploadService)
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
            'pds' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
        ]);

        $user = Auth::user();

        // Step 1: Create teacher record first
        $teacher = Teacher::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'full_name' => $request->full_name,
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
            'pds_file_path' => null,
        ]);

        // Step 2: Upload PDS if file exists
        if ($request->hasFile('pds')) {
            $pdsFile = $request->file('pds');
            $category = Category::where('name', 'Personal Data Sheet')->first();

            if ($category) {
                $document = $uploadService->handle(
                    $pdsFile,
                    $teacher->id,
                    $category->id,
                    $user
                );

                $teacher->update([
                    'pds_file_path' => $document->path
                ]);
            }
        }

        // ✅ Log the activity
        LogService::record("Added new teacher '{$teacher->full_name}' (Employee ID: {$teacher->employee_id})");

        return back()->with('success', '✅ Teacher registered successfully.');
    }
}
