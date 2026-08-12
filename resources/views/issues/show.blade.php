<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue #{{ $issue->id }} — AV-CRM Intelligence</title>
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
            max-width: 800px;
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
            overflow: hidden;
        }

        .card-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }

        .issue-title {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
        }
        .status-0 { background: #dcfce7; color: #15803d; }
        .status-1 { background: #f1f5f9; color: #475569; }
        .status-2 { background: #dbeafe; color: #1e40af; }
        .status-3 { background: #ccfbf1; color: #0f766e; }
        .status-4 { background: #f3f4f6; color: #6b7280; }

        .card-body {
            padding: 32px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            background: #f8fafc;
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .meta-item-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .meta-item-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .details-box {
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-primary);
            white-space: pre-wrap;
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 28px;
        }

        .attachments-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .attachment-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .attachment-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .btn-download {
            background: var(--accent-teal);
            color: #fff;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            transition: background 0.15s;
        }
        .btn-download:hover { background: var(--accent-teal-dark); }

        .card-footer {
            padding: 20px 32px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
        }

        .btn-back {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-delete:hover { background: #fecaca; }
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
            <div class="card-header">
                <div class="issue-title">
                    <span>Issue #{{ $issue->id }}</span>
                </div>
                <span class="status-badge status-{{ $issue->status }}">
                    ● {{ $issue->status_label }}
                </span>
            </div>

            <div class="card-body">
                <div class="meta-grid">
                    <div>
                        <div class="meta-item-label">Category</div>
                        <div class="meta-item-value">{{ $issue->category->name }}</div>
                    </div>
                    <div>
                        <div class="meta-item-label">Issue Date & Time</div>
                        <div class="meta-item-value">{{ $issue->issue_date->format('M d, Y \a\t H:i') }}</div>
                    </div>
                    <div>
                        <div class="meta-item-label">Submitted On</div>
                        <div class="meta-item-value">{{ $issue->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>

                <div class="section-title">Issue Details</div>
                <div class="details-box">{{ $issue->details }}</div>

                <div class="section-title">Attachments ({{ $issue->attachments->count() }})</div>
                @if($issue->attachments->count() > 0)
                    <div class="attachments-list">
                        @foreach($issue->attachments as $attachment)
                            <div class="attachment-card">
                                <div class="attachment-info">
                                    <span>📄 {{ $attachment->original_name }}</span>
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 12px;">({{ round($attachment->file_size / 1024, 1) }} KB)</span>
                                </div>
                                <a href="{{ route('issues.attachment.download', ['issueId' => $issue->id, 'attachmentId' => $attachment->id]) }}" class="btn-download">Download</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="color: var(--text-muted); font-size: 13.5px;">No attachments uploaded for this issue.</p>
                @endif
            </div>

            <div class="card-footer">
                <a href="{{ route('issues.index') }}" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back to My Issues
                </a>

                <form action="{{ route('issues.destroy', $issue->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this issue?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-delete">Delete Issue</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
