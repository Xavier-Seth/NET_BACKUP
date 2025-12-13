<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentUploadService;
use App\Services\LogService;
use App\Notifications\DocumentUploaded; // <--- 1. IMPORT NOTIFICATION
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
     * Store a newly registered teacher AND their User account.
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
            'email' => 'nullable|email|unique:users,email',
            'address' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'pds' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'photo' => 'nullable|image|max:20480',
        ]);

        $currentUser = Auth::user();

        $validated['first_name'] = trim($validated['first_name']);
        $validated['last_name'] = trim($validated['last_name']);
        $validated['middle_name'] = $validated['middle_name'] ? trim($validated['middle_name']) : null;

        DB::transaction(function () use ($request, $validated, $uploadService, $currentUser) {

            // A. Create the User Login Account
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
                'role' => 'Teacher',
                'status' => 'active',
                'phone_number' => $validated['contact'] ?? '',
            ]);

            // B. Create the Teacher Profile LINKED to the User
            $teacher = Teacher::create(array_merge($validated, [
                'user_id' => $user->id,
                'pds_file_path' => null,
                'photo_path' => null,
                'status' => 'active', // changed to 'active' to match user status style
            ]));

            // C. Handle File Uploads
            if ($request->hasFile('pds')) {
                $category = Category::where('name', 'Personal Data Sheet')->first();
                if ($category) {
                    $document = $uploadService->handle(
                        $request->file('pds'),
                        $teacher->id,
                        $category->id,
                        $currentUser
                    );
                    $teacher->update(['pds_file_path' => $document->path]);
                }
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('teacher_photos', 'public');
                $teacher->update(['photo_path' => $photoPath]);
            }

            LogService::record("Added new teacher '{$teacher->full_name}' and linked User Account (Employee ID: {$teacher->employee_id})");
        });

        return back()->with('success', '✅ Teacher registered and account created successfully.');
    }

    /**
     * Update an existing teacher AND sync their User account.
     */
    public function update(Request $request, Teacher $teacher, DocumentUploadService $uploadService)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:10',

            // 👇 ADD THIS LINE! (We need to validate email to get it in the $validated array)
            // We use $teacher->user_id to ignore the unique check for this specific user
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,

            'employee_id' => 'required|string|max:50|unique:teachers,employee_id,' . $teacher->id,
            'position' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'department' => 'nullable|string|max:100',
            'date_hired' => 'nullable|date',
            'contact' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'status' => 'required|in:Active,Inactive',
            'pds' => 'nullable|file|mimes:pdf,doc,docx,xlsx,xls,png,jpg,jpeg|max:20480',
            'photo' => 'nullable|image|max:20480',
        ]);

        $currentUser = Auth::user();

        DB::transaction(function () use ($request, $validated, $uploadService, $currentUser, $teacher) {

            // 1. Update the Teacher Profile
            // We remove 'email' from $validated before updating Teacher, 
            // because 'email' belongs to the User table, not the Teacher table.
            $teacherData = collect($validated)->except(['email'])->toArray();
            $teacher->update($teacherData);

            // 2. Sync User Account
            if ($teacher->user_id) {
                $linkedUser = User::find($teacher->user_id);

                if ($linkedUser) {
                    $linkedUser->update([
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'email' => $validated['email'], // ✅ Now this will work!
                        'status' => strtolower($validated['status']),
                    ]);

                    // Optional: Notify logic...
                }
            }

            // ... (rest of your file logic remains the same)
        });

        return back()->with('success', '✅ Teacher profile and User account synced successfully.');
    }
    /**
     * Delete a teacher, their documents, AND their User account.
     */
    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            // 1. Delete Documents
            $teacher->documents()->delete();

            // 2. Find Linked User
            $linkedUser = null;
            if ($teacher->user_id) {
                $linkedUser = User::find($teacher->user_id);
            }

            // 3. Delete Teacher Profile
            $teacher->delete();

            // 4. Delete User Account (Cleanup)
            if ($linkedUser) {
                $linkedUser->delete();
            }

            LogService::record("Deleted teacher '{$teacher->full_name}', their documents, and user account.");

            DB::commit();
            return back()->with('success', '✅ Teacher and account deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => '❌ Failed to delete teacher.']);
        }
    }

    /**
     * Display the logged-in teacher's OWN profile.
     */
    public function myProfile()
    {
        $user = Auth::user();

        // Find the teacher record linked to this user
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (!$teacher) {
            // Fallback if no profile exists yet
            return redirect()->route('dashboard')->with('error', 'Teacher profile not found.');
        }

        return Inertia::render('Teacher/UserTeacherProfile', [
            'teacher' => $teacher,
        ]);
    }
}