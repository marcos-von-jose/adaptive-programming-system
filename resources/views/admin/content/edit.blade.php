@php($managementPrefix = request()->routeIs('instructor.*') ? 'instructor' : 'admin')
@extends('layouts.dashboard')
@section('title', 'Edit Content')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Content library</p><h1>Edit learning content</h1><p class="lead">Update the lesson and its publication status.</p></div></div>
    @include('admin.partials.alerts')
    <section class="panel"><form method="POST" action="{{ route($managementPrefix.'.content.update', $content) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('admin.content._form')</form></section>
@endsection
