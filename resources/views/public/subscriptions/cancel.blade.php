@extends('public.layouts.page')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1>Abonnement beenden</h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <p>Bitte bestätigen Sie die Kündigung Ihres Abonnements<br><strong>{{ $subscription->title }}</strong> (aktiv seit {{ $subscription->verified_at->format('d.m.Y') }})<br>Sie werden nach der Kündigung von uns keine weiteren Benachrichtigungen mehr zu diesem Thema erhalten.</p>
            <p></p>
            <a class="btn btn-primary" href="{{ $subscription->unsubscribeUrl }}">
                Abo beenden
            </a>
        </div>
    </div>
@stop