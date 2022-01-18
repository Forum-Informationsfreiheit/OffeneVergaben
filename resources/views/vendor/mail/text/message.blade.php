@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }}
        @endcomponent
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            [offenevergaben.at]({{ url('/') }}) ist ein Projekt des Forum Informationsfreiheit und wird durch die [Internet Foundation Austria (IPA) / netidee.at](https://netidee.at) gefördert.
        @endcomponent
    @endslot
@endcomponent
