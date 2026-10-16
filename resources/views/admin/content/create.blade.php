@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Add Content')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Content library</p><h1>Create learning content</h1><p class="lead">Add a focused lesson and connect it to a competency.</p></div></div>
    @include('admin.partials.alerts')
    <section class="panel"><form method="POST" action="{{ route($managementPrefix.'.content.store') }}" enctype="multipart/form-data">@csrf @include('admin.content._form')</form></section>
@endsection
