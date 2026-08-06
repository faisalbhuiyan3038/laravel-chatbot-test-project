<?php

namespace App\Http\Controllers;

use App\Models\ChatFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Store feedback submitted from the chat UI.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question'      => ['required', 'string', 'max:2000'],
            'answer'        => ['required', 'string'],
            'rating'        => ['required', 'string', 'in:like,dislike'],
            'feedback_text' => ['nullable', 'string', 'max:1000'],
            'sources'       => ['nullable', 'array'],
            'grounded'      => ['nullable', 'boolean'],
        ]);

        $feedback = ChatFeedback::create([
            'question'      => $validated['question'],
            'answer'        => $validated['answer'],
            'rating'        => $validated['rating'],
            'feedback_text' => $validated['feedback_text'] ?? null,
            'sources'       => $validated['sources'] ?? [],
            'grounded'      => $validated['grounded'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback recorded successfully.',
            'data'    => $feedback,
        ], 201);
    }

    /**
     * Admin Feedback Dashboard View.
     */
    public function dashboard(Request $request): View
    {
        $ratingFilter = $request->query('rating');

        $query = ChatFeedback::query()->latest();

        if (in_array($ratingFilter, ['like', 'dislike'], true)) {
            $query->where('rating', $ratingFilter);
        }

        $feedbacks = $query->paginate(20)->withQueryString();

        $totalCount = ChatFeedback::count();
        $likeCount = ChatFeedback::where('rating', 'like')->count();
        $dislikeCount = ChatFeedback::where('rating', 'dislike')->count();
        $likePercentage = $totalCount > 0 ? round(($likeCount / $totalCount) * 100, 1) : 0;

        return view('admin.feedbacks', compact(
            'feedbacks',
            'totalCount',
            'likeCount',
            'dislikeCount',
            'likePercentage',
            'ratingFilter'
        ));
    }

    /**
     * Delete a feedback entry from admin dashboard.
     */
    public function destroy(int $id): JsonResponse
    {
        $feedback = ChatFeedback::findOrFail($id);
        $feedback->delete();

        return response()->json(['success' => true]);
    }
}
