@component('mail::message')
# Neuigkeiten zu Ihren Abos

Klicken Sie auf den Titel um zu den jeweiligen Ergebnissen auf offenevergaben.at zu gelangen.

@component('mail::table',[ 'tableClass' => 'daily-update-summary-table' ])
| Abo | neu  | abbestellen |
|:----|:----:| :----------:|
@foreach($subscriptions as $subscription)
    |[{{ $subscription->title }}]({!! route('public::auftraege').'?'.$subscription->query  !!})|{{ $updateInfo[$subscription->id]['new_datasets_count'] }}|[abbestellen]({{ $subscription->cancelUrl }})
@endforeach
@endcomponent

Danke für Ihr Interesse,<br>
{{ config('app.name') }}

@slot('footer')
[alle Abonnements beenden]({{ $subscriber->cancelAllSubscriptionsUrl }})<br><br>
[offenevergaben.at]({{ url('/') }}) ist ein Projekt des Forum Informationsfreiheit und wird durch die [Internet Foundation Austria (IPA) / netidee.at](https://netidee.at) gefördert.
@endslot

@endcomponent
