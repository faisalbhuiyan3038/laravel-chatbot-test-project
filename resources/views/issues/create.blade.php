<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Issue — AV-CRM Intelligence</title>
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
            gap: 12px;
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

        .user-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            background: #f1f5f9;
            border-radius: 20px;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 13px;
            font-weight: 600;
        }

        .user-pill-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--accent-teal);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .logout-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .container {
            max-width: 720px;
            margin: 36px auto;
            padding: 0 24px;
            width: 100%;
            flex: 1;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: 32px;
        }

        .card-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
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
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .required-star {
            color: #ef4444;
        }

        .form-select, .form-input, .form-textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-primary);
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-select:focus, .form-input:focus, .form-textarea:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }

        .attachment-box {
            background: #f8fafc;
            border: 1px dashed var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .file-input-item {
            margin-bottom: 10px;
        }
        .file-input-item:last-child { margin-bottom: 0; }

        .file-help {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 28px;
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }

        .btn-cancel {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: background 0.15s;
        }
        .btn-cancel:hover { background: #f1f5f9; }

        .btn-submit {
            background: var(--accent-teal);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-submit:hover { background: var(--accent-teal-dark); }
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
            <a href="{{ route('issues.index') }}" class="nav-link active">📋 My Issues</a>
            <a href="{{ route('admin.feedbacks') }}" class="nav-link">📊 Feedback Dashboard</a>
            @auth
                <a href="{{ route('profile.show') }}" class="user-pill" title="View Profile">
                    <span class="user-pill-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    <span>{{ auth()->user()->name }}</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">Log Out</button>
                </form>
            @endauth
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h1 class="card-title">Create New Issue</h1>
            <p class="card-subtitle">Fill in the mandatory details below to log a new support issue.</p>

            @if($errors->any())
                <div class="alert-error">
                    <strong style="display: block; margin-bottom: 6px;">Please fix the following errors:</strong>
                    <ul style="margin-left: 18px; font-size: 13px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('issues.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Issue Category -->
                <div class="form-group">
                    <label for="issue_category_id" class="form-label">
                        Issue Category <span class="required-star">*</span>
                    </label>
                    <select name="issue_category_id" id="issue_category_id" class="form-select" required>
                        <option value="" disabled {{ old('issue_category_id') ? '' : 'selected' }}>-- Select Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('issue_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Issue Date and Time -->
                <div class="form-group">
                    <label for="issue_date" class="form-label">
                        Issue Date & Time <span class="required-star">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="issue_date" 
                           id="issue_date" 
                           class="form-input" 
                           max="{{ now()->format('Y-m-d\TH:i') }}" 
                           value="{{ old('issue_date', now()->format('Y-m-d\TH:i')) }}" 
                           required>
                    <p class="file-help">Future dates and times cannot be selected.</p>
                </div>

                <!-- Issue Details -->
                <div class="form-group">
                    <label for="details" class="form-label">
                        Issue Details <span class="required-star">*</span>
                    </label>
                    <textarea name="details" id="details" class="form-textarea" placeholder="Describe the issue in detail..." required>{{ old('details') }}</textarea>
                </div>

                <!-- Attachments (Optional, up to 3 files under 2MB each) -->
                <div class="form-group">
                    <label class="form-label">
                        Attachments (Optional — max 3 files)
                    </label>
                    <div class="attachment-box">
                        <div class="file-input-item">
                            <input type="file" name="attachments[]" accept="image/*,application/pdf" class="form-input">
                        </div>
                        <div class="file-input-item">
                            <input type="file" name="attachments[]" accept="image/*,application/pdf" class="form-input">
                        </div>
                        <div class="file-input-item">
                            <input type="file" name="attachments[]" accept="image/*,application/pdf" class="form-input">
                        </div>
                        <p class="file-help">Accepted formats: PDF or Images (JPG, PNG, WEBP). Max size: 2MB per file.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('issues.index') }}" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">Submit Issue</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
