@extends('public.layouts.page')

@section('page:title','Downloads')

@section('body:class','page download')

@section('page:content')
    <h1>Downloads</h1>
    <div class="alert alert-danger">{{ $message }}</div>
    <a class="btn btn-primary" href="{{ route('public::downloads') }}">Zurück zu den Downloads</a>
@stop
