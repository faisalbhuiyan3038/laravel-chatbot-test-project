<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Issues — AV-CRM Intelligence</title>
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
            transition: background 0.15s;
        }
        .user-pill:hover { background: #e2e8f0; }

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
            transition: background 0.15s;
        }
        .logout-btn:hover { background: #fecaca; }

        .container {
            max-width: 1240px;
            margin: 32px auto;
            padding: 0 24px;
            width: 100%;
            flex: 1;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .btn-create {
            background: var(--accent-teal);
            color: #fff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s ease;
        }
        .btn-create:hover { background: var(--accent-teal-dark); }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            margin-bottom: 20px;
        }

        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        th {
            background: #f8fafc;
            padding: 14px 20px;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* 0: Open */
        .status-0 { background: #dcfce7; color: #15803d; }
        /* 1: Locked */
        .status-1 { background: #f1f5f9; color: #475569; }
        /* 2: In Progress */
        .status-2 { background: #dbeafe; color: #1e40af; }
        /* 3: Resolved */
        .status-3 { background: #ccfbf1; color: #0f766e; }
        /* 4: Closed */
        .status-4 { background: #f3f4f6; color: #6b7280; }

        .category-badge {
            background: #e2e8f0;
            color: #334155;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .details-snippet {
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-secondary);
        }

        .action-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action {
            background: none;
            border: 1px solid var(--border);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-action:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .btn-delete {
            color: #ef4444;
            border-color: #fecdd3;
        }
        .btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .pagination-bar {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        <div class="page-header">
            <div>
                <h1 class="page-title">My Created Issues</h1>
                <p class="page-subtitle">View and manage issues submitted by your account.</p>
            </div>
            <a href="{{ route('issues.create') }}" class="btn-create">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                Create New Issue
            </a>
        </div>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 220px;">Category</th>
                        <th>Details</th>
                        <th style="width: 160px;">Issue Date/Time</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 100px;">Attachments</th>
                        <th style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td style="font-weight: 600; color: var(--text-muted);">#{{ $issue->id }}</td>
                            <td>
                                <span class="category-badge">{{ $issue->category->name }}</span>
                            </td>
                            <td>
                                <div class="details-snippet" title="{{ $issue->details }}">
                                    {{ $issue->details }}
                                </div>
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-secondary);">
                                {{ $issue->issue_date->format('M d, Y H:i') }}
                            </td>
                            <td>
                                <span class="status-badge status-{{ $issue->status }}">
                                    ● {{ $issue->status_label }}
                                </span>
                            </td>
                            <td>
                                @if($issue->attachments->count() > 0)
                                    <span style="font-weight: 600; font-size: 12.5px;">📎 {{ $issue->attachments->count() }} file(s)</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 12.5px;">None</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="{{ route('issues.show', $issue->id) }}" class="btn-action" title="View details">View</a>
                                    <form action="{{ route('issues.destroy', $issue->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this issue?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action btn-delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted);">
                                You haven't created any issues yet. Click <strong>"Create New Issue"</strong> above to record a new issue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($issues->hasPages())
                <div class="pagination-bar">
                    {{ $issues->links() }}
                </div>
            @endif
        </div>
    </div>

</body>
</html>
