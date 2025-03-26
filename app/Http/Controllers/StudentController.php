<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StudentController extends Controller
{
    // ✅ Student Records Listing
    public function index()
    {
        $students = Student::select('id', 'lrn', 'first_name', 'middle_name', 'last_name', 'grade_level', 'school_year')->get();

        return Inertia::render('StudentRecords', [
            'students' => $students,
        ]);
    }

    // ✅ Register New Student
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lrn' => 'required|string|unique:students',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'birthdate' => 'required|date',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'nullable|string|max:50',
            'citizenship' => 'nullable|string|max:100',
            'place_of_birth' => 'nullable|string|max:255',
            'school_year' => 'nullable|string|max:20',
            'guardian_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'form137' => 'nullable|file',
            'psa' => 'nullable|file',
            'eccrd' => 'nullable|file',
        ]);

        // ✅ Save student record
        $student = Student::create($validated);

        // ✅ Upload and save documents
        foreach (['form137' => 'Form 137', 'psa' => 'PSA', 'eccrd' => 'ECCRD'] as $field => $category) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $path = $file->store('documents', 'public');

                Document::create([
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'category' => $category,
                    'lrn' => $validated['lrn'],
                ]);
            }
        }

        return redirect()->route('students.register')->with('success', 'Student and documents uploaded successfully.');
    }
}
