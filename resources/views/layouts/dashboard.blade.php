@php
    $user = auth()->user();
    $twoFactorPending = $user->two_factor_required
        && (! filled($user->two_factor_secret) || ! filled($user->two_factor_confirmed_at));
    $siteName = \App\Models\SystemSetting::valueFor('site_name', 'Adaptive Programming System');
    $role = $user->role->name;
    $dashboardRoute = match ($role) {
        'Administrator' => 'admin.dashboard',
        'Instructor' => 'instructor.dashboard',
        default => 'student.dashboard',
    };
    $navigation = match ($role) {
        'Administrator' => [
            ['label' => 'Overview', 'href' => route($dashboardRoute), 'icon' => 'grid', 'active' => 'admin.dashboard'],
            ['label' => 'Two-factor authentication', 'href' => route('account.two-factor.edit'), 'icon' => 'settings', 'active' => 'account.two-factor.*'],
            ['label' => 'Manage users', 'href' => route('admin.users.index'), 'icon' => 'users', 'active' => 'admin.users.*'],
            ['label' => 'Manage content', 'href' => route('admin.content.index'), 'icon' => 'book', 'active' => ['admin.content.*', 'admin.exercises.*']],
            ['label' => 'Manage assessments', 'href' => route('admin.assessments.index'), 'icon' => 'clipboard', 'active' => 'admin.assessments.*'],
            ['label' => 'Student progress', 'href' => route('admin.progress.index'), 'icon' => 'chart', 'active' => 'admin.progress.*'],
            ['label' => 'Provide feedback', 'href' => route('admin.feedback.index'), 'icon' => 'clipboard', 'active' => 'admin.feedback.*'],
            ['label' => 'System settings', 'href' => route('admin.settings.edit'), 'icon' => 'settings', 'active' => 'admin.settings.*'],
            ['label' => 'System logs', 'href' => route('admin.logs.index'), 'icon' => 'pulse', 'active' => 'admin.logs.*'],
            ['label' => 'View analytics', 'href' => route('admin.reports.index'), 'icon' => 'chart', 'active' => 'admin.reports.*'],
        ],
        'Instructor' => [
            ['label' => 'Overview', 'href' => route($dashboardRoute), 'icon' => 'grid', 'active' => 'instructor.dashboard'],
            ['label' => 'Two-factor authentication', 'href' => route('account.two-factor.edit'), 'icon' => 'settings', 'active' => 'account.two-factor.*'],
            ['label' => 'Manage Content', 'href' => route('instructor.content.index'), 'icon' => 'book', 'active' => ['instructor.content.*', 'instructor.exercises.*']],
            ['label' => 'Monitor Student Progress', 'href' => route('instructor.progress.index'), 'icon' => 'chart', 'active' => 'instructor.progress.*'],
            ['label' => 'View Analytics', 'href' => route('instructor.analytics.index'), 'icon' => 'chart', 'active' => 'instructor.analytics.*'],
            ['label' => 'Manage Assessments', 'href' => route('instructor.assessments.index'), 'icon' => 'clipboard', 'active' => 'instructor.assessments.*'],
            ['label' => 'Provide Feedback', 'href' => route('instructor.feedback.index'), 'icon' => 'clipboard', 'active' => 'instructor.feedback.*'],
            ['label' => 'Profile Info', 'href' => route('instructor.profile.edit'), 'icon' => 'users', 'active' => 'instructor.profile.*'],
        ],
        default => [
            ['label' => 'Overview', 'href' => route($dashboardRoute), 'icon' => 'grid', 'active' => 'student.dashboard'],
            ['label' => 'Two-factor authentication', 'href' => route('account.two-factor.edit'), 'icon' => 'settings', 'active' => 'account.two-factor.*'],
            ['label' => 'Take Assessment', 'href' => route('student.assessments.index'), 'icon' => 'clipboard', 'active' => 'student.assessments.*'],
            ['label' => 'Access Learning Materials', 'href' => route('student.learning.index'), 'icon' => 'book', 'active' => 'student.learning.*'],
            ['label' => 'Solve and Submit Code', 'href' => route('student.exercises.index'), 'icon' => 'clipboard', 'active' => ['student.exercises.*', 'student.submissions.*']],
            ['label' => 'View Progress', 'href' => route('student.progress.index'), 'icon' => 'chart', 'active' => 'student.progress.*'],
            ['label' => 'Receive Feedback', 'href' => route('student.feedback.index'), 'icon' => 'pulse', 'active' => 'student.feedback.*'],
            ['label' => 'Profile Info', 'href' => route('student.profile.edit'), 'icon' => 'users', 'active' => 'student.profile.*'],
        ],
    };
    $initials = collect(explode(' ', $user->name))->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | {{ $siteName }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/adaptive-programming-logo.svg') }}">
    @include('partials.theme-boot')
    @vite('resources/js/app.js')
    <style>
        :root { --ink: #172033; --muted: #718096; --line: #e7e9ee; --paper: #fff; --canvas: #f5f6f8; --accent: #b45309; --accent-bright: #f0a34b; --accent-soft: #fff5e9; --chart-primary: #e7a05a; --chart-secondary: #243950; --chart-neutral: #dce2e9; --sidebar: 266px; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--canvas); color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        img, video, iframe { max-width: 100%; }
        button, a { font: inherit; }
        .sidebar { position: fixed; inset: 0 auto 0 0; z-index: 20; display: flex; width: min(var(--sidebar), calc(100vw - 24px)); flex-direction: column; padding: 26px 18px 20px; background: #121d2e; color: #f9fafb; transition: transform .22s ease; }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 8px 30px; color: #fff; text-decoration: none; }
        .brand-mark { display: block; width: 40px; height: 40px; flex: 0 0 auto; border-radius: 10px; }
        .brand-copy { font-size: 13px; font-weight: 800; line-height: 1.25; letter-spacing: -.01em; }
        .role-label { margin: 0 10px 10px; color: #8290a5; font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        nav { display: grid; gap: 5px; overflow-y: auto; min-height: 0; padding-right: 2px; }
        .nav-link { display: flex; align-items: center; gap: 12px; min-height: 45px; padding: 0 12px; border-radius: 11px; color: #b8c1cf; text-decoration: none; font-size: 13px; font-weight: 650; }
        .nav-link:hover { background: #202e41; color: #fff; }
        .nav-link.active { background: rgba(240, 163, 75, .15); color: #ffd19a; }
        .nav-link svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .avatar { display: grid; width: 36px; height: 36px; flex: 0 0 auto; place-items: center; border-radius: 50%; background: #f7c58a; color: #633b13; font-size: 12px; font-weight: 800; }
        .avatar img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
        .user-photo-cell .avatar { width: 44px; height: 44px; }
        .user-photo-preview { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; }
        .user-modal-content { max-height: min(68vh, 720px); overflow-y: auto; text-align: left; }
        .user-modal-content .form-grid { align-items: start; gap: 20px 16px; }
        .user-password-section { gap: 15px; margin-top: 2px; padding-top: 19px; border-top: 1px solid var(--line); }
        .user-password-heading h3 { margin: 0; font-size: 14px; font-weight: 750; }
        .user-password-heading p { margin: 5px 0 0; color: var(--muted); font-size: 11px; line-height: 1.5; }
        .user-password-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .user-details { display: grid; gap: 0; margin: 0; text-align: left; }
        .user-details > div { display: grid; grid-template-columns: minmax(120px, .7fr) minmax(0, 1.3fr); gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .user-details > div:last-child { border-bottom: 0; }
        .user-details dt { color: var(--muted); font-size: 12px; font-weight: 700; }
        .user-details dd { margin: 0; overflow-wrap: anywhere; color: var(--ink); font-size: 13px; }
        .user-details-photo { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; }
        .app { display: flex; min-height: 100vh; flex-direction: column; margin-left: var(--sidebar); }
        .topbar { display: flex; height: 76px; align-items: center; justify-content: space-between; gap: 12px; padding: 0 clamp(24px, 4vw, 54px); background: #fff; border-bottom: 1px solid var(--line); }
        .topbar-title { display: flex; min-width: 0; flex: 1; align-items: center; gap: 10px; overflow: hidden; font-size: 14px; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
        .topbar-title img { width: 28px; height: 28px; flex: 0 0 28px; border-radius: 7px; }
        .topbar-title span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
        .topbar-actions { display: flex; flex: 0 0 auto; align-items: center; gap: 10px; }
        .topbar-date { padding: 9px 14px; border: 1px solid var(--line); border-radius: 999px; color: #586174; font-size: 12px; font-weight: 650; }
        .account-menu { position: relative; }
        .account-trigger { display: flex; min-height: 42px; align-items: center; gap: 9px; padding: 3px 11px 3px 4px; border: 1px solid var(--line); border-radius: 999px; background: var(--paper); color: var(--ink); cursor: pointer; list-style: none; }
        .account-trigger::-webkit-details-marker { display: none; }
        .account-trigger:hover, .account-menu[open] .account-trigger { border-color: #d0a16d; background: var(--accent-soft); }
        .account-trigger .avatar { width: 32px; height: 32px; }
        .account-trigger-name { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; font-weight: 750; }
        .account-chevron { width: 15px; height: 15px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 2; transition: transform .15s ease; }
        .account-menu[open] .account-chevron { transform: rotate(180deg); }
        .account-dropdown { position: absolute; z-index: 30; top: calc(100% + 8px); right: 0; width: 245px; padding: 8px; border: 1px solid var(--line); border-radius: 13px; background: var(--paper); box-shadow: 0 16px 40px rgba(23, 32, 51, .16); }
        .account-identity { padding: 9px 10px 12px; border-bottom: 1px solid var(--line); font-size: 11px; color: var(--muted); }
        .account-identity strong { display: block; margin-top: 3px; overflow-wrap: anywhere; color: var(--ink); font-size: 13px; }
        .account-identity span { display: block; margin-top: 2px; }
        .account-logout { display: flex; width: 100%; min-height: 40px; align-items: center; gap: 9px; margin-top: 5px; padding: 0 10px; border: 0; border-radius: 8px; background: transparent; color: var(--ink); cursor: pointer; font-size: 12px; font-weight: 700; text-align: left; }
        .account-logout:hover { background: var(--accent-soft); }
        .account-logout svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .menu-button { display: none; padding: 8px; border: 0; background: transparent; cursor: pointer; }
        .menu-button svg { width: 23px; height: 23px; }
        .content { width: min(1360px, calc(100% - 64px)); min-width: 0; flex: 1; margin: 0 auto; padding: 36px 0 64px; }
        .site-footer { padding: 18px 24px; border-top: 1px solid var(--line); color: var(--muted); font-size: 12px; text-align: center; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 32px; }
        .page-heading > * { min-width: 0; }
        .eyebrow { margin: 0 0 8px; color: var(--accent); font-size: 11px; font-weight: 850; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; overflow-wrap: anywhere; font-size: clamp(28px, 4vw, 38px); font-weight: 750; letter-spacing: -.035em; }
        .lead { max-width: 680px; margin: 10px 0 0; color: var(--muted); font-size: 15px; line-height: 1.7; }
        .status-pill { padding: 8px 12px; border: 1px solid #d9ede3; border-radius: 999px; background: #f3fbf7; color: #237a4b; font-size: 12px; font-weight: 750; white-space: nowrap; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 22px; }
        .stat, .panel { min-width: 0; background: var(--paper); border: 1px solid #edf0f3; border-radius: 20px; box-shadow: 0 12px 30px rgba(23, 32, 51, .035); }
        .stat { padding: 21px; }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 700; }
        .stat-value { margin-top: 9px; font-size: 28px; font-weight: 750; letter-spacing: -.03em; }
        .stat-note { margin-top: 6px; color: #9aa2b1; font-size: 11px; }
        .dashboard-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(260px, .8fr); gap: 18px; }
        .panel { padding: 26px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; gap: 10px 16px; flex-wrap: wrap; margin-bottom: 22px; }
        .panel-header > * { min-width: 0; }
        .panel h2 { margin: 0; overflow-wrap: anywhere; font-size: 16px; font-weight: 750; }
        .panel-kicker { color: var(--muted); font-size: 12px; }
        .list { display: grid; }
        .list-item { display: flex; align-items: center; gap: 13px; padding: 14px 0; border-top: 1px solid #f0f1f3; }
        .list-item:first-child { border-top: 0; padding-top: 0; }
        .list-icon { display: grid; width: 38px; height: 38px; flex: 0 0 auto; place-items: center; border-radius: 10px; background: #f3f4f6; color: #596273; font-size: 13px; font-weight: 800; }
        .list-copy { min-width: 0; }
        .list-title { overflow-wrap: anywhere; font-size: 13px; font-weight: 750; }
        .list-subtitle { margin-top: 3px; overflow-wrap: anywhere; color: var(--muted); font-size: 11px; }
        .list-meta { margin-left: auto; color: #9aa2b1; font-size: 11px; white-space: nowrap; }
        .quick-grid { display: grid; gap: 10px; }
        .quick-action { display: block; padding: 14px; border: 1px solid var(--line); border-radius: 10px; color: var(--ink); text-decoration: none; transition: border-color .15s, transform .15s; }
        .quick-action:hover { border-color: #d0a16d; transform: translateY(-1px); }
        .quick-title { font-size: 13px; font-weight: 750; }
        .quick-copy { margin-top: 4px; color: var(--muted); font-size: 11px; line-height: 1.5; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 18px; }
        .filters { display: flex; flex: 1; gap: 10px; }
        .field, .select, .textarea { width: 100%; min-width: 0; max-width: 100%; padding: 10px 12px; border: 1px solid #d8dce3; border-radius: 8px; background: #fff; color: var(--ink); font: inherit; font-size: 13px; }
        .field:focus, .select:focus, .textarea:focus { outline: 3px solid var(--accent-soft); border-color: #c4874d; }
        .textarea { min-height: 130px; resize: vertical; line-height: 1.6; }
        .filters .field { max-width: 310px; }
        .filters .select { max-width: 190px; }
        .button { display: inline-flex; max-width: 100%; min-height: 40px; align-items: center; justify-content: center; gap: 8px; padding: 8px 15px; border: 1px solid transparent; border-radius: 8px; background: var(--ink); color: #fff; text-align: center; text-decoration: none; cursor: pointer; font-size: 12px; font-weight: 750; }
        .button:hover { background: #283349; }
        .button.secondary { border-color: #d8dce3; background: #fff; color: #4b5563; }
        .button.danger { border-color: #f1c8c5; background: #fff; color: #b42318; }
        .button.small { min-height: 32px; padding: 0 10px; }
        .table-wrap { max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .user-photo-cell { width: 1%; white-space: nowrap; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 11px 13px; border-bottom: 1px solid var(--line); color: #8a93a3; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-align: left; text-transform: uppercase; }
        td { padding: 14px 13px; border-bottom: 1px solid #f0f1f3; color: #4b5563; font-size: 12px; vertical-align: middle; }
        tr:last-child td { border-bottom: 0; }
        .cell-title { overflow-wrap: anywhere; color: var(--ink); font-size: 13px; font-weight: 750; }
        .cell-subtitle { margin-top: 3px; overflow-wrap: anywhere; color: #8a93a3; font-size: 11px; }
        .badge { display: inline-flex; padding: 5px 8px; border-radius: 999px; background: #f0f2f5; color: #586174; font-size: 10px; font-weight: 750; text-transform: capitalize; }
        .badge.success { background: #eaf8f0; color: #237a4b; }
        .badge.warning { background: #fff5e7; color: #a85b15; }
        .actions { display: flex; justify-content: flex-end; gap: 7px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .form-group { display: grid; min-width: 0; gap: 7px; }
        .admin-password-field { position: relative; }
        .admin-password-field .field { padding-right: 48px; }
        .admin-password-toggle { position: absolute; top: 50%; right: 7px; display: grid; width: 34px; height: 34px; place-items: center; transform: translateY(-50%); border: 0; border-radius: 7px; background: transparent; color: #667085; cursor: pointer; }
        .admin-password-toggle:hover { background: var(--accent-soft); color: var(--accent); }
        .admin-password-toggle:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .admin-password-toggle svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .admin-password-toggle .eye-closed, .admin-password-toggle[aria-pressed="true"] .eye-open { display: none; }
        .admin-password-toggle[aria-pressed="true"] .eye-closed { display: block; }
        .form-group.full { grid-column: 1 / -1; }
        .form-label { font-size: 12px; font-weight: 750; }
        .form-help { color: var(--muted); font-size: 11px; line-height: 1.5; }
        .form-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 9px; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--line); }
        .checkbox-row { display: flex; align-items: flex-start; gap: 10px; }
        .notice { margin-bottom: 18px; padding: 12px 15px; border: 1px solid #cce7d7; border-radius: 9px; background: #f2fbf6; color: #27724a; overflow-wrap: anywhere; font-size: 12px; }
        .notice.error { border-color: #f2d0cd; background: #fff5f4; color: #a7352d; }
        .notice.warning { border-color: #f2d6ad; background: #fff8ed; color: #8b5411; }
        .sidebar-security-notice { margin: 0 0 16px; padding: 11px 12px; border: 1px solid rgba(255, 209, 154, .3); border-radius: 10px; background: rgba(240, 163, 75, .12); color: #ffd19a; font-size: 11px; font-weight: 650; line-height: 1.55; }
        [data-fullscreen-activity]:fullscreen { width: 100%; height: 100%; overflow: auto; padding: 28px clamp(20px, 5vw, 72px); background: var(--canvas); color: var(--ink); }
        .activity-timer { position: sticky; top: 0; z-index: 5; width: fit-content; margin: -12px 0 18px auto; padding: 10px 14px; border: 1px solid #f0d5a8; border-radius: 10px; background: #fff6e8; color: #814510; font-size: 13px; font-weight: 700; box-shadow: 0 5px 16px rgba(23, 32, 51, .08); }
        .activity-timer strong { margin-left: 7px; font-variant-numeric: tabular-nums; }
        [data-activity-content][hidden], [data-activity-gate][hidden], [data-fullscreen-error][hidden], [data-timer-return][hidden], [data-fullscreen-start][hidden], [data-gate-time][hidden] { display: none !important; }
        .validation { margin: 0 0 18px; padding: 13px 16px 13px 32px; border: 1px solid #f2d0cd; border-radius: 9px; background: #fff5f4; color: #a7352d; font-size: 12px; line-height: 1.7; }
        .empty { padding: 44px 20px; color: var(--muted); text-align: center; }
        .pagination { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-top: 18px; color: var(--muted); font-size: 11px; }
        .pagination-links { display: flex; gap: 6px; }
        .pagination-links a, .pagination-links span { display: grid; min-width: 31px; height: 31px; place-items: center; border: 1px solid var(--line); border-radius: 7px; color: #596273; text-decoration: none; }
        .pagination-links .current { border-color: var(--ink); background: var(--ink); color: #fff; }
        .backdrop { display: none; }
        .overview-hero-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) repeat(2, minmax(0, 1fr)); gap: 18px; margin-bottom: 20px; }
        .overview-feature, .overview-metric { min-height: 230px; border-radius: 22px; padding: 27px; }
        .overview-feature { position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: flex-start; background: #17283a; color: #fff; }
        .overview-feature::after { content: ''; position: absolute; right: -45px; bottom: -80px; width: 210px; height: 210px; border: 24px solid rgba(240, 163, 75, .28); border-radius: 50%; }
        .overview-feature::before { content: ''; position: absolute; right: 65px; bottom: -90px; width: 140px; height: 140px; border: 18px solid rgba(240, 163, 75, .12); border-radius: 50%; }
        .feature-kicker { display: inline-flex; padding: 7px 11px; border-radius: 999px; background: rgba(255, 255, 255, .12); color: #ffcf95; font-size: 11px; font-weight: 800; }
        .overview-feature h2 { position: relative; z-index: 1; max-width: 350px; margin: 24px 0 9px; font-size: clamp(21px, 2vw, 27px); line-height: 1.22; letter-spacing: -.03em; }
        .overview-feature p { position: relative; z-index: 1; max-width: 320px; margin: 0; color: #c4ceda; font-size: 12px; line-height: 1.6; }
        .feature-link { position: relative; z-index: 1; display: inline-flex; margin-top: auto; padding-top: 15px; color: #ffbd72; text-decoration: none; font-size: 12px; font-weight: 800; }
        .feature-link:hover { color: #fff; }
        .overview-metric { display: flex; flex-direction: column; background: #fff; border: 1px solid #edf0f3; box-shadow: 0 12px 30px rgba(23, 32, 51, .035); }
        .metric-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #647086; font-size: 12px; font-weight: 650; }
        .metric-icon { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 10px; background: #fff3e5; color: #a85516; font-size: 16px; font-weight: 800; }
        .metric-value { margin-top: 20px; font-size: clamp(31px, 3vw, 38px); font-weight: 800; letter-spacing: -.05em; }
        .metric-note { margin-top: 3px; color: var(--muted); font-size: 12px; }
        .metric-link { margin-top: auto; padding-top: 18px; color: #a85516; text-decoration: none; font-size: 12px; font-weight: 800; }
        .metric-link:hover { color: #17283a; }
        .metric-spark { display: flex; height: 43px; align-items: end; gap: 6px; margin-top: auto; padding-top: 14px; }
        .metric-spark span { flex: 1; height: var(--height); border-radius: 5px 5px 0 0; background: #f5bd7a; }
        .metric-spark span:nth-child(even) { background: #fbe3c6; }
        .overview-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(280px, .75fr); gap: 20px; margin-bottom: 20px; }
        .overview-grid .panel { min-width: 0; }
        .chart-heading { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 24px; }
        .chart-heading h2 { margin: 0 0 5px; font-size: 17px; }
        .chart-heading p { margin: 0; color: var(--muted); font-size: 12px; }
        .chart-total { color: var(--ink); font-size: 13px; font-weight: 800; white-space: nowrap; }
        .bar-chart { display: flex; height: 228px; align-items: stretch; gap: clamp(8px, 2vw, 22px); padding: 8px 8px 0; border-bottom: 1px solid #e7e9ee; background: repeating-linear-gradient(to top, transparent 0, transparent 50px, #edf0f3 51px); }
        .bar-column { display: flex; flex: 1; min-width: 0; flex-direction: column; align-items: center; justify-content: end; }
        .bar-track { display: flex; width: min(100%, 42px); height: 155px; align-items: end; }
        .bar-fill { display: block; width: 100%; height: var(--height); border-radius: 7px 7px 0 0; background: #e4a25d; }
        .bar-column:nth-child(even) .bar-fill { background: #243950; }
        .bar-value { margin-bottom: 7px; color: #627086; font-size: 11px; font-weight: 750; }
        .bar-label { margin-top: 9px; color: #758196; font-size: 11px; }
        .donut-wrap { display: grid; min-height: 210px; place-items: center; }
        .donut { display: grid; width: 176px; height: 176px; place-items: center; border-radius: 50%; background: var(--donut); }
        .donut-inner { display: flex; width: 126px; height: 126px; flex-direction: column; align-items: center; justify-content: center; border-radius: 50%; background: #fff; text-align: center; }
        .donut-inner strong { font-size: 28px; letter-spacing: -.04em; }
        .donut-inner span { color: var(--muted); font-size: 10px; }
        .legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px 17px; margin-top: 4px; color: #657184; font-size: 11px; }
        .legend-item { display: inline-flex; align-items: center; gap: 6px; }
        .legend-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--dot); }
        .overview-bottom { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); gap: 20px; }
        .overview-bottom .panel { min-width: 0; }
        .overview-bottom .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .overview-bottom .list-subtitle { overflow-wrap: anywhere; line-height: 1.5; }
        @media (max-width: 1100px) { .overview-hero-grid { grid-template-columns: 1fr 1fr; } .overview-feature { grid-column: 1 / -1; } }
        @media (max-width: 850px) {
            .sidebar { transform: translateX(-100%); }
            body.menu-open .sidebar { transform: translateX(0); box-shadow: 20px 0 50px rgba(20, 28, 45, .14); }
            body.menu-open .backdrop { position: fixed; inset: 0; z-index: 15; display: block; background: rgba(17, 24, 39, .32); }
            .app { margin-left: 0; }
            .menu-button { display: block; }
            .topbar { gap: 10px; padding: 0 20px; }
            .content { width: min(100% - 32px, 700px); padding-top: 32px; }
            .stats, .dashboard-grid, .overview-grid, .overview-bottom { grid-template-columns: 1fr; }
            .overview-hero-grid { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: auto; }
            .toolbar, .filters { align-items: stretch; flex-direction: column; }
            .filters .field, .filters .select { max-width: none; }
            .page-heading { align-items: flex-start; flex-direction: column; }
        }
        @media (max-width: 560px) {
            .overview-hero-grid, .overview-bottom .quick-grid { grid-template-columns: 1fr; }
            .overview-feature { grid-column: auto; }
            .overview-feature, .overview-metric { min-height: 205px; }
            .topbar { height: 64px; padding: 0 14px; }
            .topbar-actions { gap: 6px; }
            .topbar-date, .account-trigger-name { display: none; }
            .account-trigger { padding-right: 7px; }
            .content { width: calc(100% - 28px); padding-top: 24px; }
            .panel { padding: 18px; }
            .page-heading { gap: 14px; margin-bottom: 24px; }
            .list-item { flex-wrap: wrap; }
            .list-item > .list-copy { flex: 1 1 165px; }
            .list-item > .button, .list-item > form { margin-left: auto; }
            .code-history-heading { flex-wrap: wrap; }
            .toolbar .filters .button { align-self: flex-start; }
            [data-fullscreen-activity]:fullscreen { padding: 16px 14px; }
        }
        @media (max-width: 380px) { .topbar-title span { display: none; } .account-dropdown { width: min(245px, calc(100vw - 28px)); } }
        html[data-theme="dark"] { color-scheme: dark; --ink: #eff4fb; --muted: #adbbcc; --line: #34445a; --paper: #1b293b; --canvas: #101a28; --accent: #f0b36b; --accent-soft: #3a2b1f; --chart-primary: #f0b36b; --chart-secondary: #8ba3bd; --chart-neutral: #405169; }
        html[data-theme="dark"] .sidebar { background: #0c1522; }
        html[data-theme="dark"] .topbar { background: #172437; }
        html[data-theme="dark"] .topbar-date { color: #c8d4e2; }
        html[data-theme="dark"] .account-trigger, html[data-theme="dark"] .account-dropdown { border-color: #45546b; background: #233348; color: #e4ebf4; }
        html[data-theme="dark"] .account-trigger:hover, html[data-theme="dark"] .account-menu[open] .account-trigger, html[data-theme="dark"] .account-logout:hover { border-color: #f0a34b; background: #3a2b1f; }
        html[data-theme="dark"] .account-identity { border-color: #45546b; }
        html[data-theme="dark"] .menu-button { color: #eff4fb; }
        html[data-theme="dark"] .theme-toggle { border-color: #45546b; background: #233348; color: #e4ebf4; }
        html[data-theme="dark"] .theme-toggle:hover { border-color: #f0a34b; color: #ffc182; }
        html[data-theme="dark"] .status-pill { border-color: #3e6756; background: #213d36; color: #a5ebbe; }
        html[data-theme="dark"] .stat, html[data-theme="dark"] .panel, html[data-theme="dark"] .overview-metric { border-color: #34445a; background: var(--paper); box-shadow: 0 12px 30px rgba(0, 0, 0, .12); }
        html[data-theme="dark"] .stat-note, html[data-theme="dark"] .list-meta, html[data-theme="dark"] .cell-subtitle { color: #a6b5c7; }
        html[data-theme="dark"] .list-item { border-top-color: #34445a; }
        html[data-theme="dark"] .list-icon { background: #2b3c52; color: #d8e4f1; }
        html[data-theme="dark"] .quick-action { border-color: #405169; background: #223247; }
        html[data-theme="dark"] .quick-action:hover { border-color: #d9974e; background: #2c3c51; }
        html[data-theme="dark"] .field, html[data-theme="dark"] .select, html[data-theme="dark"] .textarea { border-color: #45546b; background: #142235; color: #eff4fb; }
        html[data-theme="dark"] .field::placeholder, html[data-theme="dark"] .textarea::placeholder { color: #a0aec1; }
        html[data-theme="dark"] .button { background: #e6a456; color: #172033; }
        html[data-theme="dark"] .button:hover { background: #f4ba75; color: #172033; }
        html[data-theme="dark"] .button.secondary { border-color: #516178; background: #26374d; color: #e3ebf5; }
        html[data-theme="dark"] .button.secondary:hover { background: #34465d; }
        html[data-theme="dark"] .button.danger { border-color: #85545a; background: #3c252e; color: #ffc1bb; }
        html[data-theme="dark"] .button.danger:hover { background: #51303a; }
        html[data-theme="dark"] th, html[data-theme="dark"] td { border-bottom-color: #34445a; }
        html[data-theme="dark"] th { color: #a7b7ca; }
        html[data-theme="dark"] td { color: #d3dfec; }
        html[data-theme="dark"] td a, html[data-theme="dark"] .list-subtitle a { color: #f0b36b; }
        html[data-theme="dark"] .badge { background: #304158; color: #d7e2ee; }
        html[data-theme="dark"] .badge.success { background: #214638; color: #a9ecc2; }
        html[data-theme="dark"] .badge.warning { background: #4b3926; color: #ffd194; }
        html[data-theme="dark"] .form-actions { border-top-color: #34445a; }
        html[data-theme="dark"] .notice { border-color: #3e6756; background: #213d36; color: #b1ecc6; }
        html[data-theme="dark"] .notice.error, html[data-theme="dark"] .validation { border-color: #85545a; background: #3c252e; color: #ffc1bb; }
        html[data-theme="dark"] .notice.warning { border-color: #795a34; background: #3a3023; color: #ffd194; }
        html[data-theme="dark"] .sidebar-security-notice { border-color: rgba(255, 209, 154, .25); background: rgba(240, 163, 75, .1); color: #ffd19a; }
        html[data-theme="dark"] .pagination-links a, html[data-theme="dark"] .pagination-links span { color: #d3dfec; }
        html[data-theme="dark"] .pagination-links .current { border-color: #e6a456; background: #e6a456; color: #172033; }
        html[data-theme="dark"] .overview-feature { background: #22364d; }
        html[data-theme="dark"] .metric-top, html[data-theme="dark"] .bar-value, html[data-theme="dark"] .bar-label, html[data-theme="dark"] .legend { color: #b5c2d2; }
        html[data-theme="dark"] .metric-icon { background: #463522; color: #ffc182; }
        html[data-theme="dark"] .metric-link { color: #f0b36b; }
        html[data-theme="dark"] .metric-link:hover { color: #ffd19b; }
        html[data-theme="dark"] .bar-chart { border-bottom-color: #405169; background: repeating-linear-gradient(to top, transparent 0, transparent 50px, #34445a 51px); }
        html[data-theme="dark"] .bar-column:nth-child(even) .bar-fill { background: #8ba3bd; }
        html[data-theme="dark"] .donut-inner { background: var(--paper); }
        @media (max-width: 700px) { .topbar-date { display: none; } }
        @media (max-width: 700px) { .user-modal-content .form-grid, .user-password-grid { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 700px) {
            .table-wrap { overflow: visible; }
            .responsive-card-table, .responsive-card-table tbody { display: block; width: 100%; }
            .responsive-card-table thead { display: none; }
            .responsive-card-table tbody { display: grid; gap: 12px; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0; margin: 0; padding: 12px 14px; border: 1px solid var(--line); border-radius: 13px; background: var(--paper); }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td { display: grid; grid-template-columns: minmax(84px, .65fr) minmax(0, 1.35fr); align-items: start; gap: 12px; min-width: 0; padding: 9px 0; border-bottom: 1px solid var(--line); font-size: 12px; overflow-wrap: anywhere; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td:last-child { border-bottom: 0; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td::before { content: attr(data-label); color: var(--muted); font-size: 10px; font-weight: 750; line-height: 1.5; text-transform: uppercase; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td.responsive-table-actions { display: block; padding-top: 12px; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td.responsive-table-actions::before { display: block; margin-bottom: 8px; }
            .responsive-card-table tbody tr:not(.responsive-table-empty) td .actions { justify-content: flex-start; flex-wrap: wrap; }
            .responsive-card-table tbody tr.responsive-table-empty { display: block; }
            .responsive-card-table tbody tr.responsive-table-empty td { display: block; padding: 12px; }
            .user-management-table, .user-management-table tbody { display: block; width: 100%; }
            .user-management-table thead { display: none; }
            .user-management-table tbody { display: grid; gap: 12px; }
            .user-management-table .user-management-row { display: grid; grid-template-columns: 44px minmax(0, 1fr); align-items: center; gap: 8px 12px; margin: 0; padding: 14px; border: 1px solid var(--line); border-radius: 13px; background: var(--paper); }
            .user-management-table .user-management-row td { min-width: 0; padding: 0; border: 0; text-align: left; }
            .user-management-table .user-photo-cell { grid-column: 1; grid-row: 1 / span 3; width: auto; white-space: normal; }
            .user-management-table .user-photo-cell .avatar { width: 42px; height: 42px; }
            .user-management-table .user-info-cell { grid-column: 2; grid-row: 1; }
            .user-management-table .user-info-cell .cell-title { font-size: 13px; line-height: 1.35; word-break: normal; }
            .user-management-table .user-info-cell .cell-subtitle { margin-top: 3px; font-size: 11px; overflow-wrap: anywhere; }
            .user-management-table .user-role-cell, .user-management-table .user-joined-cell { grid-column: 2; display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 11px; }
            .user-management-table .user-role-cell { grid-row: 2; }
            .user-management-table .user-joined-cell { grid-row: 3; }
            .user-management-table .user-role-cell::before, .user-management-table .user-joined-cell::before { content: attr(data-label); min-width: 38px; color: var(--muted); font-size: 10px; font-weight: 750; text-transform: uppercase; }
            .user-management-table .user-actions-cell { grid-column: 1 / -1; grid-row: 4; padding-top: 10px !important; border-top: 1px solid var(--line) !important; }
            .user-management-table .user-actions-cell .actions { justify-content: flex-start; flex-wrap: wrap; }
            .user-management-table .user-empty-row { display: block; }
            .user-management-table .user-empty-row td { display: block; padding: 12px; }
        }
        @media (max-width: 560px) { .topbar-actions .theme-toggle span { display: none; } .topbar-title { min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; } }
        .student-code-editor { display: none; width: 100%; min-width: 0; min-height: 420px; border: 1px solid #d8dce3; border-radius: 8px; overflow: hidden; }
        .student-code-editor.is-ready { display: block; }
        .student-code-fallback { min-height: 340px; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; tab-size: 4; }
        .student-code-fallback[hidden], [data-code-error][hidden] { display: none !important; }
        .submitted-code { overflow-x: auto; border: 1px solid #d8dce3; border-radius: 8px; background: #f8fafc; }
        .code-history-item { min-width: 0; padding: 16px 0; border-top: 1px solid var(--line); }
        .code-history-item:first-child { padding-top: 0; border-top: 0; }
        .code-history-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .code-history-code { max-height: 240px; overflow: auto; margin: 12px 0 0; padding: 12px 14px; border: 1px solid #d8dce3; border-radius: 8px; background: #f8fafc; color: var(--ink); font: 12px/1.7 ui-monospace, SFMono-Regular, Consolas, monospace; tab-size: 4; }
        .code-history-item.is-previous .code-history-code { background: #f0f2f5; color: #586174; }
        .submitted-code ol { margin: 0; padding: 12px 0; list-style: none; counter-reset: line; }
        .submitted-code li { display: flex; min-height: 24px; align-items: baseline; gap: 12px; padding: 0 12px; }
        .submitted-code li.has-error { background: #fff1ed; }
        .submitted-code li.is-selected { background: #ffe2c7; }
        .submitted-code .code-line-number { width: 3em; flex: 0 0 3em; color: #7b8797; text-align: right; user-select: none; }
        .submitted-code code { flex: 1; white-space: pre; font: 12px/1.7 ui-monospace, SFMono-Regular, Consolas, monospace; }
        .code-line-pick { flex: 0 0 auto; border: 0; background: transparent; color: #a85b15; cursor: pointer; font-size: 11px; }
        .code-line-pick input { accent-color: #b45309; cursor: pointer; vertical-align: middle; }
        .code-line-pick:hover { text-decoration: underline; }
        html[data-theme="dark"] .student-code-editor { border-color: #45546b; }
        html[data-theme="dark"] .submitted-code { border-color: #45546b; background: #142235; }
        html[data-theme="dark"] .code-history-code { border-color: #45546b; background: #142235; }
        html[data-theme="dark"] .code-history-item.is-previous .code-history-code { background: #1c2b3e; color: #a8b7ca; }
        html[data-theme="dark"] .submitted-code li.has-error { background: #4b3926; }
        html[data-theme="dark"] .submitted-code li.is-selected { background: #694527; }
        @media (max-width: 700px) { .student-code-editor { min-height: 320px; } }
    </style>
</head>
<body data-authenticated-page>
    <div class="backdrop" onclick="document.body.classList.remove('menu-open')"></div>
    <aside class="sidebar" aria-label="Main navigation">
        <a class="brand" href="{{ route($dashboardRoute) }}" @if ($twoFactorPending) data-two-factor-sidebar-link data-setup-url="{{ route('account.two-factor.edit') }}" aria-haspopup="dialog" @endif><img class="brand-mark" src="{{ asset('images/adaptive-programming-logo.svg') }}" alt="" width="40" height="40"><span class="brand-copy">{{ $siteName }}</span></a>
        <p class="role-label">{{ $role }} menu</p>
        @if ($twoFactorPending)
            <div class="sidebar-security-notice" role="status">Security setup required: complete two-factor authentication before using the sidebar menu.</div>
        @endif
        <nav>
            @foreach ($navigation as $index => $item)
                <a class="nav-link {{ isset($item['active']) ? (request()->routeIs(...(array) $item['active']) ? 'active' : '') : ($index === 0 ? 'active' : '') }}" href="{{ $item['href'] }}" @if ($twoFactorPending) data-two-factor-sidebar-link data-setup-url="{{ route('account.two-factor.edit') }}" aria-haspopup="dialog" @endif>
                    @switch($item['icon'])
                        @case('grid') <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> @break
                        @case('users') <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> @break
                        @case('book') <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg> @break
                        @case('clipboard') <svg viewBox="0 0 24 24"><path d="M9 5H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg> @break
                        @case('chart') <svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m7 16 4-5 3 3 5-7"/></svg> @break
                        @case('pulse') <svg viewBox="0 0 24 24"><path d="M3 12h4l2-7 4 14 2-7h6"/></svg> @break
                        @default <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.37.26.59.67.6 1.1v.1h1v4h-.1a1.7 1.7 0 0 0-1.5.8z"/></svg>
                    @endswitch
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </aside>
    <div class="app">
        <header class="topbar">
            <button class="menu-button" type="button" aria-label="Open menu" onclick="document.body.classList.toggle('menu-open')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <div class="topbar-title"><img src="{{ asset('images/adaptive-programming-logo.svg') }}" alt="" width="28" height="28"><span>@yield('title')</span></div>
            <div class="topbar-actions">
                @include('partials.theme-toggle')
                <div class="topbar-date">{{ now()->format('l, F j') }}</div>
                <details class="account-menu" data-account-menu>
                    <summary class="account-trigger" aria-label="Account menu for {{ $user->name }}">
                        <span class="avatar">@if ($user->profile_photo_path)<img src="{{ route('users.photo', $user) }}" alt="">@else{{ $initials }}@endif</span>
                        <span class="account-trigger-name">{{ $user->name }}</span>
                        <svg class="account-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="account-dropdown">
                        <div class="account-identity">Signed in as<strong>{{ $user->name }}</strong><span>{{ $role }}</span></div>
                        <form method="POST" action="{{ route('logout') }}" data-confirm-logout>
                            @csrf
                            <button class="account-logout" type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>Log out</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
        <main class="content">@yield('content')</main>
        @include('partials.site-footer')
    </div>
    @if (session('two_factor_required_notice'))
        <div hidden data-two-factor-required-alert data-message="{{ session('two_factor_required_notice') }}" data-setup-url="{{ route('account.two-factor.edit') }}"></div>
    @endif
</body>
</html>
