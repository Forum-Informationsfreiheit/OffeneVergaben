@extends('public.layouts.default')

@section('body:class','datasets')

@section('page:content')
    <h1 class="page-title">
        Aufträge
    </h1>
    <div class="filter-wrapper collapsed">
        <div class="filter-head">
            <a href="#" data-status="hidden">
                <span class="icon-wrapper filter">
                    @svg('/img/icons/filter.svg','filter')
                </span>
                <span class="action-text">Ergebnisse einschränken</span>
            </a>
        </div>
        <div class="filter-body">

        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="results-meta">
                <span class="count">ungefähr ? Ergebnisse</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table class="table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>Bezeichnung</th>
                    <th>Auftraggeber</th>
                    <th>Lieferant</th>
                    <th>Bieter</th>
                    <th>Summe</th>
                    <th>Aktualisiert</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="name">{{ $item->title }}</td>
                        <td class="name">{{ $item->offeror->name }}</td>
                        <td class="name">{{ $item->contractor ? $item->contractor->name : '' }}</td>
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