@component('mail::message')
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    switch ($level) {
        case 'success':
        case 'error':
            $color = $level;
            break;
        default:
            $color = 'primary';
    }
?>
@component('mail::button', ['url' => $actionUrl, 'color' => $color])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
{{ config('app.name') }}
@endif

PS: Finden Sie diesen Service nützlich?<br>
Mit einer [Spende an das Forum Informationsfreiheit](https://www.informationsfreiheit.at/spenden) können Sie helfen, den Betrieb von {{ config('app.name') }} sicherzustellen.

{{-- Subcopy --}}
@isset($actionText)
@slot('subcopy')
@lang(
    "If you’re having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser: [:actionURL](:actionURL)',
    [
        'actionText' => $actionText,
        'actionURL' => $actionUrl,
    ]
)
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
[{{ config('app.name') }}]({{ url('/') }}) ist ein zivilgesellschaftliches Projekt des Forum Informationsfreiheit, ZVR 796723786, Schuhmeierplatz 9/25, 1160 Wien. Email: [office@informationsfreiheit.at](mailto:office@informationsfreiheit.at)<br><br>
Ermöglicht wurde die Umsetzung durch die [Netidee.at](https://netidee.at), eine Förderung der Internet Privatstiftung Austria (IPA).
@endslot
@endcomponent