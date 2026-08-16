# Issue & Ticket Management System (with Conversational AI Assistant)

## Purpose
Provides a complete support ticketing and issue lifecycle management system accessible through both traditional web interfaces (CRUD views) and a conversational AI agent inside the chat interface. Users can report bugs, log support tickets, upload attachments (PDFs, images), track status, and request updates or deletions. Admins have system-wide visibility to transition issue statuses across the resolution lifecycle (`Open`, `In Progress`, `Resolved`, `Closed`, `Rejected`).

## Entry Points
- **Web UI Routes (Authenticated)**:
  - `GET /issues` -> `IssueController@index` (Paginated list of user's or all issues)
  - `GET /issues/create` -> `IssueController@create` (Web form to open an issue)
  - `POST /issues` -> `IssueController@store` (Validates and persists issue with attachments)
  - `GET /issues/{id}` -> `IssueController@show` (Detailed view of issue and attachments)
  - `GET /issues/{id}/edit` -> `IssueController@edit` (Edit view)
  - `PUT|PATCH /issues/{id}` -> `IssueController@update` (Updates details or admin status)
  - `DELETE /issues/{id}` -> `IssueController@destroy` (Deletes issue and associated storage files)
  - `GET /issues/{issueId}/attachment/{attachmentId}` -> `IssueController@downloadAttachment`
- **Conversational AI Entry Points**:
  - `POST /chat/ask` (dispatches to `IssueIntentDetector` & `IssueAiService::handleStream`)

## Key Files & Classes
- `app/Http/Controllers/IssueController.php` — Handles HTTP CRUD, file upload processing, role-based scope isolation, and secure attachment downloads.
- `app/Models/Issue.php` — Eloquent model with status constants (`STATUS_OPEN = '0'`, `STATUS_IN_PROGRESS = '2'`, etc.) and relationships to User, Category, and Attachments.
- `app/Models/IssueCategory.php` — Category lookup model (`id`, `name`).
- `app/Models/IssueAttachment.php` — File metadata model (`file_path`, `original_name`, `file_type`, `file_size`).
- `app/Services/Issue/IssueIntentDetector.php` — LLM classifier determining if user message is `issue_create`, `issue_query`, `issue_update`, `issue_delete`, or `faq`.
- `app/Services/Issue/IssueAiService.php` — State machine managing multi-turn conversational issue creation, validation, file attachment staging, confirmation summaries, and status queries.
- `database/migrations/2026_08_12_000001_create_issue_categories_table.php` — Schema for issue categories.
- `database/migrations/2026_08_12_000002_create_issues_table.php` — Schema for issues table.
- `database/migrations/2026_08_12_000003_create_issue_attachments_table.php` — Schema for issue attachments.
- `resources/views/issues/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php` — Blade views.

## Data Flow

### 1. Conversational AI Issue Creation Flow
```
User in Chat: "I want to report a login failure bug"
      │
      ▼
IssueIntentDetector::detect() ──► Returns 'issue_create'
      │
      ▼
IssueAiService::handleStream()
      │
      ├─► Phase 1: Collecting
      │     - Extracts fields via LLM (Category ID, Date, Details description)
      │     - Merges into session('ai_issue_session')
      │     - Validates via Validator facade (dates <= now, details <= 5000 chars)
      │
      ├─► Phase 2: Collecting Attachments
      │     - Prompts user with [Action:Upload Attachment] [Action:Skip]
      │     - Temporarily stores files in local disk (`temp_attachments/`)
      │
      ├─► Phase 3: Confirming
      │     - Formats human-readable summary
      │     - Waits for positive confirmation regex (yes, confirm, okay, proceed)
      │
      └─► Phase 4: DB Write
            - Creates `Issue` record directly linked to `Auth::user()->id`
            - Moves files from `temp_attachments/` to `public/attachments/`
            - Returns issue confirmation link: `/issues/{id}`
```

### 2. Traditional Web CRUD Flow
1. **Creation**: `POST /issues` validates input, verifies date is `before_or_equal:now`, stores up to 3 files in `public/attachments`, creates `IssueAttachment` rows, and saves the issue under `Auth::user()->issues()->create()`.
2. **Access Control**: `IssueController::getAccessibleIssue($id)` ensures regular users (`role: 'user'`) can only query their own issues, while `role: 'admin'` users can query and update any system issue.
3. **Status Updates**: Only users with `role: 'admin'` can alter the `status` field during `IssueController::update()`.

## Relevant Code Snippets

```php
// app/Http/Controllers/IssueController.php
// Scoped access control for issues
private function getAccessibleIssue(int $id): Issue
{
    $user = Auth::user();
    if ($user->isAdmin()) {
        return Issue::with(['user', 'category', 'attachments'])->findOrFail($id);
    }
    return $user->issues()->with(['category', 'attachments'])->findOrFail($id);
}
```

```php
// app/Services/Issue/IssueAiService.php
// Strict Server-Side Validation & User-ID Binding
$issue = $user->issues()->create([
    'issue_category_id' => (int) $validated['issue_category_id'],
    'issue_date'        => $validated['issue_date'],
    'details'           => $validated['details'],
    'status'            => Issue::STATUS_OPEN, // Always forced to '0'
]);
```

## Database Involvement

### Tables Touched
- `issue_categories`: Available ticket categories.
- `issues`: Core issue records.
- `issue_attachments`: File metadata linking to stored files on disk.

| Table | Column | Type | Constraints | Description |
|---|---|---|---|---|
| `issues` | `id` | bigint | PK | Issue identifier |
| `issues` | `user_id` | bigint | FK -> users.id (cascade) | Owner user ID |
| `issues` | `issue_category_id` | bigint | FK -> issue_categories.id | Assigned category |
| `issues` | `issue_date` | datetime | Not Null | When the issue occurred (<= now) |
| `issues` | `details` | text (5000 max) | Not Null | Detailed description |
| `issues` | `status` | enum/char | Default `'0'` | 0=Open, 1=Locked, 2=In Progress, 3=Resolved, 4=Closed |
| `issue_attachments` | `issue_id` | bigint | FK -> issues.id (cascade) | Parent issue |
| `issue_attachments` | `file_path` | varchar(255) | Not Null | Relative path on disk (`public` disk) |
| `issue_attachments` | `original_name` | varchar(255) | Not Null | Uploaded filename |
| `issue_attachments` | `file_type` | varchar(100) | Not Null | MIME type |
| `issue_attachments` | `file_size` | bigint | Not Null | Size in bytes |

## API & Route Details

| Method | URI | Name | Middleware | Description |
|---|---|---|---|---|
| `GET` | `/issues` | `issues.index` | `web`, `auth` | Paginated issue list (own issues for users, all for admins) |
| `GET` | `/issues/create` | `issues.create` | `web`, `auth` | Form to create a new issue |
| `POST` | `/issues` | `issues.store` | `web`, `auth` | Submits new issue with attachments |
| `GET` | `/issues/{id}` | `issues.show` | `web`, `auth` | View issue details and attachment links |
| `GET` | `/issues/{id}/edit` | `issues.edit` | `web`, `auth` | Edit issue form |
| `PUT/PATCH` | `/issues/{id}` | `issues.update` | `web`, `auth` | Update issue details (and status if admin) |
| `DELETE` | `/issues/{id}` | `issues.destroy` | `web`, `auth` | Deletes issue and physical attachment files |
| `GET` | `/issues/{id}/attachment/{attId}` | `issues.attachment.download` | `web`, `auth` | Streams file download from storage |

## Edge Cases & Conditional Logic
- **Windows / Laragon File Upload Bug Workaround**: On certain local Windows/Laragon setups, PHP temporary directory permissions cause `$file->getRealPath()` to return false, breaking Laravel's native `$file->store()`. Both `IssueController` and `FaqChatController` bypass this by using `file_get_contents($file->getPathname())` with direct `Storage::put()`.
- **Active Issue Session Bypass**: In `FaqChatController::ask()`, if an active session (`session('ai_issue_session')`) exists with an uncompleted phase, incoming messages (such as "yes", "skip", "uploading...") bypass `IssueIntentDetector` to prevent misclassification as a general FAQ query.
- **Future Date Prevention**: The validation rule `before_or_equal:now` prevents logging issues with future dates.

## Notes & Concerns
- **Temporary Attachment Garbage Collection**: If a user uploads files during a conversational AI issue creation flow but abandons the conversation before confirmation, the uploaded files in `storage/app/temp_attachments/` remain on disk indefinitely unless cleaned by a scheduled cron command.
- **Attachment Total Count Cap during Updates**: When updating an issue, `IssueController::update` verifies that `existingCount + count(newFiles) <= 3`. However, the web UI does not provide an explicit button to delete individual attachments without deleting the entire issue.
- **Status Constants Discrepancy in Prompts**: The prompt in `IssueAiService` mentions status labels (e.g. `4=Rejected`), whereas `Issue::STATUSES` defines `4 => 'Closed'`.
