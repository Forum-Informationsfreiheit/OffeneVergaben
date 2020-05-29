@extends('public.layouts.default')

@section('page:title','Lieferant ' . $org->name)

@section('body:class','contractor')

@section('head:append')
    <link rel="stylesheet" href="{{ link_to_stylesheet('vendor/fontawesome/all.min',false) }}">
@stop

@section('page:content')
    <h1 class="page-title">
        Lieferant {{ $org->name }}{!! $org->is_identified ? '<span title="'.$org->nationalIdLabel.' '.$org->nationalId.'" class="org-identifier-inline">' . $org->nationalId . '</span>' : '' !!}
    </h1>
    <div class="stats-wrapper mb-4">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <span class="stat-heading">Gewonnene Aufträge</span>
                    <span class="stat-value">{{ $stats->totalCount }}</span>
                </div>
                <div>
                    <span class="stat-heading">Durchschnittliche Bieteranzahl</span>
                    <span class="stat-value">{{ $stats->totalCount ? round($stats->totalTenders / $stats->totalCount,1) : '-' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <span class="stat-heading mb-2">Top 5 Auftraggeber</span>
                @if(count($stats->topOfferors))
                    <ul>
                        @foreach($stats->topOfferors as $topOfferorItem)
                            <li>
                                <a href="{{ route('public::show-auftraggeber',$topOfferorItem->org->id) }}" title="{{ $topOfferorItem->org->name }}">{{ ui_shorten($topOfferorItem->org->name,48) }}</a> ({{ $topOfferorItem->offeror_count }})
                            </li>
                        @endforeach
                    </ul>
                @else
                    <em>keine Daten vorhanden</em>
                @endif
            </div>
            <div class="col-md-4">
                <span class="stat-heading mb-2">Top 5 Kategorien</span>
                @if(count($stats->topCpvs))
                    <ul>
                        @foreach($stats->topCpvs as $topCpvItem)
                            <li>
                                <a href="{{ route('public::auftraege',[ 'cpv' => $topCpvItem->cpv->trimmed_code, 'cpv_like' => 1 ]) }}" title="{{ $topCpvItem->cpv->code }} {{ $topCpvItem->cpv->name }}">{{ $topCpvItem->cpv->trimmed_code }} {{ ui_shorten($topCpvItem->cpv->name) }}</a> ({{ $topCpvItem->cpv_count }})
                            </li>
                        @endforeach
                    </ul>
                @else
                    <em>keine Daten vorhanden</em>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="info-block data-commentary mb-3">
                <i class="fa fa-info-circle"></i>
                <p class="mb-0">
                    Unten angeführte Summen beschreiben den Gesamtwert eines Auftrages – dieser muss nicht dem tatsächlich erhaltenen Betrag entsprechen:
                </p>
                <ul class="my-1">
                    <li>bei Rahmenverträgen wird das volle Liefer-Volumen mitunter nicht ausgeschöpft;</li>
                    <li>bei Ausschreibungen mit mehreren Losen und Lieferanten ist nur der Gesamtwert aller Lose verfügbar;</li>
                </ul>
                <p class="mb-0">
                    etwaige spätere Vertragsänderungen sind oft nicht in den Daten reflektiert.
                </p>
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
                        <td class="cpv">{{ $item->cpv ? $item->cpv->toString() : '' }}</td>
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