@extends('public.layouts.page')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1>Abonnements beenden</h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <p>Bitte bestätigen Sie die Kündigung Ihrer Abonnements:</p>
            <ul style="list-style: disc">
                @foreach($subscriptions as $subscription)
                    @if(!$subscription->verified_at)
                        @continue
                    @endif
                    <li><strong>{{ $subscription->title }}</strong>, aktiv seit {{ $subscription->verified_at->format('d.m.Y') }}</li>
                @endforeach
            </ul>
            <p>Sie werden nach der Kündigung keine weiteren Benachrichtigungen mehr erhalten.</p>
            <a class="btn btn-primary" href="{{ $subscriber->unsubscribeAllUrl }}">
                Alle Abos beenden
            </a>
        </div>
    </div>
@stop