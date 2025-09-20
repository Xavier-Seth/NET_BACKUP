<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\DocumentUploadService;

class StudentController extends Controller
{
    // Student Records Listing
    public function index()
    {
        $students = Student::select(
            'id',
            'lrn',
            'first_name',
            'middle_name',
            'last_name',
            'grade_level',
            'school_year'
        )->get();

        return Inertia::render('StudentRecords', [
            'students' => $students,
        ]);
    }

    // Fetch One Student (no grades)
    public function show($lrn)
    {
        $student = Student::where('lrn', $lrn)->firstOrFail();
        return response()->json($student);
    }

    // Register New Student
    public function store(Request $request, DocumentUploadService $uploadService)
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
            'grade_level' => 'required|string|max:50',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
        ]);

        $student = Student::create($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $uploadService->handle(
                    $file,
                    $request->input('categories')[$index] ?? 'Uncategorized',
                    $request->input('lrns')[$index] ?? $validated['lrn'],
                    $request->input('types')[$index] ?? 'student'
                );
            }
        }

        return redirect()
            ->route('students.register')
            ->with('success', 'Student and documents uploaded successfully.');
    }
}
