# User Authentication, RBAC & Profile Management

## Purpose
Provides session-based user authentication, new user registration, self-service profile updating, and role-based access differentiation (`user` vs `admin`). It ensures administrative endpoints (such as the Chat Feedback Dashboard) and authenticated workspaces (such as Issue Management and Profile Settings) are restricted to valid users, while allowing public access to the AI Chat interface.

## Entry Points
- **HTTP Routes (Guest)**:
  - `GET /login` -> `AuthController@showLoginForm`
  - `POST /login` -> `AuthController@login`
  - `GET /register` -> `AuthController@showRegisterForm`
  - `POST /register` -> `AuthController@register`
- **HTTP Routes (Auth)**:
  - `POST /logout` -> `AuthController@logout`
  - `GET /profile` -> `ProfileController@show`
  - `POST /profile` -> `ProfileController@update`

## Key Files & Classes
- `app/Http/Controllers/AuthController.php` — Handles session login, user creation/registration, and session termination.
- `app/Http/Controllers/ProfileController.php` — Handles rendering and updating the authenticated user's credentials.
- `app/Models/User.php` — Eloquent model representing application users, defining role helper methods (`isAdmin()`, `isGeneral()`) and issue relationships.
- `database/migrations/0001_01_01_000000_create_users_table.php` — Baseline schema for users, password resets, and session tables.
- `database/migrations/2026_08_12_000004_add_role_to_users_table.php` — Adds `role` enum/string column defaulting to `'user'`.
- `resources/views/auth/login.blade.php` — Login view template.
- `resources/views/auth/register.blade.php` — Registration view template.
- `resources/views/auth/profile.blade.php` — Profile editing interface.
- `tests/Feature/AuthTest.php` — Automated feature tests covering login, validation failures, registration role defaults, logout, and profile changes.

## Data Flow

### 1. User Registration Flow
1. **Request**: User posts `name`, `email`, `password`, `password_confirmation` to `POST /register`.
2. **Validation**: `AuthController::register()` validates presence, email uniqueness against `users` table, and password minimum length (8 characters) with confirmation.
3. **Model Persistence**: User is instantiated with `role` defaulting to `'user'` in DB schema; email is lowercased, password hashed with Bcrypt via `Hash::make()`.
4. **Auto-Login**: `Auth::login($user)` establishes the authenticated session.
5. **Response**: Redirects to `route('chat.index')` with flash success message.

### 2. User Login Flow
1. **Request**: User posts credentials (`email`, `password`, optional `remember`) to `POST /login`.
2. **Authentication Check**: `Auth::attempt($credentials, $request->boolean('remember'))`.
3. **Session Rejuvenation**: On success, `session()->regenerate()` prevents session fixation attacks and redirects to `redirect()->intended(route('admin.feedbacks'))`.
4. **Failure Branch**: On invalid credentials, redirects back with validation error on `email` and retains email input via `->onlyInput('email')`.

### 3. Profile Update Flow
1. **Request**: Authenticated user posts updated `name`, `email`, and optional `password` / `password_confirmation` to `POST /profile`.
2. **Validation**: Email uniqueness check ignores the currently authenticated user's ID (`Rule::unique('users')->ignore($user->id)`).
3. **Persistence**: Updates `name` and normalized `email`. If `password` is non-empty, re-hashes and updates `password`.
4. **Response**: Redirects back with flash status message `Profile updated successfully!`.

## Relevant Code Snippets

```php
// app/Http/Controllers/AuthController.php
public function login(Request $request): RedirectResponse
{
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended(route('admin.feedbacks'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
}
```

```php
// app/Models/User.php
public function isAdmin(): bool
{
    return $this->role === 'admin';
}

public function isGeneral(): bool
{
    return $this->role === 'user';
}
```

## Database Involvement

### Tables Touched
- `users`: Stores user identity, authentication hashes, and roles.

| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | bigint | PK, Auto Increment | Primary user ID |
| `name` | varchar(255) | Not Null | User display name |
| `email` | varchar(255) | Unique, Not Null | Normalized login email |
| `password` | varchar(255) | Not Null | Bcrypt hashed password |
| `role` | varchar(20) | Default `'user'`, Not Null | Access tier (`user` or `admin`) |
| `remember_token` | varchar(100) | Nullable | Token for persistent "remember me" logins |
| `created_at` / `updated_at` | timestamp | Nullable | Timestamps |

### Relationships
- `User` hasMany `Issue` (`$user->issues()`).

## API & Route Details

| Method | URI | Name | Middleware | Request Payload | Response |
|---|---|---|---|---|---|
| `GET` | `/login` | `login` | `web`, `guest` | None | HTML View (`auth.login`) |
| `POST` | `/login` | None | `web`, `guest` | `email`, `password`, `remember?` | Redirect to `admin.feedbacks` or intended URL |
| `GET` | `/register` | `register` | `web`, `guest` | None | HTML View (`auth.register`) |
| `POST` | `/register` | None | `web`, `guest` | `name`, `email`, `password`, `password_confirmation` | Redirect to `chat.index` |
| `POST` | `/logout` | `logout` | `web`, `auth` | `_token` | Redirect to `login` |
| `GET` | `/profile` | `profile.show` | `web`, `auth` | None | HTML View (`auth.profile`) |
| `POST` | `/profile` | `profile.update` | `web`, `auth` | `name`, `email`, `password?`, `password_confirmation?` | Redirect back with status |

## Edge Cases & Conditional Logic
- **Email Normalization**: `AuthController` and `ProfileController` explicitly lowercase email addresses before saving (`strtolower($validated['email'])`), preventing casing duplicate issues during lookups.
- **Optional Password Update**: In `ProfileController::update`, if `password` is submitted as empty or null, the existing password is preserved without re-hashing empty strings.
- **Role Elevation Guard**: Registration endpoint (`AuthController::register`) does not expose `role` in `$fillable` extraction during registration, ensuring new registrants always receive the default `'user'` role and cannot self-promote to `'admin'`.

## Notes & Concerns
- **Login Intended Redirect**: `AuthController::login` defaults `intended()` redirect to `route('admin.feedbacks')`. While appropriate for admins, regular users (`role: 'user'`) logging in without a prior intended URL will be redirected to `/admin/feedbacks` (which currently allows any authenticated user or requires custom controller authorization).
- **Missing Dedicated Admin Middleware**: Role checks (`isAdmin()`) are checked ad-hoc inside controllers/views (e.g. `IssueController`, `FeedbackController`) rather than encapsulated in a reusable route middleware like `EnsureUserIsAdmin`.
- **Missing Password Reset & Email Verification**: Routes for password recovery (`forgot-password`, `reset-password`) are not implemented in `routes/web.php`. If a user loses their credentials, manual database intervention is required.
