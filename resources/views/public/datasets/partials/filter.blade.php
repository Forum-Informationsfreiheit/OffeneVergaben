<form id="filterForm" method="GET" action="{{ route('public::auftraege') }}">
    <div class="row">
        <!-- template
        <div class="col-md-3">
            <div class="filter-group">
                <div class="filter-group-label">

                </div>
                <div class="filter-group-inputs">

                </div>
            </div>
        </div>
        -->
        <div class="col-md-3">
            <div class="row">
                <div class="col-sm-12">
                    <div class="filter-group">
                <span class="filter-group-label">
                    Auftragsart
                </span>
                        <div class="filter-group-inputs">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="ausschreibung" name="types[]" id="filterAusschreibung" {{ $filters->has('types','ausschreibung') ? 'checked' : ''}}>
                                <label class="form-check-label" for="filterAusschreibung">
                                    Ausschreibung
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="auftrag" name="types[]" id="filterAuftrag" {{ $filters->has('types','auftrag') ? 'checked' : '' }}>
                                <label class="form-check-label" for="filterAuftrag">
                                    Auftrag
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="filter-group date-range">
                        <div class="filter-group-label">
                            Zeitraum
                        </div>
                        <div class="filter-group-inputs form-row">
                            <div class="col-sm-4"><span class="block-input-label-sm">von</span></div>
                            <div class="col-sm-8"><input class="form-control form-control-sm mb-1" type="date" name="date_from" value="{{ $filters->has('date_from') ? request('date_from') : '' }}"></div>
                        </div>
                        <div class="filter-group-inputs form-row">
                            <div class="col-sm-4"><span class="block-input-label-sm">bis</span></div>
                            <div class="col-sm-8"><input class="form-control form-control-sm mb-1" type="date" name="date_to" value="{{ $filters->has('date_to') ? request('date_to') : '' }}"></div>
                        </div>
                        <div class="filter-group-inputs form-row">
                            <div class="col-sm-4"><span class="block-input-label-sm">Parameter</span></div>
                            <div class="col-sm-8">
                                <select class="form-control form-control-sm mb-1" name="date_type">
                                    <option {{ $filters->has('date_type') ? '' : 'selected="selected"' }} value="default">Datensatz aktualisiert</option>
                                    <option {{ $filters->has('date_type','dateRte') ? 'selected="selected"' : '' }} value="dateRte">Schlusstermin für den Eingang</option>
                                    <option {{ $filters->has('date_type','dateFPu') ? 'selected="selected"' : '' }} value="dateFPu">Erstmalige Verfügbarkeit</option>
                                    <option {{ $filters->has('date_type','dateSta') ? 'selected="selected"' : '' }} value="dateSta">Ausführungsbeginn</option>
                                    <option {{ $filters->has('date_type','dateEnd') ? 'selected="selected"' : '' }} value="dateEnd">Erfüllungszeitpunkt</option>
                                    <option {{ $filters->has('date_type','dateCCo') ? 'selected="selected"' : '' }} value="dateCCo">Tag Vertragsabschluss</option>
                                    <option {{ $filters->has('date_type','dateLCh') ? 'selected="selected"' : '' }} value="dateLCh">Letzte Änderung</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="filter-group">
                <div class="filter-group-label">
                    Auftrags- oder Konzessionsart
                </div>
                <div class="filter-group-inputs">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="works" name="contract_types[]" id="filterWorks" {{ $filters->has('contract_types','works') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterWorks">
                            Bauauftrag
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="services" name="contract_types[]" id="filterServices" {{ $filters->has('contract_types','services') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterServices">
                            Dienstleistungsauftrag
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="supplies" name="contract_types[]" id="filterSupplies" {{ $filters->has('contract_types','supplies') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterSupplies">
                            Lieferauftrag
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="filter-group volume-range">
                <div class="filter-group-label">
                    Auftragsvolumen
                </div>
                <div class="filter-group-inputs">
                    <input class="form-control form-control-sm" type="text" name="volume_from" value="{{ request('volume_from') }}" placeholder="von...">
                    <span class="dash">&ndash;</span>
                    <input class="form-control form-control-sm" type="text" name="volume_to" value="{{ request('volume_to') }}" placeholder="...bis">
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="filter-group">
                <div class="filter-group-label">
                    Kategorie (CPV Hauptteil)
                </div>
                <div class="filter-group-inputs">
                    <input class="form-control form-control-sm" type="text" name="cpv" value="{{ request('cpv') }}{{ request('cpv_like') ? '*' : '' }}">
                </div>
            </div>
        </div>
    </div>
    <button style="margin: 10px 0 0 0;" type="submit" class="btn btn-primary">Filtern</button>
</form>