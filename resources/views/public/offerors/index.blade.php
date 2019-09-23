@extends('public.layouts.default')

@section('body:class','datasets')

@section('page:content')
    <h1 class="page-title">
        Auftraggeber
    </h1>
    <div id="filterWrapper" class="filter-wrapper collapsed">
        <div class="filter-head">
            <a href="#" id="filterToggle" class="filter-toggle" data-status="hidden">
                <span class="icon-wrapper filter">
                    @svg('/img/icons/filter.svg','filter')
                </span>
                <span class="action-text">Ergebnisse einschränken</span>
            </a>
        </div>
        <div class="filter-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="filter-group">
                        <span class="filter-group-label">
                            Auftragsart
                        </span>
                        <div class="filter-group-inputs">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" name="ausschreibung" id="filterAusschreibung">
                                <label class="form-check-label" for="filterAusschreibung">
                                    Ausschreibung
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" name="auftrag" id="filterAuftrag">
                                <label class="form-check-label" for="filterAuftrag">
                                    Auftrag
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="results-meta">
                <span class="count">ungefähr {{ $totalItems }} Ergebnisse</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>Name</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><a href="{{ route('public::show-auftraggeber',$item->id) }}">{{ $item->name }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $items->links() }}
            </div>
        </div>
    </div>
@stop

@section('body:append')
    <script>
        var $filterRoot   = $('#filterWrapper');
        var $filterToggle = $('#filterToggle');
        $filterToggle.on('click',function() {
            $filterRoot.toggleClass('collapsed');
        });
    </script>
@stop