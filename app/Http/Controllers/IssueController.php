<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueAttachment;
use App\Models\IssueCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueController extends Controller
{
    private function getAccessibleIssue(int $id): Issue
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return Issue::with(['user', 'category', 'attachments'])->findOrFail($id);
        }

        return $user->issues()->with(['category', 'attachments'])->findOrFail($id);
    }

    public function index(): View
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $issues = Issue::with(['user', 'category', 'attachments'])
                ->latest()
                ->paginate(15);
        } else {
            $issues = $user->issues()
                ->with(['category', 'attachments'])
                ->latest()
                ->paginate(10);
        }

        return view('issues.index', compact('issues'));
    }

    public function create(): View
    {
        $categories = IssueCategory::all();

        return view('issues.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'issue_category_id' => ['required', 'exists:issue_categories,id'],
            'issue_date'        => ['required', 'date', 'before_or_equal:now'],
            'details'           => ['required', 'string', 'max:5000'],
            'attachments'       => ['nullable', 'array', 'max:3'],
            'attachments.*'     => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'issue_date.before_or_equal' => 'Issue date and time cannot be in the future.',
            'attachments.max'           => 'You can upload a maximum of 3 attachments.',
            'attachments.*.max'         => 'Each attachment must be under 2MB.',
            'attachments.*.mimes'       => 'Attachments must be a PDF or Image file (pdf, jpg, jpeg, png, webp).',
        ]);

        $issue = Auth::user()->issues()->create([
            'issue_category_id' => $validated['issue_category_id'],
            'issue_date'        => $validated['issue_date'],
            'details'           => $validated['details'],
            'status'            => Issue::STATUS_OPEN, // Initially '0'
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    // On Windows/Laragon, getRealPath() can return false due to temp folder permissions,
                    // causing store() to throw ValueError. Bypass it by reading the raw temp path.
                    $hashName = $file->hashName();
                    $path = 'attachments/' . $hashName;
                    
                    \Illuminate\Support\Facades\Storage::disk('public')->put(
                        $path,
                        file_get_contents($file->getPathname())
                    );
                    
                    $issue->attachments()->create([
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type'     => $file->getClientMimeType(),
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }
        }

        return redirect()->route('issues.index')->with('success', 'Issue created successfully!');
    }

    public function show(int $id): View
    {
        $issue = $this->getAccessibleIssue($id);

        return view('issues.show', compact('issue'));
    }

    public function edit(int $id): View
    {
        $issue = $this->getAccessibleIssue($id);
        $categories = IssueCategory::all();

        return view('issues.edit', compact('issue', 'categories'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $issue = $this->getAccessibleIssue($id);
        $user = Auth::user();

        $rules = [
            'issue_category_id' => ['required', 'exists:issue_categories,id'],
            'issue_date'        => ['required', 'date', 'before_or_equal:now'],
            'details'           => ['required', 'string', 'max:5000'],
            'attachments'       => ['nullable', 'array', 'max:3'],
            'attachments.*'     => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($user->isAdmin()) {
            $rules['status'] = ['required', 'in:0,1,2,3,4'];
        }

        $validated = $request->validate($rules, [
            'issue_date.before_or_equal' => 'Issue date and time cannot be in the future.',
            'attachments.max'           => 'You can upload a maximum of 3 attachments.',
            'attachments.*.max'         => 'Each attachment must be under 2MB.',
            'attachments.*.mimes'       => 'Attachments must be a PDF or Image file (pdf, jpg, jpeg, png, webp).',
        ]);

        $issue->issue_category_id = $validated['issue_category_id'];
        $issue->issue_date = $validated['issue_date'];
        $issue->details = $validated['details'];

        // Only Admin users can update the issue status
        if ($user->isAdmin() && isset($validated['status'])) {
            $issue->status = $validated['status'];
        }

        $issue->save();

        if ($request->hasFile('attachments')) {
            $existingCount = $issue->attachments()->count();
            $newFiles = $request->file('attachments');

            if ($existingCount + count($newFiles) > 3) {
                return back()->withErrors(['attachments' => 'Total attachments for an issue cannot exceed 3 files. Please delete existing attachments if needed.']);
            }

            foreach ($newFiles as $file) {
                if ($file && $file->isValid()) {
                    $hashName = $file->hashName();
                    $path = 'attachments/' . $hashName;
                    
                    \Illuminate\Support\Facades\Storage::disk('public')->put(
                        $path,
                        file_get_contents($file->getPathname())
                    );
                    
                    $issue->attachments()->create([
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'file_type'     => $file->getClientMimeType(),
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }
        }

        return redirect()->route('issues.show', $issue->id)->with('success', 'Issue updated successfully!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $issue = $this->getAccessibleIssue($id);

        foreach ($issue->attachments as $attachment) {
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        }

        $issue->delete();

        return redirect()->route('issues.index')->with('success', 'Issue deleted successfully.');
    }

    public function downloadAttachment(int $issueId, int $attachmentId): StreamedResponse
    {
        $issue = $this->getAccessibleIssue($issueId);
        $attachment = $issue->attachments()->findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name);
    }
}
