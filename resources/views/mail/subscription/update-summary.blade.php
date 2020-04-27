@component('mail::message')
# Neuigkeiten zu Ihren Abos

Klicken Sie auf den Titel um zu den jeweiligen Ergebnissen auf offenevergaben.at zu gelangen.

@component('mail::table',[ 'tableClass' => 'daily-update-summary-table' ])
| Abo | neu  | abbestellen |
|:----|:----:| :----------:|
@foreach($subscriptions as $subscription)
    |[{{ $subscription->title }}]({!! route('public::auftraege').'?'.$subscription->query  !!})|{{ $updateInfo[$subscription->id]['new_datasets_count'] }}|[abbestellen]({{ url('/') }})
@endforeach
@endcomponent

Danke für Ihr Interesse,<br>
{{ config('app.name') }}
@endcomponent
