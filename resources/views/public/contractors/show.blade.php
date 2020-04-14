@extends('public.layouts.default')

@section('body:class','contractor')

@section('page:content')
    <h1 class="page-title">
        Lieferant {{ $org->name }}
    </h1>
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
                        Weitere Lieferanten
                    </th>
                    <th>
                        Kategorie
                        @include('public.datasets.partials.sort',['field' => 'cpv'])
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
                    <tr>
                        <td class="title"><a href="{{ route('public::auftrag',$item->id) }}">{{ $item->title }}</a></td>
                        <td class="offeror">
                            <a href="{{ route('public::show-auftraggeber',$item->offeror->organization_id) }}">{{ $item->offeror->name }}</a>
                            @if($item->offerors_count > 1)
                                <span class="badge badge-pill badge-light">+&nbsp;{{ $item->offerors_count -1 }} weitere Lieferanten</span>
                            @endif
                        </td>
                        <td class="contractors">
                            @foreach($item->contractors as $contractor)
                                @if($contractor->organization_id == $org->id)
                                    @continue
                                @endif
                                <a href="{{ route('public::lieferant',$contractor->organization_id) }}">{{ ui_shorten($contractor->name) }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </td>
                        <td class="cpv">{{ $item->cpv->toString() }}</td>
                        <td class="nb">{{ $item->nb_tenders_received }}</td>
                        <td class="value">{{ $item->valTotalFormatted }}</td>
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
    </script>
@stop