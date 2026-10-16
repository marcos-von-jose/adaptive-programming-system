@extends('layouts.dashboard')
@section('title', 'System Settings')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Configuration</p><h1>System settings</h1><p class="lead">Control general platform information and registration behavior.</p></div></div>
    @include('admin.partials.alerts')
    <section class="panel"><form method="POST" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group"><label class="form-label" for="site_name">System name</label><input class="field" id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required></div>
            <div class="form-group"><label class="form-label" for="support_email">Support email</label><input class="field" id="support_email" name="support_email" type="email" value="{{ old('support_email', $settings['support_email']) }}" required></div>
            <div class="form-group"><label class="form-label" for="default_language">Default language</label><select class="select" id="default_language" name="default_language"><option value="en" selected>English</option></select></div>
            <div class="form-group"><label class="form-label" for="items_per_page">Items per page</label><input class="field" id="items_per_page" name="items_per_page" type="number" min="5" max="100" value="{{ old('items_per_page', $settings['items_per_page']) }}" required></div>
            <div class="form-group full"><label class="checkbox-row"><input name="allow_registration" type="checkbox" value="1" @checked(old('allow_registration', $settings['allow_registration']) === '1')><span><strong class="form-label">Allow student registration</strong><span class="form-help" style="display:block;margin-top:4px">When disabled, the public registration page is unavailable. Administrators can still create accounts.</span></span></label></div>
        </div>
        <div class="form-actions"><button class="button" type="submit">Save settings</button></div>
    </form></section>
@endsection
