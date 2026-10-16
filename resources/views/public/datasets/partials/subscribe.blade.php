<div id="subscribeMessages" role="alert" hidden></div>
<form id="subscribeForm" method="POST" novalidate action="{{ route('public::subscribe') }}">
    <div class="row">
        <div class="col-md-12">
            <p style="line-height: 1.5; font-size: 0.85rem" class="mb-4">
                Um einmal täglich über neue Ergebnisse zu Ihrer Suche per Email informiert zu werden, geben Sie bitte eine Bezeichnung für die Suche sowie Ihre Emailadresse ein. Nach der Übermittlung erhalten Sie ein Bestätigungs-Email. Klicken Sie den Link darin, um die Benachrichtigung zu aktivieren.
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="subscribeTitle" style="display: none;">Bezeichnung</label>
                <input name="pm_title" type="text" class="form-control" value="" placeholder='z.B. "Aufträge ab 1 Mio"'> {{-- Honeypot --}}
                <input name="title" type="text" class="form-control" id="subscribeTitle" aria-describedby="subscribeTitleHelp" placeholder='z.B. "Aufträge ab 1 Mio. €"'>
                <small id="subscribeTitleHelp" class="form-text text-muted">Aussagekräfitge Bezeichnung</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="subscribeEmail" style="display: none;">Email</label>
                <input name="email" type="email" class="form-control" id="subscribeEmail" aria-describedby="subscribeEmailHelp" placeholder='z.B. "marlene.musterfrau@provider.at"'>
                <small id="subscribeEmailHelp" class="form-text text-muted">Benachrichtigungen an diese Email Adresse schicken. Wir verwenden Ihre Email-Adresse nur, um  Informationen zu OffeneVergaben.at zu übermitteln, und geben diese nicht an Dritte weiter. Sie können Benachrichtigungen jederzeit abbestellen.</small>
            </div>
        </div>
    </div>
    @if(false)
    <div class="row">
        <div class="col-md-6">
            <p style="line-height: 1.5; font-size: 0.85rem;" class="mb-3">Wir verwenden Ihre Email-Adresse nur, um  Informationen zu OffeneVergaben.at zu übermitteln, und geben diese nicht an Dritte weiter. Sie können Benachrichtigungen jederzeit abbestellen.</p>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-md-6">
            <div class="form-check">
                <input name="confirm" class="form-check-input" type="checkbox" value="1" id="subscribeCheck" autocomplete="off">
                <label class="form-check-label" for="subscribeCheck">
                    Ich habe die <a target="_blank" href="{{ url('/datenschutz') }}">Datenschutzerklärung</a> gelesen und stimme dieser zu.
                </label>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <input type="hidden" name="query" value="{{ $queryString }}">
            <button id="subscribeSubmit" class="btn btn-primary mt-3" type="submit">
                Aktivieren
            </button>
</div>
</div>
</form>
