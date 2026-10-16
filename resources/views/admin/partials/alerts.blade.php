@if (session('success')) <div class="notice">{{ session('success') }}</div> @endif
@if (session('error')) <div class="notice error">{{ session('error') }}</div> @endif
@if ($errors->any())
    <ul class="validation">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
@endif
