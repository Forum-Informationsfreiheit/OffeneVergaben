@extends('public.layouts.page')

@section('page:title', $success ? 'Benachrichtigung' : 'Fehler')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1>Benachrichtigungen</h1>
            @foreach($messages as $message)
                <div class="alert alert-{{ $success ? 'success' : 'danger' }}">{{ $message }}</div>
            @endforeach
            <a class="btn btn-primary" href="{{ route('public::auftraege') }}">Zu den Aufträgen</a>
        </div>
    </div>
@stop
