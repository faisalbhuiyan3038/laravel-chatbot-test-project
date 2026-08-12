<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile — AV-CRM Intelligence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --accent-teal: #0d9488;
            --accent-teal-dark: #0f766e;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .brand-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            transition: all 0.15s;
        }
        .nav-link:hover, .nav-link.active {
            background: #f1f5f9;
            color: var(--accent-teal);
        }
        .logout-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .logout-btn:hover {
            background: #fecaca;
        }
        .container {
            max-width: 720px;
            margin: 40px auto;
            padding: 0 24px;
            width: 100%;
            flex: 1;
        }
        .profile-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .card-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 20px;
            background: #fafafa;
        }
        .avatar-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.2);
        }
        .user-title {
            font-size: 20px;
            font-weight: 700;
        }
        .user-subtitle {
            font-size: 13.5px;
            color: var(--text-secondary);
        }
        .card-body {
            padding: 32px;
        }
        .alert-status {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 24px;
        }
        .alert-error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.15s;
        }
        .form-input:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }
        .section-divider {
            margin: 28px 0;
            border-top: 1px solid var(--border);
            position: relative;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .btn-save {
            background: var(--accent-teal);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-save:hover {
            background: var(--accent-teal-dark);
        }
    </style>
</head>
<body>

    <header>
        <div class="brand">
            <div class="brand-icon">AI</div>
            <div class="brand-title">AV-CRM Intelligence</div>
        </div>
        <div class="nav-links">
            <a href="{{ route('chat.index') }}" class="nav-link">💬 Public Chat</a>
            <a href="{{ route('admin.feedbacks') }}" class="nav-link">📊 Feedback Dashboard</a>
            <a href="{{ route('profile.show') }}" class="nav-link active">👤 Profile</a>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="card-header">
                <div class="avatar-circle">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="user-title">{{ $user->name }}</h1>
                    <p class="user-subtitle">{{ $user->email }} • Account created {{ $user->created_at ? $user->created_at->format('M d, Y') : 'recently' }}</p>
                </div>
            </div>

            <div class="card-body">
                @if(session('status'))
                    <div class="alert-status">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert-error">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf

                    <h2 class="section-title">Personal Information</h2>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="section-divider"></div>

                    <h2 class="section-title">Change Password (Optional)</h2>
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">Leave blank if you do not wish to change your password.</p>

                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter new password">
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" placeholder="Confirm new password">
                    </div>

                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
