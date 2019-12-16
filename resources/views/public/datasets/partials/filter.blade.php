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
                    <input class="form-control" type="text" name="volume_from" value="{{ request('volume_from') }}">
                    <span class="dash">&ndash;</span>
                    <input class="form-control" type="text" name="volume_to" value="{{ request('volume_to') }}">
                </div>
            </div>
        </div>
    </div>
    <button style="margin: 10px 0 0 0;" type="submit" class="btn btn-primary">Filtern</button>
</form>