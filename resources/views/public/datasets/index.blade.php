@extends('public.layouts.default')

@section('page:title','Aufträge')

@section('head:append')
    <style>.form-group .ftob { display: none; visibility: hidden; }</style>
@stop

@section('body:class','datasets')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1 class="page-title">
                Aufträge
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div id="filterWrapper" class="filter-wrapper {{-- $filters->hasAny() ? '' : 'collapsed' --}}">
                <div class="filter-head">
                    <a href="#" id="filterToggle" class="filter-toggle" data-status="hidden">
                <span class="icon-wrapper filter">
                    @svg('/img/icons/filter.svg','filter')
                </span>
                        <span class="action-text">Ergebnisse einschränken</span>
                    </a>
                </div>
                <div class="filter-body">
                    @include('public.datasets.partials.filter')
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="float-left results-meta">
                {{-- Auf hunderter runden --}}
                <span class="count">{{ $totalItems > 100 ? 'ungefähr ' . number_format(round($totalItems,-2),0,',','.') : number_format($totalItems,0,',','.') }} Ergebnisse</span>
            </div>
            <div title='Um eine tägliche Email-Benachrichtigung zu erstellen: schränken Sie die Ergebnisse gemäß Ihrem Interesse ein und klicken Sie auf "Filtern".' class="float-right">
                @svg('/img/icons/benachrichtigung.svg','subscribe')&nbsp;&nbsp;
                @if( $filters->hasAny() )
                    <a id="subscribeToggle" href="#">Benachrichtigung für diese Suche einrichten</a>
                    @else
                    <span class="text-muted">Benachrichtigung für diese Suche einrichten</span>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div id="subscribeWrapper" class="subscribe-wrapper {{ $errors->subscription->isEmpty() ? 'collapsed' : '' }}">
                <div class="subscribe-head">
                    <span class="icon-wrapper subscribe">
                        @svg('/img/icons/benachrichtigung.svg','subscribe')&nbsp;&nbsp;
                    </span>
                    <span class="action-text">Benachrichtigung einrichten</span>
                </div>
                <div class="subscribe-body">
                    @include('public.datasets.partials.subscribe')
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>
                        Bezeichnung
                        @include('public.datasets.partials.sort',['field' => 'title'])
                    </th>
                    <th>
                        Auftraggeber
                        @include('public.datasets.partials.sort',['field' => 'offeror'])
                    </th>
                    <th>
                        Lieferant
                        @include('public.datasets.partials.sort',['field' => 'contractor'])
                    </th>
                    <th>
                        Bieter
                        @include('public.datasets.partials.sort',['field' => 'nb_tenders_received'])
                    </th>
                    <th>
                        Summe
                        @include('public.datasets.partials.sort',['field' => 'val_total'])
                    </th>
                    <th>
                        Aktualisiert
                        @include('public.datasets.partials.sort',['field' => 'item_lastmod'])
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->id }}">
                        <td class="name">
                            <a href="{{ route('public::auftrag',$item->id) }}">{{ $item->title }}</a>
                        </td>
                        <td class="name">
                            <a href="{{ route('public::show-auftraggeber',$item->offeror->organization_id) }}">{{ $item->offeror->name }}</a>
                            @if($item->offerors_count > 1)
                                <span class="badge badge-pill badge-light">+&nbsp;{{ $item->offerors_count -1 }} weitere Auftraggeber</span>
                            @endif
                        </td>
                        <td class="name">
                            @if($item->contractor)
                                <a href="{{ route('public::lieferant',$item->contractor->organization_id) }}">{{ $item->contractor->name }}</a>
                                @if($item->contractors_count && $item->contractors_count > 1)
                                    <span class="badge badge-pill badge-light">+&nbsp;{{ $item->contractors_count -1 }} weitere Lieferanten</span>
                                @endif
                                @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="nb">{{ $item->nb_tenders_received }}</td>
                        <td class="value">{{ $item->valTotalFormatted }}</td>
                        {{-- <td class="date" title="{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y h:i') : '' }}">{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y') : '' }}</td> --}}
                        <td class="date" title="{{ $item->item_lastmod ? $item->item_lastmod->format('d.m.Y h:i') : '' }}">{{ $item->item_lastmod ? $item->item_lastmod->format('d.m.Y') : '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $data->appends(request()->query())->links('public.partials.pagination', [ 'ulClass' => [ "mx-auto", "justify-content-center" ] ]) }}
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

        var $subscribeRoot =  $('#subscribeWrapper');
        var $subscribeToggle = $('#subscribeToggle');
        $subscribeToggle.on('click',function(evt) {
            $subscribeRoot.toggleClass('collapsed');

            evt.preventDefault();
            return false;
        });
    </script>
    <script>
        (function(app) {
            var $cpv = $('#cpvInput');
            var $help = $('#cpvInputHelp');

            app.autocomplete({
                input: $cpv.get(0),
                fetch: function(text, update) {
                    text = text.toLowerCase();

                    $.get('{{ route("public::ajax-cpv-search") }}',{
                        query: text
                    }).done(function(data) {
                        if (!data) {
                            update([]); return;
                        }

                        var suggestions = [];
                        data.forEach(function(e) {
                            suggestions.push({
                                label: e.trimmed_code + " " + e.name,
                                value: e.trimmed_code,
                                code:  e.code,
                                name:  e.name,
                            });
                        });

                        update(suggestions);
                    });
                },
                onSelect: function(item) {
                    if (item.value.length === 8) {
                        $cpv.val(item.value);
                    } else {
                        $cpv.val(item.value + "*");
                    }

                    $help.text(item.name);
                },
                preventSubmit: true,
            });

            $('input[name="pm_title"]').addClass('ftob');
        })(__ives);
    </script>
@stop
