@extends('public.layouts.default')

@section('page:title','Lieferanten')

@section('body:class','contractors')

@section('head:append')
    <link rel="stylesheet" href="{{ link_to_stylesheet('vendor/fontawesome/all.min',false) }}">
@stop

@section('page:content')
    <h1 class="page-title">
        Lieferanten
    </h1>
    <div class="row">
        <div class="col-md-12">
            <div class="info-block data-commentary mb-3">
                <i class="fa fa-info-circle"></i>
                <p class="mb-0">
                    Das Gesamtvolumen beschreibt die Gesamtsummen aller Aufträge, bei denen ein Lieferant einen Zuschlag erhalten hat – dies muss nicht den tatsächlich erhaltenen Beträgen entsprechen:
                </p>
                <ul class="my-1">
                    <li>
                        bei Rahmenverträgen wird das volle Liefer-Volumen mitunter nicht ausgeschöpft;
                    </li>
                    <li>
                        bei Ausschreibungen mit mehreren Losen und Lieferanten ist nur der Gesamtwert aller Lose verfügbar;
                    </li>
                </ul>
                <p class="mb-0">
                    etwaige spätere Vertragsänderungen sind oft nicht in den Daten reflektiert.
                </p>
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
                        <th>
                            Name
                            @include('public.datasets.partials.sort',['field' => 'name'])
                        </th>
                        <th>
                            National Id
                        </th>
                        <th>
                            Anzahl&nbsp;Aufträge
                            @include('public.datasets.partials.sort',['field' => 'count'])
                        </th>
                        <th>
                            Gesamtvolumen
                            @include('public.datasets.partials.sort',['field' => 'sum'])
                        </th>
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->id }}">
                        <td class="name">
                            <a href="{{ route('public::lieferant',$item->id) }}">{{ $item->name }}</a>
                        </td>
                        <td class="national-id">{{ $item->national_id === "?" ? "" : $item->national_id }}</td>
                        <td class="count">{{ $item->datasets_count }}</td>
                        <td class="value">{{ ui_format_money($item->sum_val_total) }}</td>
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
    </script>
@stop