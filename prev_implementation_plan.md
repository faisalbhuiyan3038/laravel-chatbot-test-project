# Add AI-driven Issue Updating and Deletion

This plan outlines the architecture and implementation steps to allow authenticated users to update and delete their own support issues via the AI chat interface, matching the capabilities of the manual web UI.

## User Review Required

> [!IMPORTANT]
> - **Attachments:** The AI cannot upload or delete individual attachments. When deleting an issue, all associated attachments will be deleted automatically (matching the manual UI). For updates, users must use the web UI if they want to manage attachments.
> - **Admin Actions:** Updating issue status remains an admin-only feature in the backend and will not be supported via AI.

## Proposed Changes

### 1. Intent Detection
We need to extend the intent classifier to recognise two new operational actions.

#### [MODIFY] `app/Services/Issue/IssueIntentDetector.php`
- Add two new valid intents: `issue_update` and `issue_delete`.
- Update the `SYSTEM_PROMPT` with rules and examples for these new intents:
  - `issue_update`: Modifying, editing, or changing an existing ticket (e.g., "Change the category of issue #5", "Update my ticket details").
  - `issue_delete`: Canceling, removing, or deleting a ticket (e.g., "Delete issue #12", "Cancel my ticket").

### 2. Request Routing
#### [MODIFY] `app/Http/Controllers/FaqChatController.php`
- Add `issue_update` and `issue_delete` to the array of intents routed to `IssueAiService->handleStream()`.

### 3. Core AI Service Logic
#### [MODIFY] `app/Services/Issue/IssueAiService.php`
- **System Prompt Updates:** Update the `buildIssueSystemPrompt` to explicitly instruct the LLM on how to extract `issue_id` for updates and deletions, and restrict actions to the user's own issues.
- **New Session States:** Introduce state tracking for the new flows.
- **Delete Workflow (`handleDeleteStream`):**
  - **Phase 1 (Collecting ID):** Extract the target issue ID using the LLM. If missing, ask the user.
  - **Phase 2 (Validation):** Fetch the issue via `$user->issues()->find($id)`. If it doesn't exist or isn't owned by the user, return an error message.
  - **Phase 3 (Confirmation):** Show a summary of the issue to be deleted and ask for explicit confirmation (using existing `isPositiveConfirmation` logic).
  - **Phase 4 (Execution):** If confirmed, delete the issue and its attachments from storage, clear session, and return a success message.
- **Update Workflow (`handleUpdateStream`):**
  - **Phase 1 (Collecting ID):** Extract the target issue ID. If missing, ask for it.
  - **Phase 2 (Loading Context):** Once the ID is found and verified against `$user->issues()`, load its current DB values into the session state.
  - **Phase 3 (Collecting Updates):** Extract updated fields (`issue_category_id`, `issue_date`, `details`) using the LLM. If no fields have changed, prompt the user ("What would you like to update?").
  - **Phase 4 (Validation):** Run `Validator::make()` on the modified fields. Ask for corrections if invalid.
  - **Phase 5 (Confirmation):** Show a summary of the *new* state and ask for explicit confirmation.
  - **Phase 6 (Execution):** If confirmed, save the changes to the DB, clear session, and return a success message.
- **Session Management:** Ensure `session()->save()` is called after all new session mutations inside the streamed response closures.

## Verification Plan

### Automated Tests
I will create tests in `tests/Unit/IssueIntentDetectorTest.php` to verify the classifier correctly identifies:
- "Delete issue 5" -> `issue_delete`
- "Update my ticket" -> `issue_update`

### Manual Verification
1. Open the chat and say "Delete my issue #123" (where 123 is a valid issue I own). Verify it asks for confirmation, deletes it, and removes files.
2. Say "Update the category of issue #123 to Card Lost". Verify it extracts the ID, updates the fields, asks for confirmation, and successfully saves it.
3. Try to delete/update an issue owned by someone else or an invalid ID to ensure the PHP security boundaries block the action.
