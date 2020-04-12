@extends('public.layouts.page')

@section('body:class','page download')

@section('page:header')
    <section class="header lg">
        <h1>Downloads</h1>
    </section>
@stop

@section('page:content')
    <h2 style="text-align: center;">
        Datei: {{ $title }}
    </h2>
    <p style="text-align: center">
        Klicken Sie auf den folgenden Link um den Download zu starten. Der Link ist eine Stunde lang gültig.
        <br>
        <a rel="nofollow" class="btn btn-primary btn-lg mt-3" href="{{ $link }}">Download</a>
        <br style="height: 1px;">
        <small style="color: darkgrey">{{ $link }}</small>
    </p>
@stop

@section('body:append')
@stop