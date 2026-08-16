# Chat Feedback Collection & Quality Insights System

## Purpose
Collects user sentiment and qualitative feedback (thumbs up/down ratings, optional critique comments, retrieved source IDs, and grounding status) on AI chat responses. Provides administrative analytics via both a web-based dashboard and a CLI insights command to monitor user satisfaction and identify gaps in the project knowledge base.

## Entry Points
- **HTTP Routes (Public / API)**:
  - `POST /chat/feedback` -> `FeedbackController@store` (Submits thumbs up/down rating + comment)
- **HTTP Routes (Admin / Authenticated)**:
  - `GET /admin/feedbacks` -> `FeedbackController@dashboard` (Renders feedback analytics dashboard)
  - `DELETE /admin/feedbacks/{id}` -> `FeedbackController@destroy` (Deletes feedback row)
- **Console Commands**:
  - `php artisan faq:feedback-insights {--limit=10}` -> `FeedbackInsightsCommand` (Outputs summary table and disliked queries requiring documentation fixes)

## Key Files & Classes
- `app/Http/Controllers/FeedbackController.php` — Handles public feedback submissions, paginated dashboard queries with rating filters, and deletion.
- `app/Models/ChatFeedback.php` — Eloquent model mapping to `chat_feedbacks` table with JSON array casting for retrieved `sources` and boolean casting for `grounded`.
- `app/Console/Commands/FeedbackInsightsCommand.php` — CLI analytics tool summarizing satisfaction percentage and listing latest negative feedback comments.
- `database/migrations/2026_08_06_170000_create_chat_feedbacks_table.php` — Migration creating `chat_feedbacks` table.
- `resources/views/admin/feedbacks.blade.php` — Responsive admin dashboard displaying aggregate metrics (total, likes, dislikes, satisfaction rate %) and paginated feedback logs with filter tabs.
- `tests/Feature/FeedbackTest.php` — Feature tests covering like/dislike submissions, guest redirect guards, and dashboard view rendering.

## Data Flow

### 1. Public Feedback Submission Flow
1. **User Action**: In the chat UI (`chat.blade.php`), after an assistant response completes, the user clicks 👍 (like) or 👎 (dislike).
2. **Payload Dispatch**: An AJAX `fetch('POST /chat/feedback')` request sends the question, answer, rating, grounding state, source chunk IDs, and optional user comment.
3. **Validation & Persistence**: `FeedbackController::store` validates fields and creates a `ChatFeedback` record.
4. **Response**: Returns JSON `{"success": true, "message": "Feedback recorded successfully."}` with HTTP status `201 Created`.

### 2. Admin Quality Analysis Flow
1. **Web Dashboard**: An authenticated staff/admin accesses `/admin/feedbacks`. The controller calculates aggregate metrics (`totalCount`, `likeCount`, `dislikeCount`, `likePercentage`) and paginates feedback rows.
2. **CLI Insights**: DevOps or content managers run `php artisan faq:feedback-insights`. It prints an ASCII metrics table and lists the top N disliked queries with user comments and referenced FAQ IDs to pinpoint which documentation sections require revision.

## Relevant Code Snippets

```php
// app/Http/Controllers/FeedbackController.php
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
```

```php
// app/Console/Commands/FeedbackInsightsCommand.php
$this->table(
    ['Metric', 'Value'],
    [
        ['Total Rated Responses', $total],
        ['Liked Responses (👍)', $likes],
        ['Disliked Responses (👎)', $dislikes],
        ['User Satisfaction Rate', "{$percentage}%"],
    ]
);
```

## Database Involvement

### Tables Touched
- `chat_feedbacks`: Stores user feedback logs and context.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | bigint | PK, Auto Increment | Feedback record ID |
| `question` | text | Not Null | User's original query text |
| `answer` | text | Not Null | AI's generated response |
| `rating` | enum/varchar | Not Null | `'like'` or `'dislike'` |
| `feedback_text` | text | Nullable | User comment / explanation of why response was unhelpful |
| `sources` | json / text | Nullable | Array of retrieved chunk IDs and cosine scores |
| `grounded` | boolean | Default `true` | Indicates whether RAG found relevant context |
| `created_at` / `updated_at` | timestamp | Nullable | Submission timestamps |

## API & Route Details

| Method | URI | Name | Middleware | Description |
|---|---|---|---|---|
| `POST` | `/chat/feedback` | `chat.feedback` | `web` | Public endpoint for submitting chat response feedback |
| `GET` | `/admin/feedbacks` | `admin.feedbacks` | `web`, `auth` | Dashboard view with metrics and logs |
| `DELETE` | `/admin/feedbacks/{id}` | `admin.feedbacks.destroy` | `web`, `auth` | Deletes a specific feedback entry |

### JSON Request Payload (`POST /chat/feedback`)
```json
{
  "question": "How do I change my biller info?",
  "answer": "Contact support.",
  "rating": "dislike",
  "feedback_text": "Answer was too brief and did not provide the contact details.",
  "grounded": true,
  "sources": [
    {"id": 4, "score": 0.6124}
  ]
}
```

## Edge Cases & Conditional Logic
- **Anonymous Feedback**: Submitting feedback does not require a logged-in user session, ensuring public chat users on landing pages can submit feedback without authentication friction.
- **Rating Filtering**: The admin dashboard supports `?rating=like` or `?rating=dislike` query parameters to isolate negative reviews for fast triage.
- **Zero Feedback Handling**: Both the dashboard view and `faq:feedback-insights` command handle empty databases gracefully without division-by-zero errors (`$totalCount > 0 ? ... : 0`).

## Notes & Concerns
- **Route Authorization Check**: While `/admin/feedbacks` is protected by `auth` middleware, it does not explicitly verify `$user->isAdmin()`. Any registered user (`role: 'user'`) who logs in can view the administrative feedback dashboard unless gated in controller logic or dedicated admin middleware.
- **Rate Limiting on Feedback**: `POST /chat/feedback` is not throttled via `throttle:X,1` in `routes/web.php`, opening potential exposure to automated feedback spam if unmonitored.
- **Lack of Project Association in Schema**: `chat_feedbacks` records do not store `project_id`. When multiple projects are active, feedback cannot be filtered by project without regex-matching query text.
