{{--
    Donation call-out in the emails. The text is in partials/donation-text.blade.php (shared with the website).
--}}
@include('partials.donation-text', ['channel' => 'mail'])
