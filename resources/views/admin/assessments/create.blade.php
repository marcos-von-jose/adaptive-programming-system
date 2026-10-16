@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Create Assessment')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Learning evaluation</p><h1>Create assessment</h1><p class="lead">Start with the assessment details, then add questions.</p></div></div>
@include('admin.partials.alerts')
<section class="panel"><form method="POST" action="{{ route($managementPrefix.'.assessments.store') }}">@csrf @include('admin.assessments._form')</form></section>
@endsection
