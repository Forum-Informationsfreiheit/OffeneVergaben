{{--
    Text of the donation call-out, the one place to edit it. Used by
    - the emails: mail/partials/donation.blade.php (daily subscription summary)
    - the website: public/partials/donation.blade.php (homepage below the search field, /aufträge above the results,
      and as a small box on the page of a single dataset)

    Plain HTML (no Markdown), so that it works in both. Keep the lines unindented: the emails are Markdown, where
    indented lines become code blocks.

    Different text per place: $channel is 'mail' (emails), 'web' (homepage, /aufträge) or 'dataset' (the small box on
    the page of a single dataset, currently only the last sentence), e.g.

    @if($channel === 'mail')
    Sie erhalten diese Benachrichtigung kostenlos.<br>
    @else
    Diese Seite ist kostenlos.<br>
    @endif

    Lines outside @if appear in both.

    The heart before the link is a character (&#10084;) with an inline color, not an image or a CSS class: email
    programs often block images and ignore the site's stylesheet.
--}}
@php
    // Emails: the first two weeks from this date "vermisst?", then alternating every day. Set it to the day of the
    // first emails after the deploy. Day 0 is that date; days before it count as the first two weeks.
    $campaignStart = \Carbon\Carbon::parse('2026-10-16', config('app.timezone'));
    $campaignDay = $campaignStart->diffInDays(now()->startOfDay(), false);
@endphp
@if($channel === 'mail')
@if($campaignDay < 14)
<b>Haben Sie diese Mails vermisst?</b><br>
@elseif($campaignDay % 2 === 0)
<b>Nutzen Sie diese Plattform beruflich?</b><br>
@else
<b>Finden Sie {{ config('app.name') }} nützlich?</b><br>
@endif
@elseif($channel === 'web')
<b>Nutzen Sie diesen Service beruflich?</b><br>
@endif
@if($channel !== 'dataset')
Wir betreiben {{ config('app.name') }} seit 2021 ohne weiterer Finanzierung ehrenamtlich und mussten gerade signifikant Arbeit investieren, um von Behörden geänderte Veröffentlichungsformate zu unterstützen.<br>
@endif
Mit einer <span style="color: #e54f5c;" aria-hidden="true">&#10084;</span>&nbsp;<a href="https://fragdenstaat.at/spenden/?pk_keyword=offenevergaben">Spende an das Forum Informationsfreiheit</a> können Sie helfen, den Betrieb von {{ config('app.name') }} sicherzustellen.
