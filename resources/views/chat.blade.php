<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AV-CRM Assistant — WhatsApp Web</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --wa-bg: #eae6df;
            --wa-header-bg: #f0f2f5;
            --wa-sidebar-bg: #ffffff;
            --wa-sidebar-hover: #f5f6f6;
            --wa-sidebar-active: #f0f2f5;
            --wa-teal: #00a884;
            --wa-teal-dark: #008069;
            --wa-user-bubble: #d9fdd3;
            --wa-assistant-bubble: #ffffff;
            --wa-text-primary: #111b21;
            --wa-text-secondary: #667781;
            --wa-text-muted: #8696a0;
            --wa-border: #e9edef;
            --wa-icon: #54656f;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background: #d1d7db;
            color: var(--wa-text-primary);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* App Layout Container */
        #app {
            display: flex;
            width: 100vw;
            height: 100vh;
            max-width: 1600px;
            background: #fff;
            box-shadow: 0 6px 18px rgba(11, 20, 26, 0.08);
            position: relative;
            overflow: hidden;
        }

        /* Top Accent Bar (WhatsApp signature green line at top of viewport) */
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 127px;
            background-color: var(--wa-teal-dark);
            z-index: -1;
        }

        /* Sidebar (Chats Panel) */
        #sidebar {
            width: 380px;
            background: var(--wa-sidebar-bg);
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--wa-border);
            flex-shrink: 0;
            z-index: 20;
            transition: transform 0.25s cubic-bezier(0.1, 0.82, 0.25, 1);
        }

        .sidebar-header {
            height: 60px;
            background: var(--wa-header-bg);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--wa-border);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--wa-teal);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
        }

        .profile-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--wa-text-primary);
        }

        .sidebar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .icon-btn {
            background: none;
            border: none;
            color: var(--wa-icon);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .icon-btn:hover {
            background: rgba(11, 20, 26, 0.05);
            color: var(--wa-text-primary);
        }

        /* Search Bar in Sidebar */
        .search-container {
            padding: 8px 12px;
            background: #fff;
            border-bottom: 1px solid var(--wa-border);
        }

        .search-inner {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f0f2f5;
            border-radius: 8px;
            padding: 6px 12px;
        }

        .search-inner svg {
            color: var(--wa-text-secondary);
            flex-shrink: 0;
        }

        .search-input {
            border: none;
            background: transparent;
            width: 100%;
            font-size: 13.5px;
            outline: none;
            color: var(--wa-text-primary);
        }

        /* Conversation List */
        .conv-list {
            flex: 1;
            overflow-y: auto;
        }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f5f6f6;
            transition: background 0.15s ease;
            position: relative;
        }

        .conv-item:hover {
            background: var(--wa-sidebar-hover);
        }

        .conv-item.active {
            background: var(--wa-sidebar-active);
        }

        .conv-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #128c7e, #075e54);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }

        .conv-info {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .conv-top-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conv-name {
            font-size: 15px;
            font-weight: 500;
            color: var(--wa-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-time {
            font-size: 12px;
            color: var(--wa-text-muted);
            flex-shrink: 0;
        }

        .conv-bottom-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conv-preview {
            font-size: 13px;
            color: var(--wa-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-item-actions {
            display: none;
            gap: 4px;
        }

        .conv-item:hover .conv-item-actions {
            display: flex;
        }

        .action-icon {
            color: var(--wa-text-muted);
            padding: 2px;
            border-radius: 4px;
        }

        .action-icon:hover {
            color: #ea580c;
        }

        /* Sidebar Storage Footer — subtle, blends with WA style */
        .sidebar-footer {
            padding: 7px 16px;
            background: var(--wa-header-bg);
            border-top: 1px solid var(--wa-border);
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 36px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #eab308;
            flex-shrink: 0;
        }

        .status-dot.persisted {
            background: #22c55e;
        }

        .storage-status-text {
            font-size: 11px;
            color: var(--wa-text-muted);
            flex: 1;
        }

        .persist-btn {
            background: transparent;
            color: var(--wa-teal-dark);
            border: none;
            font-size: 11px;
            font-weight: 600;
            padding: 0;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .persist-btn:hover { color: var(--wa-teal); }

        /* Main Workspace */
        #main {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: var(--wa-bg);
            position: relative;
        }

        /* Background Pattern Overlay */
        #main::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.4;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }

        /* Header */
        header {
            height: 60px;
            background: var(--wa-header-bg);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--wa-border);
            z-index: 10;
        }

        .header-chat-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--wa-icon);
            cursor: pointer;
            padding: 4px;
        }

        .active-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--wa-teal-dark);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 15px;
        }

        .active-meta {
            display: flex;
            flex-direction: column;
        }

        .active-name {
            font-size: 15.5px;
            font-weight: 600;
            color: var(--wa-text-primary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .active-status {
            font-size: 12px;
            color: var(--wa-text-secondary);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Messages Canvas */
        #messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 20px 5%;
            display: flex;
            flex-direction: column;
            z-index: 5;
        }

        .messages-inner {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Date Banner Badge */
        .date-badge {
            align-self: center;
            background: #ffffff;
            color: var(--wa-text-secondary);
            font-size: 11.5px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 7px;
            box-shadow: 0 1px 1px rgba(11,20,26,0.08);
            margin: 10px 0;
            text-transform: uppercase;
        }

        /* Message Rows - USER (RIGHT) vs ASSISTANT (LEFT) */
        .msg-row {
            display: flex;
            width: 100%;
            margin-bottom: 2px;
        }

        /* CRITICAL FIX: User messages aligned RIGHT, Assistant messages aligned LEFT */
        .msg-row.user {
            justify-content: flex-end;
        }

        .msg-row.assistant {
            justify-content: flex-start;
        }

        .msg-container {
            max-width: 75%;
            display: flex;
            flex-direction: column;
        }

        /* Bubble: natural padding, no bottom hack */
        .msg-bubble {
            padding: 7px 12px 4px 12px;
            font-size: 14.2px;
            line-height: 1.5;
            color: var(--wa-text-primary);
            box-shadow: 0 1px 2px rgba(11, 20, 26, 0.12);
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: pre-wrap;
        }

        /* User Bubble Styling (Right Side - Green) */
        .msg-row.user .msg-bubble {
            background: var(--wa-user-bubble);
            border-radius: 8px 0px 8px 8px;
        }

        /* Assistant Bubble Styling (Left Side - White) */
        .msg-row.assistant .msg-bubble {
            background: var(--wa-assistant-bubble);
            border-radius: 0px 8px 8px 8px;
        }

        /* Bubble inner: text + time row in one flow */
        .bubble-text {
            display: inline;
            white-space: pre-wrap;
        }

        /* Time & ticks sit inline after text using float trick — standard WhatsApp layout */
        .msg-meta {
            float: right;
            display: flex;
            align-items: flex-end;
            gap: 3px;
            margin-left: 6px;
            margin-top: 2px;
            /* Push it visually to the bottom-right corner of the bubble */
            line-height: 1;
            position: relative;
            bottom: -1px;
        }

        .msg-time {
            font-size: 11px;
            color: var(--wa-text-muted);
            white-space: nowrap;
        }

        .msg-row.user .msg-time { color: #6aaf85; }

        .ticks-icon {
            color: #53bdeb;
            display: inline-flex;
            align-items: center;
        }

        /* Details toggle sits OUTSIDE the bubble, below it in the container */
        .details-toggle {
            font-size: 11px;
            color: var(--wa-teal-dark);
            background: none;
            border: none;
            cursor: pointer;
            padding: 3px 4px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-weight: 600;
            margin-top: 2px;
            opacity: 0.75;
            transition: opacity 0.15s;
        }

        .details-toggle:hover { opacity: 1; }

        /* Details panel outside bubble — clear float issues */
        .details-panel {
            width: 100%;
            font-size: 11.5px;
            color: var(--wa-text-secondary);
            background: rgba(255,255,255,0.85);
            border: 1px solid var(--wa-border);
            border-radius: 8px;
            padding: 8px 11px;
            margin-top: 4px;
            display: none;
            box-shadow: 0 1px 2px rgba(11,20,26,0.06);
        }

        .details-panel.open { display: block; }
        .details-panel .timing {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 11px;
            color: #334155;
        }
        .details-panel .source {
            padding: 4px 0;
            border-top: 1px solid var(--wa-border);
            font-size: 11.5px;
            line-height: 1.4;
        }
        .details-panel .source:first-child { border-top: none; }
        .score-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 700;
            margin-right: 4px;
        }

        /* Thinking Indicator */
        .thinking {
            color: var(--wa-text-secondary);
            font-style: italic;
        }
        .thinking::after {
            content: '▍';
            display: inline-block;
            margin-left: 2px;
            animation: blink 0.9s steps(1) infinite;
        }
        @keyframes blink { 50% { opacity: 0; } }

        /* Empty Chat State */
        .empty-state {
            margin: auto;
            text-align: center;
            max-width: 480px;
            background: #ffffff;
            padding: 32px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(11,20,26,0.06);
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--wa-text-primary);
            margin-bottom: 8px;
        }

        .empty-desc {
            font-size: 13.5px;
            color: var(--wa-text-secondary);
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .suggestion-chips {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .chip {
            background: #f0f2f5;
            border: 1px solid var(--wa-border);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            color: var(--wa-text-primary);
            cursor: pointer;
            text-align: left;
            transition: background 0.15s ease;
        }

        .chip:hover {
            background: #e4e6eb;
        }

        /* Pinned Announcement Message Styling */
        .pinned-header {
            font-size: 11px;
            font-weight: 700;
            color: var(--wa-teal-dark);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px dashed rgba(18, 140, 126, 0.2);
        }

        .msg-bubble.pinned-bubble {
            background: #ffffff;
            border-left: 4px solid var(--wa-teal);
            box-shadow: 0 1px 3px rgba(11, 20, 26, 0.15);
            max-width: 100%;
        }

        .suggestion-chips-wrapper {
            margin-top: 14px;
            margin-bottom: 10px;
        }

        .suggestion-title {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--wa-text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Feedback Buttons & Note Input */
        .feedback-actions {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        .feedback-btn {
            background: transparent;
            border: 1px solid transparent;
            color: var(--wa-text-muted);
            padding: 3px 7px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .feedback-btn:hover {
            background: rgba(0,0,0,0.05);
            color: var(--wa-text-primary);
        }

        .feedback-btn.like-btn.active {
            color: #16a34a;
            background: #dcfce7;
            border-color: #86efac;
        }

        .feedback-btn.dislike-btn.active {
            color: #dc2626;
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .feedback-note-input {
            display: none;
            margin-top: 6px;
            gap: 6px;
            width: 100%;
        }

        .feedback-note-input.open {
            display: flex;
        }

        .feedback-note-text {
            flex: 1;
            font-size: 11.5px;
            padding: 5px 9px;
            border: 1px solid var(--wa-border);
            border-radius: 6px;
            outline: none;
            color: var(--wa-text-primary);
            background: #ffffff;
        }

        .feedback-note-send {
            background: var(--wa-teal-dark);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Footer Input Bar */
        footer {
            height: 62px;
            background: var(--wa-header-bg);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid var(--wa-border);
            z-index: 10;
        }

        #chat-form {
            flex: 1;
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 8px;
            padding: 0 14px;
            height: 44px;
            box-shadow: 0 1px 1px rgba(11,20,26,0.05);
        }

        #chat-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14.5px;
            outline: none;
            color: var(--wa-text-primary);
            font-family: inherit;
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            background: var(--wa-teal);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease;
            flex-shrink: 0;
        }

        .send-btn:hover { background: var(--wa-teal-dark); }
        .send-btn:disabled { background: #cccccc; cursor: not-allowed; }

        /* Responsive Overlay */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11, 20, 26, 0.4);
            z-index: 15;
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                top: 0; bottom: 0; left: 0;
                width: 85%;
                max-width: 340px;
                transform: translateX(-100%);
            }
            #sidebar.open { transform: translateX(0); }
            #sidebar-overlay.open { display: block; }
            .mobile-menu-btn { display: flex; }
        }
    </style>
</head>
<body>
    <div id="app">
        <!-- Sidebar Backdrop for Mobile -->
        <div id="sidebar-overlay"></div>

        <!-- WhatsApp Sidebar -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <div class="user-profile">
                    @auth
                        <div class="user-avatar" style="background: #00a884; color: #fff;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                        <span class="profile-title" style="font-size: 14px; font-weight: 600;">{{ auth()->user()->name }}</span>
                    @else
                        <div class="user-avatar">AV</div>
                        <span class="profile-title">AV-CRM Chats</span>
                    @endauth
                </div>
                <div class="sidebar-actions">
                    <button class="icon-btn" id="new-chat-btn" title="New Chat">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>
            </div>

            <div class="search-container">
                <div class="search-inner">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" class="search-input" id="search-input" placeholder="Search or start new chat">
                </div>
            </div>

            <div class="conv-list" id="conv-list">
                <!-- Dynamic WhatsApp styled conversation items -->
            </div>

            <div class="sidebar-footer">
                <div class="status-dot" id="status-dot"></div>
                <span class="storage-status-text" id="storage-status-text">IndexedDB active</span>
                <button class="persist-btn" id="persist-req-btn" style="display: none;">Enable persistence</button>
            </div>
        </aside>

        <!-- WhatsApp Main Chat Workspace -->
        <main id="main">
            <header>
                <div class="header-chat-info">
                    <button class="mobile-menu-btn" id="mobile-menu-btn" title="Open Chats">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <div class="active-avatar" id="active-avatar">AI</div>
                    <div class="active-meta">
                        <div class="active-name">
                            <span id="chat-title">New Chat</span>
                            <button class="icon-btn" id="title-edit-btn" style="width: 24px; height: 24px;" title="Rename Chat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        </div>
                        <span class="active-status">AV-CRM FAQ Assistant • online</span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ route('admin.feedbacks') }}" class="icon-btn" title="View Feedback Dashboard" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    </a>
                    <button class="icon-btn" id="delete-current-btn" title="Delete this Chat">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                    @auth
                        <a href="{{ route('issues.index') }}" class="icon-btn" title="My Issues" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </a>
                        <a href="{{ route('profile.show') }}" class="icon-btn" title="User Profile ({{ auth()->user()->name }})" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="display: inline-flex;">
                            @csrf
                            <button type="submit" class="icon-btn" title="Log Out" style="color: #ef4444; background: none; border: none; cursor: pointer;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" style="background: #00a884; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center;" title="Log In to Dashboard">Log In</a>
                    @endauth
                </div>
            </header>

            <div id="messages-container">
                <div class="messages-inner" id="messages-inner">
                    <!-- Dynamic chat bubbles -->
                </div>
            </div>

            <footer>
                <form id="chat-form">
                    <input type="text" id="chat-input" placeholder="Type a message..." autocomplete="off" required>
                </form>
                <button class="send-btn" id="send-btn" form="chat-form" type="submit" title="Send message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </footer>
        </main>
    </div>

    <script>
        const CHAT_ASK_URL = @json(route('chat.ask'));
        const CHAT_FEEDBACK_URL = @json(route('chat.feedback'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const THINKING_PHRASES = [
            'Typing...',
            'Searching AV-CRM knowledge base...',
            'Consulting FAQ database...',
            'Formatting answer...',
        ];

        // --- IndexedDB Client Store ---
        class LocalChatStore {
            constructor() {
                this.dbName = 'AVCRM_WhatsApp_Chat_DB';
                this.version = 1;
                this.db = null;
            }

            async init() {
                return new Promise((resolve, reject) => {
                    const req = indexedDB.open(this.dbName, this.version);
                    req.onupgradeneeded = (e) => {
                        const db = e.target.result;
                        if (!db.objectStoreNames.contains('conversations')) {
                            const convStore = db.createObjectStore('conversations', { keyPath: 'id' });
                            convStore.createIndex('updated_at', 'updated_at', { unique: false });
                        }
                        if (!db.objectStoreNames.contains('messages')) {
                            const msgStore = db.createObjectStore('messages', { keyPath: 'id', autoIncrement: true });
                            msgStore.createIndex('conversation_id', 'conversation_id', { unique: false });
                        }
                    };
                    req.onsuccess = (e) => {
                        this.db = e.target.result;
                        resolve();
                    };
                    req.onerror = (e) => reject(e.target.error);
                });
            }

            async getConversations() {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('conversations', 'readonly');
                    const store = tx.objectStore('conversations');
                    const req = store.getAll();
                    req.onsuccess = () => {
                        const list = req.result || [];
                        list.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                        resolve(list);
                    };
                });
            }

            async getConversation(id) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('conversations', 'readonly');
                    const store = tx.objectStore('conversations');
                    const req = store.get(id);
                    req.onsuccess = () => resolve(req.result || null);
                });
            }

            async saveConversation(conv) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('conversations', 'readwrite');
                    const store = tx.objectStore('conversations');
                    store.put(conv);
                    tx.oncomplete = () => resolve();
                });
            }

            async deleteConversation(id) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction(['conversations', 'messages'], 'readwrite');
                    tx.objectStore('conversations').delete(id);
                    
                    const msgStore = tx.objectStore('messages');
                    const idx = msgStore.index('conversation_id');
                    const req = idx.getAllKeys(id);
                    req.onsuccess = () => {
                        (req.result || []).forEach(k => msgStore.delete(k));
                    };
                    tx.oncomplete = () => resolve();
                });
            }

            async getMessages(conversationId) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('messages', 'readonly');
                    const store = tx.objectStore('messages');
                    const idx = store.index('conversation_id');
                    const req = idx.getAll(conversationId);
                    req.onsuccess = () => {
                        const msgs = req.result || [];
                        msgs.sort((a, b) => a.id - b.id);
                        resolve(msgs);
                    };
                });
            }

            async addMessage(msg) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('messages', 'readwrite');
                    const store = tx.objectStore('messages');
                    const req = store.add(msg);
                    req.onsuccess = () => resolve(req.result);
                });
            }

            async updateMessage(msg) {
                return new Promise((resolve) => {
                    const tx = this.db.transaction('messages', 'readwrite');
                    const store = tx.objectStore('messages');
                    store.put(msg);
                    tx.oncomplete = () => resolve();
                });
            }
        }

        // --- App State ---
        const store = new LocalChatStore();
        let currentConversationId = null;
        let searchQuery = '';

        // DOM elements
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const newChatBtn = document.getElementById('new-chat-btn');
        const searchInput = document.getElementById('search-input');
        const convListEl = document.getElementById('conv-list');
        const chatTitleEl = document.getElementById('chat-title');
        const activeAvatarEl = document.getElementById('active-avatar');
        const titleEditBtn = document.getElementById('title-edit-btn');
        const deleteCurrentBtn = document.getElementById('delete-current-btn');
        const messagesInner = document.getElementById('messages-inner');
        const messagesContainer = document.getElementById('messages-container');
        const chatForm = document.getElementById('chat-form');
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        const statusDot = document.getElementById('status-dot');
        const storageStatusText = document.getElementById('storage-status-text');
        const persistReqBtn = document.getElementById('persist-req-btn');

        function generateUuid() {
            return 'conv_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function formatTime(isoStr) {
            const d = new Date(isoStr || Date.now());
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function formatDuration(ms) {
            if (ms < 1000) return `${Math.round(ms)}ms`;
            const sec = ms / 1000;
            return sec < 10 ? `${sec.toFixed(2)}s` : `${sec.toFixed(1)}s`;
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Check Firefox Persistent Storage
        async function checkPersistentStorage() {
            if (navigator.storage && navigator.storage.persisted) {
                const isPersisted = await navigator.storage.persisted();
                if (isPersisted) {
                    statusDot.classList.add('persisted');
                    storageStatusText.textContent = 'Storage Persisted';
                    persistReqBtn.style.display = 'none';
                } else {
                    statusDot.classList.remove('persisted');
                    storageStatusText.textContent = 'Storage Temporary';
                    persistReqBtn.style.display = 'block';
                }
            } else {
                storageStatusText.textContent = 'IndexedDB Active';
            }
        }

        persistReqBtn.addEventListener('click', async () => {
            if (navigator.storage && navigator.storage.persist) {
                const granted = await navigator.storage.persist();
                if (granted) {
                    statusDot.classList.add('persisted');
                    storageStatusText.textContent = 'Storage Persisted';
                    persistReqBtn.style.display = 'none';
                    alert('Firefox persistent storage enabled!');
                }
            }
        });

        // Sidebar Conversations List
        async function renderSidebar() {
            let conversations = await store.getConversations();

            if (searchQuery.trim()) {
                const q = searchQuery.toLowerCase();
                conversations = conversations.filter(c => (c.title || '').toLowerCase().includes(q));
            }

            convListEl.innerHTML = '';

            if (conversations.length === 0) {
                convListEl.innerHTML = '<div style="padding: 20px; text-align: center; font-size: 13px; color: var(--wa-text-muted);">No chats found</div>';
                return;
            }

            for (const c of conversations) {
                const item = document.createElement('div');
                item.className = `conv-item ${c.id === currentConversationId ? 'active' : ''}`;
                
                const initials = (c.title || 'Chat').slice(0, 2).toUpperCase();
                const msgs = await store.getMessages(c.id);
                const lastMsg = msgs.length ? msgs[msgs.length - 1].content : 'Click to start chatting...';
                const timeStr = formatTime(c.updated_at);

                item.innerHTML = `
                    <div class="conv-avatar">${initials}</div>
                    <div class="conv-info">
                        <div class="conv-top-line">
                            <span class="conv-name">${escapeHtml(c.title || 'New Chat')}</span>
                            <span class="conv-time">${timeStr}</span>
                        </div>
                        <div class="conv-bottom-line">
                            <span class="conv-preview">${escapeHtml(lastMsg)}</span>
                        </div>
                    </div>
                `;

                item.addEventListener('click', () => switchConversation(c.id));
                convListEl.appendChild(item);
            }
        }

        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            renderSidebar();
        });

        async function switchConversation(id) {
            currentConversationId = id;
            localStorage.setItem('active_conv_id', id);
            closeMobileSidebar();

            const conv = await store.getConversation(id);
            if (conv) {
                chatTitleEl.textContent = conv.title || 'New Chat';
                activeAvatarEl.textContent = (conv.title || 'AI').slice(0, 2).toUpperCase();
            }

            await renderMessages();
            await renderSidebar();
        }

        async function createNewChat() {
            const newId = generateUuid();
            const conv = {
                id: newId,
                title: 'New Chat',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
            };
            await store.saveConversation(conv);
            await switchConversation(newId);
        }

        deleteCurrentBtn.addEventListener('click', async () => {
            if (!currentConversationId) return;
            if (confirm('Delete this chat thread?')) {
                await store.deleteConversation(currentConversationId);
                const remaining = await store.getConversations();
                if (remaining.length > 0) {
                    await switchConversation(remaining[0].id);
                } else {
                    await createNewChat();
                }
            }
        });

        titleEditBtn.addEventListener('click', async () => {
            if (!currentConversationId) return;
            const conv = await store.getConversation(currentConversationId);
            if (!conv) return;

            const newTitle = prompt('Rename conversation:', conv.title);
            if (newTitle && newTitle.trim()) {
                conv.title = newTitle.trim();
                conv.updated_at = new Date().toISOString();
                await store.saveConversation(conv);
                chatTitleEl.textContent = conv.title;
                activeAvatarEl.textContent = conv.title.slice(0, 2).toUpperCase();
                await renderSidebar();
            }
        });

        // Messages Render
        async function renderMessages() {
            messagesInner.innerHTML = '';
            if (!currentConversationId) return;

            // Date Badge
            const dateBadge = document.createElement('div');
            dateBadge.className = 'date-badge';
            dateBadge.textContent = 'TODAY';
            messagesInner.appendChild(dateBadge);

            // Always render pinned welcome row from AI assistant
            appendPinnedWelcomeRow();

            const msgs = await store.getMessages(currentConversationId);

            if (msgs.length === 0) {
                renderSuggestionChips();
                return;
            }

            msgs.forEach((m, idx) => {
                let userQuestion = '';
                if (m.role === 'assistant' && idx > 0 && msgs[idx - 1].role === 'user') {
                    userQuestion = msgs[idx - 1].content;
                }
                appendMessageRow(m.role, m.content, m.timestamp, m.timing, m.sources, m.grounded, m.rating, m.feedback_text, m, userQuestion);
            });

            scrollToBottom();
        }

        function appendPinnedWelcomeRow() {
            const row = document.createElement('div');
            row.className = 'msg-row assistant pinned-row';

            const container = document.createElement('div');
            container.className = 'msg-container';

            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble pinned-bubble';

            const meta = document.createElement('span');
            meta.className = 'msg-meta';
            meta.innerHTML = `<span class="msg-time">System</span>`;
            bubble.appendChild(meta);

            const header = document.createElement('div');
            header.className = 'pinned-header';
            header.innerHTML = `
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4H17V2H7V4H8V12L6 14V16H11.5V22H12.5V16H18V14L16 12Z"/></svg>
                <span>PINNED ANNOUNCEMENT</span>
            `;
            bubble.appendChild(header);

            const contentText = document.createElement('div');
            contentText.className = 'bubble-text';
            contentText.innerHTML = `Hello! 👋 I am the <strong>AV-CRM AI Chatbot</strong>. I can help you resolve common issues quickly.<br><br>📌 <em>Note:</em> At the moment, I can only answer questions related to <strong>Ansar Recruitment</strong>, but I'm continuously learning and will be able to answer questions on other projects soon.`;
            bubble.appendChild(contentText);

            const clear = document.createElement('div');
            clear.style.clear = 'both';
            bubble.appendChild(clear);

            container.appendChild(bubble);
            row.appendChild(container);
            messagesInner.appendChild(row);
        }

        function renderSuggestionChips() {
            const wrapper = document.createElement('div');
            wrapper.className = 'suggestion-chips-wrapper';
            wrapper.innerHTML = `
                <div class="suggestion-title">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Suggested Questions
                </div>
                <div class="suggestion-chips">
                    <div class="chip" onclick="fillInput('How to log a new ticket in AV-CRM?')">How to log a new ticket in AV-CRM?</div>
                    <div class="chip" onclick="fillInput('Amar bill missing notification solution ki?')">Amar bill missing notification solution ki?</div>
                    <div class="chip" onclick="fillInput('কোন ক্যাটাগরিতে কল রেজিস্টার করতে হবে?')">কোন ক্যাটাগরিতে কল রেজিস্টার করতে হবে?</div>
                </div>
            `;
            messagesInner.appendChild(wrapper);
        }

        function renderEmptyState() {
            renderSuggestionChips();
        }

        window.fillInput = function(text) {
            chatInput.value = text;
            chatInput.focus();
        };

        function parseMarkdown(text) {
            if (!text) return '';
            let safe = escapeHtml(text);

            // Bold: **text** or __text__
            safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            safe = safe.replace(/__(.*?)__/g, '<strong>$1</strong>');

            // Bullet lists: * item or - item -> • item
            safe = safe.replace(/^(\s*)[\*\-] (.*)$/gm, '$1• $2');

            // Inline code `code`
            safe = safe.replace(/`([^`\n]+)`/g, '<code style="background: rgba(0,0,0,0.06); padding: 1px 4px; border-radius: 4px; font-family: monospace; font-size: 13px;">$1</code>');

            return safe;
        }

        // Message Row Generator
        function appendMessageRow(role, text, timestamp = null, timing = null, sources = [], grounded = false, rating = null, feedbackText = null, msgObj = null, questionContext = '') {
            const row = document.createElement('div');
            row.className = `msg-row ${role}`;

            const container = document.createElement('div');
            container.className = 'msg-container';

            // --- Bubble ---
            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble';

            // WhatsApp float-right meta trick:
            const meta = document.createElement('span');
            meta.className = 'msg-meta';

            const timeEl = document.createElement('span');
            timeEl.className = 'msg-time';
            timeEl.textContent = formatTime(timestamp);
            meta.appendChild(timeEl);

            if (role === 'user') {
                const ticks = document.createElement('span');
                ticks.className = 'ticks-icon';
                ticks.innerHTML = `<svg width="15" height="10" viewBox="0 0 16 11" fill="none"><path d="M11.0001 0.75L4.8126 6.9375L2.0001 4.125L0.75 5.375L4.8126 9.4375L12.2501 2L11.0001 0.75ZM15.0001 0.75L8.8126 6.9375L7.96885 6.09375L6.71885 7.34375L8.8126 9.4375L16.2501 2L15.0001 0.75Z" fill="currentColor"/></svg>`;
                meta.appendChild(ticks);
            }

            bubble.appendChild(meta);

            const contentText = document.createElement('span');
            contentText.className = 'bubble-text';
            contentText.innerHTML = parseMarkdown(text);
            bubble.appendChild(contentText);

            const clearfix = document.createElement('div');
            clearfix.style.clear = 'both';
            bubble.appendChild(clearfix);

            container.appendChild(bubble);

            // --- Details toggle + panel & Like/Dislike Actions for Assistant ---
            if (role === 'assistant' && text !== 'Hello! 👋 I am the AV-CRM AI Chatbot...') {
                const flexBar = document.createElement('div');
                flexBar.style.display = 'flex';
                flexBar.style.alignItems = 'center';
                flexBar.style.justifyContent = 'space-between';
                flexBar.style.marginTop = '2px';

                let detailsToggle = null;
                let detailsPanel = null;

                if (timing || (sources && sources.length > 0)) {
                    detailsToggle = document.createElement('button');
                    detailsToggle.className = 'details-toggle';
                    detailsToggle.innerHTML = '▾ Details';

                    detailsPanel = document.createElement('div');
                    detailsPanel.className = 'details-panel';

                    let timingHtml = '';
                    if (timing) {
                        timingHtml = `<div class="timing">
                            <span>⏱ <strong>${formatDuration(timing.total_ms || 0)}</strong> total</span>
                            <span>🔍 <strong>${formatDuration(timing.retrieval_ms || 0)}</strong> retrieval</span>
                            <span>🧠 <strong>${formatDuration(timing.generation_ms || 0)}</strong> generation</span>
                        </div>`;
                    }

                    let sourcesHtml = grounded && sources && sources.length
                        ? sources.map(s => `<div class="source"><span class="score-badge">${s.score}</span>FAQ #${s.id} — ${escapeHtml(s.question)}</div>`).join('')
                        : '<div class="source">No direct FAQ match (below similarity threshold).</div>';

                    detailsPanel.innerHTML = timingHtml + sourcesHtml;

                    detailsToggle.addEventListener('click', () => {
                        detailsPanel.classList.toggle('open');
                        detailsToggle.innerHTML = detailsPanel.classList.contains('open') ? '▴ Hide details' : '▾ Details';
                    });

                    flexBar.appendChild(detailsToggle);
                } else {
                    flexBar.appendChild(document.createElement('div'));
                }

                // Like / Dislike Buttons
                const feedbackBar = document.createElement('div');
                feedbackBar.className = 'feedback-actions';

                const likeBtn = document.createElement('button');
                likeBtn.className = `feedback-btn like-btn ${rating === 'like' ? 'active' : ''}`;
                likeBtn.title = 'Helpful response';
                likeBtn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>`;

                const dislikeBtn = document.createElement('button');
                dislikeBtn.className = `feedback-btn dislike-btn ${rating === 'dislike' ? 'active' : ''}`;
                dislikeBtn.title = 'Needs improvement';
                dislikeBtn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"/></svg>`;

                feedbackBar.appendChild(likeBtn);
                feedbackBar.appendChild(dislikeBtn);
                flexBar.appendChild(feedbackBar);

                container.appendChild(flexBar);
                if (detailsPanel) container.appendChild(detailsPanel);

                // Note input for Dislike feedback
                const noteWrapper = document.createElement('div');
                noteWrapper.className = `feedback-note-input ${rating === 'dislike' && feedbackText ? 'open' : ''}`;
                noteWrapper.innerHTML = `
                    <input type="text" class="feedback-note-text" placeholder="What was wrong or missing? (Optional)" value="${escapeHtml(feedbackText || '')}">
                    <button class="feedback-note-send">Send</button>
                `;
                container.appendChild(noteWrapper);

                likeBtn.addEventListener('click', async () => {
                    likeBtn.classList.add('active');
                    dislikeBtn.classList.remove('active');
                    noteWrapper.classList.remove('open');
                    if (msgObj) {
                        msgObj.rating = 'like';
                        await store.updateMessage(msgObj);
                    }
                    sendFeedback(questionContext, text, 'like', null, sources, grounded);
                });

                dislikeBtn.addEventListener('click', async () => {
                    const isAlreadyDisliked = dislikeBtn.classList.contains('active');
                    dislikeBtn.classList.add('active');
                    likeBtn.classList.remove('active');
                    noteWrapper.classList.add('open');
                    if (!isAlreadyDisliked) {
                        noteWrapper.querySelector('.feedback-note-text').focus();
                    }
                    if (msgObj) {
                        msgObj.rating = 'dislike';
                        await store.updateMessage(msgObj);
                    }
                    sendFeedback(questionContext, text, 'dislike', null, sources, grounded);
                });

                noteWrapper.querySelector('.feedback-note-send').addEventListener('click', async () => {
                    const comment = noteWrapper.querySelector('.feedback-note-text').value.trim();
                    if (msgObj) {
                        msgObj.feedback_text = comment;
                        await store.updateMessage(msgObj);
                    }
                    await sendFeedback(questionContext, text, 'dislike', comment, sources, grounded);
                    const btn = noteWrapper.querySelector('.feedback-note-send');
                    btn.textContent = 'Saved!';
                    setTimeout(() => { btn.textContent = 'Send'; }, 1800);
                });
            }

            row.appendChild(container);
            messagesInner.appendChild(row);
            scrollToBottom();
            return { row, bubble, contentText };
        }

        async function sendFeedback(question, answer, rating, feedbackText = null, sources = [], grounded = true) {
            try {
                await fetch(CHAT_FEEDBACK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        question: question || 'User Support Question',
                        answer: answer,
                        rating: rating,
                        feedback_text: feedbackText,
                        sources: sources,
                        grounded: grounded,
                    })
                });
            } catch (err) {
                console.error('Failed to submit feedback to server:', err);
            }
        }

        function startThinking() {
            const row = document.createElement('div');
            row.className = 'msg-row assistant';

            const container = document.createElement('div');
            container.className = 'msg-container';

            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble thinking';

            container.appendChild(bubble);
            row.appendChild(container);
            messagesInner.appendChild(row);
            scrollToBottom();

            let phraseIndex = 0;
            let charIndex = 0;
            let typingTimer = null;
            let pauseTimer = null;
            let stopped = false;

            function typeNextChar() {
                if (stopped) return;
                const phrase = THINKING_PHRASES[phraseIndex];
                if (charIndex <= phrase.length) {
                    bubble.textContent = phrase.slice(0, charIndex);
                    charIndex++;
                    scrollToBottom();
                    typingTimer = setTimeout(typeNextChar, 30 + Math.random() * 30);
                } else {
                    pauseTimer = setTimeout(() => {
                        phraseIndex = (phraseIndex + 1) % THINKING_PHRASES.length;
                        charIndex = 0;
                        bubble.textContent = '';
                        typeNextChar();
                    }, 800);
                }
            }

            typeNextChar();

            return {
                row,
                bubble,
                stop: () => {
                    stopped = true;
                    clearTimeout(typingTimer);
                    clearTimeout(pauseTimer);
                }
            };
        }

        // Chat Form Submission
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const question = chatInput.value.trim();
            if (!question) return;

            if (!currentConversationId) {
                await createNewChat();
            }

            const suggestionsEl = messagesInner.querySelector('.suggestion-chips-wrapper');
            if (suggestionsEl) {
                suggestionsEl.remove();
            }

            const nowIso = new Date().toISOString();

            // Append User Message to UI & DB (Right-aligned user bubble)
            appendMessageRow('user', question, nowIso);
            await store.addMessage({
                conversation_id: currentConversationId,
                role: 'user',
                content: question,
                timestamp: nowIso
            });

            // Fetch prior context history
            const previousMsgs = await store.getMessages(currentConversationId);
            const history = previousMsgs
                .slice(0, -1)
                .map(m => ({ role: m.role, content: m.content }));

            // Update conversation title if new
            const conv = await store.getConversation(currentConversationId);
            if (conv && (conv.title === 'New Chat' || !conv.title)) {
                conv.title = question.slice(0, 32) + (question.length > 32 ? '...' : '');
                conv.updated_at = nowIso;
                await store.saveConversation(conv);
                chatTitleEl.textContent = conv.title;
                activeAvatarEl.textContent = conv.title.slice(0, 2).toUpperCase();
                await renderSidebar();
            } else if (conv) {
                conv.updated_at = nowIso;
                await store.saveConversation(conv);
                await renderSidebar();
            }

            chatInput.value = '';
            chatInput.disabled = true;
            sendBtn.disabled = true;

            const thinking = startThinking();
            let assistantRowRes = null;
            let answerText = '';

            try {
                const response = await fetch(CHAT_ASK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ question, history }),
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                let finalMetadata = null;

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    let boundary;
                    while ((boundary = buffer.indexOf('\n\n')) !== -1) {
                        const frame = buffer.slice(0, boundary);
                        buffer = buffer.slice(boundary + 2);

                        const line = frame.split('\n').find(l => l.startsWith('data:'));
                        if (!line) continue;

                        const payload = JSON.parse(line.slice(5).trim());

                        if (payload.token) {
                            if (!assistantRowRes) {
                                thinking.stop();
                                thinking.row.remove();
                                assistantRowRes = appendMessageRow('assistant', '', new Date().toISOString());
                            }
                            answerText += payload.token;
                            assistantRowRes.contentText.innerHTML = parseMarkdown(answerText);
                            scrollToBottom();
                        }

                        if (payload.done) {
                            if (!assistantRowRes) {
                                thinking.stop();
                                thinking.row.remove();
                                assistantRowRes = appendMessageRow('assistant', answerText || 'No response generated.', new Date().toISOString());
                            }
                            finalMetadata = payload;
                        }
                    }
                }

                // Save Assistant Message
                await store.addMessage({
                    conversation_id: currentConversationId,
                    role: 'assistant',
                    content: answerText,
                    grounded: finalMetadata ? finalMetadata.grounded : false,
                    sources: finalMetadata ? finalMetadata.sources : [],
                    timing: finalMetadata ? finalMetadata.timing : null,
                    timestamp: new Date().toISOString()
                });

                await renderMessages(); // Re-render to display details panel neatly

            } catch (err) {
                thinking.stop();
                thinking.row.remove();
                appendMessageRow('assistant', 'Something went wrong while generating response. Please try again.', new Date().toISOString());
                console.error(err);
            } finally {
                chatInput.disabled = false;
                sendBtn.disabled = false;
                chatInput.focus();
            }
        });

        // Mobile Sidebar
        function openMobileSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('open');
        }
        function closeMobileSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('open');
        }

        mobileMenuBtn.addEventListener('click', openMobileSidebar);
        sidebarOverlay.addEventListener('click', closeMobileSidebar);
        newChatBtn.addEventListener('click', createNewChat);

        // App Init
        async function initApp() {
            await store.init();
            await checkPersistentStorage();

            const conversations = await store.getConversations();
            const savedActiveId = localStorage.getItem('active_conv_id');

            if (savedActiveId && conversations.some(c => c.id === savedActiveId)) {
                await switchConversation(savedActiveId);
            } else if (conversations.length > 0) {
                await switchConversation(conversations[0].id);
            } else {
                await createNewChat();
            }
        }

        initApp();
    </script>
</body>
</html>