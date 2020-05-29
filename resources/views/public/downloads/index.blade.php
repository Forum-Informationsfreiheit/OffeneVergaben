@extends('public.layouts.page')

@section('page:title','Downloads')

@section('body:class','page downloads')

@section('page:header')
    <section class="header lg">
        <h1>Downloads</h1>
    </section>
@stop

@section('page:content')
    <table class="table table-striped table-borderless">
        <tr>
            <td>Beschreibung</td>
            <td>Dateityp</td>
            <td>Größe</td>
            <td>Zuletzt aktualisiert</td>
            <td>Download</td>
        </tr>
        <tr>
            <td>
                {{-- nachdem wir aktuell nur 1 file zu handlen haben ist die meiset info erstmal fix hier --}}
                <strong>BULK Kerndaten</strong> <small>tägl. aktualisiert</small>
                <br><small>Encoding: UTF-8</small>
                <br><small>Trennzeichen: ,</small>
            </td>
            <td><strong>CSV</strong> <small>(ZIP komprimiert)</small></td>
            <td>{{ number_format($files['kerndaten_dailydump']->filesize / (1024 * 1024),2,',','.') }} <small>MB</small></td>
            <td>{{ $files['kerndaten_dailydump']->timestamp->format('d.m.Y') }} <small>{{ $files['kerndaten_dailydump']->timestamp->format('H:i') }}</small></td>
            <td><a href="{{ $files['kerndaten_dailydump']->url }}" class="btn btn-primary btn-sm">Download</a></td>
        </tr>
    </table>
@stop