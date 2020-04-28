@component('mail::message')
# Neuigkeiten zu Ihren Email-Benachrichtigungen

Klicken Sie auf den Titel des Abos, um zu den jeweiligen Suchergebnissen auf [{{ config('app.name') }}]({{ url('/') }}) zu gelangen.
Klicken Sie „abbestellen“, um keine Benachrichtigungen mehr zu erhalten.

@component('mail::table',[ 'tableClass' => 'daily-update-summary-table' ])
| Abo | neu  | abbestellen |
|:----|:----:| :----------:|
@foreach($subscriptions as $subscription)
    |[{{ $subscription->title }}]({!! route('public::auftraege').'?'.$subscription->query  !!})|{{ $updateInfo[$subscription->id]['new_datasets_count'] }}|[abbestellen]({{ $subscription->cancelUrl }})
@endforeach
@endcomponent

Finden Sie diesen Service nützlich?<br>
Mit einer Spende an das Forum Informationsfreiheit [https://www.informationsfreiheit.at/spenden/] können Sie helfen, den Betrieb von {{ config('app.name') }} sicherzustellen.

Ihr Team von<br>
{{ config('app.name') }}

@slot('subcopy')
Wenn Sie alle Benachrichtigungen beenden möchten klicken Sie auf folgenden Link<br>[alle Benachrichtigungen beenden]({{ $subscriber->cancelAllSubscriptionsUrl }})
@endslot

@slot('footer')
[{{ config('app.name') }}]({{ url('/') }}) ist ein zivilgesellschaftliches Projekt des Forum Informationsfreiheit, ZVR 796723786, Kirchberggasse 7/4A, 1070 Wien. Email: [office@informationsfreiheit.at](mailto:office@informationsfreiheit.at)<br><br>
Ermöglicht wurde die Umsetzung durch die [Netidee.at](https://netidee.at), eine Förderung der Internet Privatstiftung Austria (IPA).
@endslot

@endcomponent
