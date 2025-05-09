<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolPropertyDocument;
use App\Models\Category;
use Inertia\Inertia;

class SchoolPropertyDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = SchoolPropertyDocument::with(['user', 'category'])->orderBy('created_at', 'desc');

        // Filter by Category (ICS / RIS)
        if ($request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Search by Document No or Name
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('document_no', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        $documents = $query->paginate(20);
        $categories = Category::whereIn('name', ['ICS', 'RIS'])->get();

        return Inertia::render('DocumentsSchoolProperties', [
            'documents' => $documents,
            'categories' => $categories,
            'filters' => $request->only('category', 'search'),
        ]);
    }
}
