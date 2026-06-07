<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AIEC CRM')</title>
    <link rel="icon" href="{{ asset('images/aiec-icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/aiec-logo.png') }}">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --aiec-blue: #1a4d8f;
            --aiec-red: #e2231a;
            --aiec-nav-bg: #ffffff;
            --aiec-sidebar-bg: #1e293b;
        }
        body { background: #f4f6f9; }
        .app-topbar {
            background: var(--aiec-nav-bg);
            border-bottom: 1px solid #e2e8f0;
            padding: .5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .aiec-brand__logo {
            height: 48px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            display: block;
        }
        .app-topbar .topbar-actions {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-left: auto;
        }
        .topbar-user {
            font-size: .875rem;
            color: #475569;
            white-space: nowrap;
        }
        .topbar-user strong { color: #0f172a; }
        .app-body { display: flex; min-height: calc(100vh - 65px); }
        .sidebar {
            min-height: 100%;
            background: var(--aiec-sidebar-bg);
            width: 240px;
            flex-shrink: 0;
            transition: width .2s ease, padding .2s ease;
            overflow: hidden;
        }
        body.sidebar-collapsed .sidebar {
            width: 0;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .sidebar-toggle-btn {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            display: block;
            padding: .6rem 1rem;
            border-radius: .35rem;
        }
        .sidebar-link-with-badge {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }
        .sidebar-count-badge {
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0 .35rem;
            background: #dc3545;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            line-height: 1;
        }
        .sidebar-badge-group {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            flex-shrink: 0;
        }
        .sidebar-count-badge.today {
            background: #ffc107;
            color: #111827;
        }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: #fff; }
        .sidebar .sidebar-crm-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            padding: 0 1rem;
            margin-bottom: .5rem;
        }
        .chat-box { max-height: 420px; overflow-y: auto; background: #e5ddd5; border-radius: 8px; padding: 1rem; }
        .chat-bubble { max-width: 75%; padding: .5rem .75rem; border-radius: 8px; margin-bottom: .5rem; background: #fff; }
        .chat-bubble.mine { margin-left: auto; background: #dcf8c6; }
        .mention-highlight { color: var(--aiec-blue); font-weight: 600; }
        .mention-wrap { position: relative; }
        .mention-dropdown {
            position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px;
            background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.15); max-height: 200px; overflow-y: auto; z-index: 1050;
        }
        .mention-item {
            display: block; width: 100%; text-align: left; border: 0; background: transparent;
            padding: .5rem .75rem; cursor: pointer;
        }
        .mention-item:hover, .mention-item.active { background: #e7f1ff; }
        .global-search-shell {
            width: min(520px, 46vw);
            align-items: center;
            gap: .55rem;
        }
        .global-search-field { min-width: 0; flex: 1; }
        .search-results {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 360px;
            overflow-y: auto;
        }
        .deep-search-toggle {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
            font-size: .78rem;
            color: #475569;
            user-select: none;
        }
        .deep-search-toggle .form-check-input {
            margin: 0;
            width: 2rem;
            height: 1rem;
            cursor: pointer;
        }
        .search-result-title {
            display: flex;
            gap: .45rem;
            align-items: center;
            flex-wrap: wrap;
            font-weight: 600;
            color: #0f172a;
        }
        .search-result-meta {
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            margin-top: .2rem;
            font-size: .72rem;
            color: #64748b;
        }
        .search-result-match {
            margin-top: .35rem;
            font-size: .74rem;
            color: #334155;
        }
        .search-result-match div {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .notification-dropdown-menu { width: 360px; max-width: 95vw; }
        .notification-list { max-height: 380px; overflow-y: auto; }
        .notification-item {
            display: block; padding: .75rem 1rem; border-bottom: 1px solid #eee;
            text-decoration: none; color: inherit;
        }
        .notification-item:hover { background: #f8f9fa; }
        .notification-item.unread { background: #e7f1ff; }
        #notif-badge { font-size: 10px; min-width: 18px; }
        .login-brand__logo { max-width: 280px; width: 100%; height: auto; }
    </style>
    @stack('styles')
</head>
<body>
@auth
<header class="app-topbar">
    @include('partials.brand-logo')
    <button type="button" class="btn btn-light border sidebar-toggle-btn" id="sidebar-toggle" aria-label="Toggle navigation" aria-expanded="true">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-actions">
        <div class="global-search-shell d-none d-md-flex">
            <div class="position-relative global-search-field">
                <input type="text" id="global-search" class="form-control form-control-sm" placeholder="Search name, PID, phone..." autocomplete="off">
                <div id="search-results" class="search-results list-group shadow d-none"></div>
            </div>
            <label class="deep-search-toggle" for="deep-search-toggle">
                <input type="checkbox" id="deep-search-toggle" class="form-check-input" role="switch">
                <span>Deep search</span>
            </label>
        </div>
        <div class="dropdown">
            <button type="button"
                    class="btn btn-light border rounded-circle position-relative"
                    id="notif-bell-btn"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    data-notifications-url="{{ route('notifications.index') }}"
                    data-read-all-url="{{ route('notifications.read-all') }}"
                    aria-label="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notif-badge">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                    <strong>Notifications</strong>
                    <button type="button" class="btn btn-link btn-sm p-0" id="notif-mark-all">Mark all read</button>
                </div>
                <div id="notif-list" class="notification-list">
                    <div class="text-center text-muted py-4">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="topbar-user d-none d-lg-block">
        <strong>{{ auth()->user()->name }}</strong>
        <span class="text-muted">· {{ auth()->user()->role }}</span>
    </div>
</header>
<div class="app-body">
    <aside class="sidebar p-3 text-white">
        <div class="sidebar-crm-label">Menu</div>
        <nav class="d-flex flex-column gap-1">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            @if(in_array(auth()->user()->role, ['receptionist', 'telecaller'], true))
                <a href="{{ route('visiting-clients.index') }}" class="sidebar-link-with-badge {{ request()->routeIs('visiting-clients.*') ? 'active' : '' }}">
                    <span>Visiting Client</span>
                    @if(($visitingClientsCount ?? 0) > 0)
                        <span class="sidebar-count-badge">{{ $visitingClientsCount > 99 ? '99+' : $visitingClientsCount }}</span>
                    @endif
                </a>
            @endif
            @if(auth()->user()->role === 'receptionist')
                <a href="{{ route('tab-entries.index') }}" class="sidebar-link-with-badge {{ request()->routeIs('tab-entries.*') ? 'active' : '' }}">
                    <span>Tab Entries</span>
                    @if(($tabEntriesCount ?? 0) > 0)
                        <span class="sidebar-count-badge">{{ $tabEntriesCount > 99 ? '99+' : $tabEntriesCount }}</span>
                    @endif
                </a>
            @endif
            @if(auth()->user()->role !== 'telecaller')
                <a href="{{ route('new-cases.index') }}" class="sidebar-link-with-badge {{ request()->routeIs('new-cases.*') ? 'active' : '' }}">
                    <span>New Cases</span>
                    @if(($newCasesCount ?? 0) > 0)
                        <span class="sidebar-count-badge">{{ $newCasesCount > 99 ? '99+' : $newCasesCount }}</span>
                    @endif
                </a>
            @endif
            @if(auth()->user()->role === 'telecaller')
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.index') || request()->routeIs('customers.show') ? 'active' : '' }}">My Leads</a>
                <a href="{{ route('customers.create-telecaller') }}" class="{{ request()->routeIs('customers.create-telecaller') ? 'active' : '' }}">Add Lead</a>
            @else
                <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">Customers</a>
                @if(in_array(auth()->user()->role, ['admin','receptionist','director']))
                    <a href="{{ route('customers.create') }}">Add Customer</a>
                @endif
            @endif
            @if(auth()->user()->role !== 'receptionist')
                <a href="{{ route('follow-ups.index') }}" class="sidebar-link-with-badge {{ request()->routeIs('follow-ups.*') ? 'active' : '' }}">
                    <span>My Follow-Ups</span>
                    @if(($overdueFollowUpsCount ?? 0) > 0 || ($todayFollowUpsCount ?? 0) > 0)
                        <span class="sidebar-badge-group">
                            @if(($overdueFollowUpsCount ?? 0) > 0)
                                <span class="sidebar-count-badge">{{ $overdueFollowUpsCount > 99 ? '99+' : $overdueFollowUpsCount }}</span>
                            @endif
                            @if(($todayFollowUpsCount ?? 0) > 0)
                                <span class="sidebar-count-badge today">{{ $todayFollowUpsCount > 99 ? '99+' : $todayFollowUpsCount }}</span>
                            @endif
                        </span>
                    @endif
                </a>
            @endif
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">Users</a>
                <a href="{{ route('process-timelines.index') }}" class="{{ request()->routeIs('process-timelines.*') ? 'active' : '' }}">Process Timelines</a>
                <a href="{{ route('qualifications.index') }}" class="{{ request()->routeIs('qualifications.*') ? 'active' : '' }}">Qualifications</a>
                <a href="{{ route('google-chat-settings.edit') }}" class="{{ request()->routeIs('google-chat-settings.*') ? 'active' : '' }}">Google Chat</a>
            @endif
        </nav>
        <form action="{{ route('logout') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm w-100">Logout</button>
        </form>
    </aside>
    <main class="flex-grow-1 p-4">
        <div class="d-md-none mb-3 position-relative global-search-mobile-wrap">
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="global-search-mobile" class="form-control" placeholder="Search name, PID, phone..." autocomplete="off">
                <label class="deep-search-toggle" for="deep-search-toggle-mobile">
                    <input type="checkbox" id="deep-search-toggle-mobile" class="form-check-input" role="switch">
                    <span>Deep</span>
                </label>
            </div>
            <div id="search-results-mobile" class="search-results list-group shadow d-none"></div>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4 class="mb-0">@yield('page-title', 'Dashboard')</h4>
            @hasSection('page-actions')
                <div>@yield('page-actions')</div>
            @endif
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
@else
    @yield('content')
@endauth
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@auth
<script>
(function () {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarState = localStorage.getItem('aiec.sidebar.collapsed');

    if (sidebarState === '1') {
        document.body.classList.add('sidebar-collapsed');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            localStorage.setItem('aiec.sidebar.collapsed', collapsed ? '1' : '0');
        });
    }

    const searchUrl = @json(url('/search/customers'));
    const customerBaseUrl = @json(url('/customers'));

    function text(value, fallback = '') {
        return value === null || value === undefined || value === '' ? fallback : value;
    }

    function appendMeta(parent, label, value) {
        if (!value) return;
        const span = document.createElement('span');
        span.className = 'badge bg-light text-secondary border';
        span.textContent = `${label}: ${value}`;
        parent.appendChild(span);
    }

    function renderResults(results, data) {
        results.innerHTML = '';
        if (!data.length) {
            results.innerHTML = '<div class="list-group-item text-muted">No results</div>';
            return;
        }

        data.forEach(c => {
            const a = document.createElement('a');
            a.href = `${customerBaseUrl}/${c.id}`;
            a.className = 'list-group-item list-group-item-action';

            const title = document.createElement('div');
            title.className = 'search-result-title';

            const pid = document.createElement('span');
            pid.textContent = text(c.pid, 'No PID');
            title.appendChild(pid);

            const name = document.createElement('span');
            name.textContent = `- ${text(c.name, 'Unnamed')}`;
            title.appendChild(name);

            const phone = document.createElement('span');
            phone.className = 'text-muted fw-normal';
            phone.textContent = `- ${text(c.phone, 'No phone')}`;
            title.appendChild(phone);

            const meta = document.createElement('div');
            meta.className = 'search-result-meta';
            appendMeta(meta, 'Country', c.country);
            appendMeta(meta, 'Visa', c.visa_type);
            appendMeta(meta, 'Status', c.status);

            a.appendChild(title);
            a.appendChild(meta);

            if (Array.isArray(c.matches) && c.matches.length) {
                const matchWrap = document.createElement('div');
                matchWrap.className = 'search-result-match';
                c.matches.forEach(match => {
                    const line = document.createElement('div');
                    line.textContent = match;
                    matchWrap.appendChild(line);
                });
                a.appendChild(matchWrap);
            }

            results.appendChild(a);
        });
    }

    function bindSearch(input, results, deepToggle) {
        if (!input) return;
        let timer;
        let controller;

        function runSearch() {
            clearTimeout(timer);
            const q = input.value.trim();
            if (!results) return;
            if (q.length < 2) { results.classList.add('d-none'); return; }
            timer = setTimeout(() => {
                if (controller) controller.abort();
                controller = new AbortController();

                const params = new URLSearchParams({ q });
                if (deepToggle && deepToggle.checked) {
                    params.set('deep', '1');
                }

                fetch(`${searchUrl}?${params.toString()}`, {
                    signal: controller.signal,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    results.innerHTML = '';
                    if (!data.length) {
                        results.innerHTML = '<div class="list-group-item text-muted">No results</div>';
                    } else {
                        data.forEach(c => {
                            const a = document.createElement('a');
                            a.href = `${customerBaseUrl}/${c.id}`;
                            a.className = 'list-group-item list-group-item-action';
                            a.textContent = '';
                            const title = document.createElement('div');
                            title.className = 'search-result-title';
                            const pid = document.createElement('span');
                            pid.textContent = text(c.pid, 'No PID');
                            const name = document.createElement('span');
                            name.textContent = `- ${text(c.name, 'Unnamed')}`;
                            const phone = document.createElement('span');
                            phone.className = 'text-muted fw-normal';
                            phone.textContent = `- ${text(c.phone, 'No phone')}`;
                            title.appendChild(pid);
                            title.appendChild(name);
                            title.appendChild(phone);
                            const meta = document.createElement('div');
                            meta.className = 'search-result-meta';
                            appendMeta(meta, 'Country', c.country);
                            appendMeta(meta, 'Visa', c.visa_type);
                            appendMeta(meta, 'Status', c.status);
                            a.appendChild(title);
                            a.appendChild(meta);
                            if (Array.isArray(c.matches) && c.matches.length) {
                                const matchWrap = document.createElement('div');
                                matchWrap.className = 'search-result-match';
                                c.matches.forEach(match => {
                                    const line = document.createElement('div');
                                    line.textContent = match;
                                    matchWrap.appendChild(line);
                                });
                                a.appendChild(matchWrap);
                            }
                            results.appendChild(a);
                        });
                    }
                    results.classList.remove('d-none');
                })
                .catch(error => {
                    if (error.name !== 'AbortError') {
                        results.innerHTML = '<div class="list-group-item text-danger">Search failed</div>';
                        results.classList.remove('d-none');
                    }
                });
            }, deepToggle && deepToggle.checked ? 450 : 250);
        }

        input.addEventListener('input', runSearch);
        if (deepToggle) {
            deepToggle.addEventListener('change', runSearch);
        }
    }
    bindSearch(
        document.getElementById('global-search'),
        document.getElementById('search-results'),
        document.getElementById('deep-search-toggle')
    );
    bindSearch(
        document.getElementById('global-search-mobile'),
        document.getElementById('search-results-mobile'),
        document.getElementById('deep-search-toggle-mobile')
    );
    document.addEventListener('click', e => {
        document.querySelectorAll('.search-results').forEach(results => {
            const shell = results.closest('.global-search-shell, .global-search-mobile-wrap');
            if (shell && !shell.contains(e.target)) {
                results.classList.add('d-none');
            }
        });
    });
})();
</script>
<script src="{{ asset('js/notifications.js') }}"></script>
@endauth
@stack('scripts')
</body>
</html>
