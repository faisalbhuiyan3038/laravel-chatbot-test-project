<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AV-CRM AI - Knowledge Base Feedback Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            --like-bg: #dcfce7;
            --like-text: #15803d;
            --dislike-bg: #ffe4e6;
            --dislike-text: #be123c;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation Header */
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

        /* Main Container */
        .container {
            max-width: 1240px;
            margin: 32px auto;
            padding: 0 24px;
            width: 100%;
            flex: 1;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Filters & Table Container */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .table-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
        }

        .tab-btn {
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            color: var(--text-secondary);
            background: #f1f5f9;
            transition: all 0.15s;
        }

        .tab-btn.active {
            background: var(--accent-teal);
            color: #ffffff;
        }

        /* Data Table */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        th {
            background: #f8fafc;
            padding: 12px 20px;
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
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .badge-like {
            background: var(--like-bg);
            color: var(--like-text);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-dislike {
            background: var(--dislike-bg);
            color: var(--dislike-text);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .question-text {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .answer-preview {
            color: var(--text-secondary);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .comment-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #9f1239;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-top: 6px;
            font-style: italic;
        }

        .sources-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .source-tag {
            background: #e2e8f0;
            color: #334155;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }

        .delete-btn {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.15s;
        }

        .delete-btn:hover {
            background: #fee2e2;
        }

        .pagination-bar {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        .user-pill:hover {
            background: #e2e8f0;
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
            transition: background 0.15s;
        }
        .logout-btn:hover {
            background: #fecaca;
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
            <a href="{{ route('chat.index') }}" class="nav-link">💬 Chat Interface</a>
            <a href="{{ route('admin.feedbacks') }}" class="nav-link active">📊 Knowledge Base Feedbacks</a>
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
            <h1 class="page-title">Evolving Knowledge Base Insights</h1>
            <p class="page-subtitle">Inspect user ratings and negative feedback to continuously train and optimize RAG context retrieval.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Rated Queries</div>
                <div class="stat-value">{{ number_format($totalCount) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Satisfaction Score</div>
                <div class="stat-value" style="color: var(--like-text);">{{ $likePercentage }}%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Liked Responses 👍</div>
                <div class="stat-value" style="color: var(--like-text);">{{ number_format($likeCount) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Disliked Responses 👎</div>
                <div class="stat-value" style="color: var(--dislike-text);">{{ number_format($dislikeCount) }}</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3 style="font-size: 15px; font-weight: 600;">Feedback Log</h3>
                <div class="filter-tabs">
                    <a href="{{ route('admin.feedbacks') }}" class="tab-btn {{ !$ratingFilter ? 'active' : '' }}">All</a>
                    <a href="{{ route('admin.feedbacks', ['rating' => 'like']) }}" class="tab-btn {{ $ratingFilter === 'like' ? 'active' : '' }}">Liked 👍</a>
                    <a href="{{ route('admin.feedbacks', ['rating' => 'dislike']) }}" class="tab-btn {{ $ratingFilter === 'dislike' ? 'active' : '' }}">Disliked 👎</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Rating</th>
                        <th style="width: 35%;">User Question & AI Response</th>
                        <th style="width: 25%;">Feedback / User Comment</th>
                        <th style="width: 20%;">RAG Sources</th>
                        <th style="width: 120px;">Timestamp</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedbacks as $fb)
                        <tr id="fb-row-{{ $fb->id }}">
                            <td>
                                @if($fb->rating === 'like')
                                    <span class="badge-like">👍 Like</span>
                                @else
                                    <span class="badge-dislike">👎 Dislike</span>
                                @endif
                            </td>
                            <td>
                                <div class="question-text">Q: {{ $fb->question }}</div>
                                <div class="answer-preview">A: {{ $fb->answer }}</div>
                            </td>
                            <td>
                                @if($fb->feedback_text)
                                    <div class="comment-box">💬 "{{ $fb->feedback_text }}"</div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 12px;">No comment left</span>
                                @endif
                            </td>
                            <td>
                                <div class="sources-list">
                                    @if(!empty($fb->sources))
                                        @foreach($fb->sources as $src)
                                            <span class="source-tag">#{{ $src['id'] ?? 'FAQ' }} (score: {{ $src['score'] ?? 'N/A' }})</span>
                                        @endforeach
                                    @else
                                        <span style="color: var(--text-muted); font-size: 12px;">No sources (Ungrounded)</span>
                                    @endif
                                </div>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 12px;">
                                {{ $fb->created_at->format('M d, H:i') }}
                            </td>
                            <td>
                                <button class="delete-btn" onclick="deleteFeedback({{ $fb->id }})" title="Delete entry">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                No feedback recorded yet. Ratings submitted by users in the chat will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($feedbacks->hasPages())
                <div class="pagination-bar">
                    {{ $feedbacks->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        async function deleteFeedback(id) {
            if (!confirm('Are you sure you want to delete this feedback log?')) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const res = await fetch(`/admin/feedbacks/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    const row = document.getElementById(`fb-row-${id}`);
                    if (row) row.remove();
                }
            } catch (err) {
                alert('Failed to delete feedback entry.');
            }
        }
    </script>
</body>
</html>
