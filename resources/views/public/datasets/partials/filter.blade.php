<form id="filterForm" method="GET" action="{{ route('public::auftraege') }}">
    <div class="row">
        <div class="col-md-9">
            <div class="row">
                <div class="col-sm-12">
                    <div class="filter-group">
                        <div class="filter-group-label">
                            Textfilter
                        </div>
                        <div class="filter-group-inputs">
                            <input class="form-control form-control-sm" type="text" name="search" value="{{ request('search') }}" placeholder="Nach Bezeichnung, Auftraggeber oder Lieferant filtern (min. 5 Zeichen eingeben)">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="supplies" name="contract_types[]" id="filterSupplies" {{ $filters->has('contract_types','supplies') ? 'checked' : '' }} >
                                <label class="form-check-label" for="filterSupplies">
                                    Lieferauftrag
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="ocm_works" name="contract_types[]" id="filterWorksOcm" {{ $filters->has('contract_types','ocm_works') ? 'checked' : '' }} >
                                <label class="form-check-label" for="filterWorksOcm">
                                    Baukonzession
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="ocm_services" name="contract_types[]" id="filterServicesOcm" {{ $filters->has('contract_types','ocm_services') ? 'checked' : '' }} >
                                <label class="form-check-label" for="filterServicesOcm">
                                    Dienstleistungskonzession
                                </label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" value="ocm_supplies" name="contract_types[]" id="filterSuppliesOcm" {{ $filters->has('contract_types','ocm_supplies') ? 'checked' : '' }} >
                                <label class="form-check-label" for="filterSuppliesOcm">
                                    Lieferkonzession
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
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
                    <div class="filter-group tenders-range">
                        <div class="filter-group-label">
                            Bieter
                        </div>
                        <div class="filter-group-inputs">
                            <input class="form-control form-control-sm" type="text" name="tenders_from" value="{{ request('tenders_from') }}" placeholder="von...">
                            <span class="dash">&ndash;</span>
                            <input class="form-control form-control-sm" type="text" name="tenders_to" value="{{ request('tenders_to') }}" placeholder="...bis">
                        </div>
                    </div>
                    <div class="filter-group">
                        <div class="filter-group-label">
                            Kategorie (CPV Hauptteil)
                        </div>
                        <div class="filter-group-inputs">
                            <input class="form-control form-control-sm" type="text" name="cpv" id="cpvInput" value="{{ request('cpv') }}{{ request('cpv_like') ? '*' : '' }}" autocomplete="off" placeholder='z.B. "Bauarbeiten"'>
                            <small id="cpvInputHelp" class="form-text text-muted"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="filter-group">
                <div class="filter-group-label">
                    Erfüllungsort
                </div>
                <div class="filter-group-inputs">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="NAT" name="nuts[]" id="filterNutsInt" {{ $filters->has('nuts','NAT') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNutsInt">International</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT11" name="nuts[]" id="filterNuts11" {{ $filters->has('nuts','AT11') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts11">Burgenland</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT21" name="nuts[]" id="filterNuts21" {{ $filters->has('nuts','AT21') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts21">Kärnten</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT12" name="nuts[]" id="filterNuts12" {{ $filters->has('nuts','AT12') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts12">Niederösterreich</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT31" name="nuts[]" id="filterNuts31" {{ $filters->has('nuts','AT31') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts31">Oberösterreich</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT32" name="nuts[]" id="filterNuts32" {{ $filters->has('nuts','AT32') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts32">Salzburg</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT22" name="nuts[]" id="filterNuts22" {{ $filters->has('nuts','AT22') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts22">Steiermark</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT33" name="nuts[]" id="filterNuts33" {{ $filters->has('nuts','AT33') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts33">Tirol</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT34" name="nuts[]" id="filterNuts34" {{ $filters->has('nuts','AT34') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts34">Vorarlberg</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="AT13" name="nuts[]" id="filterNuts13" {{ $filters->has('nuts','AT13') ? 'checked' : '' }} >
                        <label class="form-check-label" for="filterNuts13">Wien</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button style="margin: 10px 0 0 0;" type="submit" class="btn btn-primary">Filtern</button>
</form>