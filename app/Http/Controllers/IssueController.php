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
    public function index(): View
    {
        $issues = Auth::user()->issues()
            ->with(['category', 'attachments'])
            ->latest()
            ->paginate(10);

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
            'status'            => Issue::STATUS_OPEN,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('attachments', 'public');
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
        $issue = Auth::user()->issues()
            ->with(['category', 'attachments'])
            ->findOrFail($id);

        return view('issues.show', compact('issue'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $issue = Auth::user()->issues()
            ->with('attachments')
            ->findOrFail($id);

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
        $issue = Auth::user()->issues()->findOrFail($issueId);
        $attachment = $issue->attachments()->findOrFail($attachmentId);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->original_name);
    }
}
