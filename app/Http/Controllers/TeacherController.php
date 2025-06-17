<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Category;
use App\Models\Document;
use App\Services\DocumentUploadService;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TeacherController extends Controller
{
    /**
     * Display all teachers.
     */
    public function index()
    {
        $teachers = Teacher::select('id', 'full_name', 'status', 'photo_path')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Teacher/Teachers', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(Teacher $teacher)
    {
        return Inertia::render('Teacher/EditTeacher', [
            'teacher' => $teacher,
        ]);
    }

    /**
     * View teacher profile (read-only) with their uploaded documents.
     */
    public function show(Teacher $teacher)
    {
        $documents = Document::with('category')
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->get();

        return Inertia::render('Teacher/ViewTeacher', [
            'teacher' => $teacher,
            'documents' => $documents,
        ]);
    }

    /**
     * Update an existing teacher.
     */
    public function update(Request $request, Teacher $teacher, DocumentUploadService $uploadService)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'employee_id' => 'required|string|max:50|unique:teachers,employee_id,' . $teacher->id,
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'department' => 'nullable|string|max:100',
            'date_hired' => 'nullable|date',
            'contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:teachers,email,' . $teacher->id,
            'address' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'required|in:Active,Inactive',
            'pds' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'photo' => 'nullable|image|max:20480',
        ]);

        $user = Auth::user();

        $validated['first_name'] = trim($validated['first_name']);
        $validated['last_name'] = trim($validated['last_name']);
        $validated['middle_name'] = $validated['middle_name'] ? trim($validated['middle_name']) : null;

        $teacher->update($validated);

        if ($request->hasFile('pds')) {
            $category = Category::where('name', 'Personal Data Sheet')->first();

            if ($category) {
                $document = $uploadService->handle(
                    $request->file('pds'),
                    $teacher->id,
                    $category->id,
                    $user
                );

                $teacher->update(['pds_file_path' => $document->path]);
            }
        }

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teacher_photos', 'public');
            $teacher->update(['photo_path' => $photoPath]);
        }

        LogService::record("Updated teacher '{$teacher->full_name}' (Employee ID: {$teacher->employee_id})");

        return back()->with('success', '✅ Teacher profile updated successfully.');
    }

    /**
     * Store a newly registered teacher.
     */
    public function store(Request $request, DocumentUploadService $uploadService)
    {
        $validated = $request->validate([
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
            'photo' => 'nullable|image|max:20480',
        ]);

        $user = Auth::user();

        $validated['first_name'] = trim($validated['first_name']);
        $validated['last_name'] = trim($validated['last_name']);
        $validated['middle_name'] = $validated['middle_name'] ? trim($validated['middle_name']) : null;

        $teacher = Teacher::create(array_merge($validated, [
            'pds_file_path' => null,
            'photo_path' => null,
            'status' => 'Active',
        ]));

        if ($request->hasFile('pds')) {
            $category = Category::where('name', 'Personal Data Sheet')->first();

            if ($category) {
                $document = $uploadService->handle(
                    $request->file('pds'),
                    $teacher->id,
                    $category->id,
                    $user
                );

                $teacher->update(['pds_file_path' => $document->path]);
            }
        }

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('teacher_photos', 'public');
            $teacher->update(['photo_path' => $photoPath]);
        }

        LogService::record("Added new teacher '{$teacher->full_name}' (Employee ID: {$teacher->employee_id})");

        return back()->with('success', '✅ Teacher registered successfully.');
    }

    /**
     * Delete a teacher and their documents.
     */
    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $teacher->documents()->delete();
            $teacher->delete();

            LogService::record("Deleted teacher '{$teacher->full_name}' and their documents");

            DB::commit();
            return back()->with('success', '✅ Teacher and related documents deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '❌ Failed to delete teacher.']);
        }
    }
}
