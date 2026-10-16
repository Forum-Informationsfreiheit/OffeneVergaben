{{--
    Donation call-out on the website: homepage below the search field, /aufträge between the filter and the results,
    and small on the page of a single dataset (@include('public.partials.donation', ['channel' => 'dataset'])).
    The text is in partials/donation-text.blade.php (shared with the emails), the styles in sass/app/_donation.scss.
--}}
@php($channel = $channel ?? 'web')
<aside class="donation-callout{{ $channel === 'dataset' ? ' donation-callout-small' : '' }}" aria-label="Spenden">
    <p>@include('partials.donation-text', ['channel' => $channel])</p>
</aside>
