@extends('public.layouts.default')

@section('page:title','Auftraggeber')

@section('body:class','offerors')

@section('page:content')
    <h1 class="page-title">
        Auftraggeber
    </h1>
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
                    <th class="fixed-width w-12">
                        National Id
                    </th>
                    <th class="fixed-width w-13">
                        Anzahl&nbsp;Aufträge
                        @include('public.datasets.partials.sort',['field' => 'count'])
                    </th>
                    <th class="fixed-width w-13">
                        Gesamtvolumen
                        @include('public.datasets.partials.sort',['field' => 'sum'])
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->id }}">
                        <td class="name">
                            <a href="{{ route('public::show-auftraggeber',$item->id) }}">{{ $item->name }}</a>
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