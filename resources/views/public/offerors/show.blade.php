@extends('public.layouts.default')

@section('body:class','datasets')

@section('page:content')
    <h1 class="page-title">
        Auftraggeber {{ $org->name }}
    </h1>
    <div class="row">
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>Bezeichnung</th>
                    <th>Lieferant</th>
                    <th>Kategorie (CPV Hauptteil)</th>
                    <th>Bieter</th>
                    <th>Summe</th>
                    <th>Aktualisiert</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="name">{{ $item->title }}</td>
                        <td class="name">
                            @if($item->contractor)
                                <a href="{{ route('public::lieferant',$item->contractor->organization_id) }}">{{ $item->contractor->name }}</a>
                                @else
                                &nbsp;
                                @endif
                        </td>
                        <td class="name">{{ $item->cpv->toString() }}</td>
                        <td class="nb">{{ $item->nb_tenders_received }}</td>
                        <td class="value">{{ $item->valTotalFormatted }}</td>
                        <td class="date" title="{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y h:i') : '' }}">{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y') : '' }}</td>
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