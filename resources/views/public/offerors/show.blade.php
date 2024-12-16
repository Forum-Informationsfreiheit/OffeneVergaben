@extends('public.layouts.default')

@section('page:title','Auftraggeber ' . $org->name)

@section('body:class','offeror')

@section('page:content')
    <h1 class="page-title">
        Auftraggeber {{ $org->name }}{!! $org->is_identified ? '<span title="'.$org->nationalIdLabel.' '.$org->nationalId.'" class="org-identifier-inline">' . $org->nationalId . '</span>' : '' !!}
    </h1>
    <div class="stats-wrapper mb-4">
        <div class="row">
            <div class="col-md-4">
                <div>
                    <span class="stat-heading">Gesamtvolumen vergebener Aufträge</span>
                    <span class="stat-value">{{ ui_format_money($stats->totalVal) }}</span>
                </div>
                <div>
                    <span class="stat-heading">Durchschnittliche Bieteranzahl</span>
                    <span class="stat-value">{{ $stats->totalCount ? round($stats->totalTenders / $stats->totalCount,1) : '-' }}</span>
                </div>
            </div>
            <div class="col-md-4">
                <span class="stat-heading mb-2">Top 5 Lieferanten</span>
                @if(count($stats->topContractors))
                    <ul>
                        @foreach($stats->topContractors as $topContractorItem)
                            <li>
                                <a href="{{ route('public::lieferant',$topContractorItem->org->id) }}" title="{{ $topContractorItem->org->name }}">{{ ui_shorten($topContractorItem->org->name,48) }}</a> ({{ $topContractorItem->contractor_count }})
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
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>
                        Bezeichnung
                        @include('public.datasets.partials.sort',['field' => 'title'])
                    </th>
                    <th>
                        Lieferant
                        @include('public.datasets.partials.sort',['field' => 'contractor'])
                    </th>
                    <th>
                        Kategorie (CPV Hauptteil)
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
                        <td class="name"><a href="{{ route('public::auftrag',$item->id) }}">{{ $item->title }}</a></td>
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
                        <td class="name">{{ $item->cpv ? $item->cpv->toString() : '' }}</td>
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
    @php if_debug_mode_print_query_log(); @endphp
    <script>
        var $filterRoot   = $('#filterWrapper');
        var $filterToggle = $('#filterToggle');
        $filterToggle.on('click',function() {
            $filterRoot.toggleClass('collapsed');
        });
    </script>
@stop
