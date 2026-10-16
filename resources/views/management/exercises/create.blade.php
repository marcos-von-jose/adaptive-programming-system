@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Create Exercise')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Programming practice</p><h1>Create coding exercise</h1><p class="lead">Write a clear task and optional starter code.</p></div></div>
@include('admin.partials.alerts')
<section class="panel"><form method="POST" action="{{ route($managementPrefix.'.exercises.store') }}">@csrf @include('management.exercises._form')</form></section>
@endsection
