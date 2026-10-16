<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | {{ \App\Models\SystemSetting::valueFor('site_name', 'Adaptive Programming System') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/adaptive-programming-logo.svg') }}">
    @include('partials.theme-boot')
    @vite(['resources/css/auth.css', 'resources/js/auth.js'])
</head>
<body>
    <div class="container-fluid auth-page px-0">
        <div class="row g-0 min-vh-100">
            <aside class="col-lg-5 col-xl-6 auth-showcase d-none d-lg-flex align-items-center justify-content-center" aria-hidden="true">
                <div class="showcase-card">
                    <img class="showcase-logo" src="{{ asset('images/adaptive-programming-logo.svg') }}" alt="" width="144" height="144">
                    <div class="showcase-path"><span></span><i></i><span></span><i></i><span></span><i></i><span></span></div>
                </div>
            </aside>
            <div class="col-12 col-lg-7 col-xl-6 auth-form-side d-flex flex-column align-items-center justify-content-center py-4 px-3 px-sm-4">
                <div class="auth-card-wrap">
                    <main class="auth-card">
                        <img class="auth-logo" src="{{ asset('images/adaptive-programming-logo.svg') }}" alt="Adaptive Programming System logo" width="76" height="76">
                        @yield('content')
                    </main>
                </div>
                @include('partials.site-footer')
            </div>
        </div>
    </div>
</body>
</html>
