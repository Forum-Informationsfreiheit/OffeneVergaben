@extends('layouts.earlybird')

@section('body')
    <h2>Aktive Datasets</h2>

    <div class="sort" style="margin-bottom: 20px;">
        <strong>Sortieren nach</strong>
        <ul style="list-style: none; margin: 0; padding: 0">
            <li><a href="{{ url('/datasets?orderBy=offeror_name'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">Anbieter Name</a>&nbsp;<a href="{{ url('/datasets?orderBy=offeror_name&desc=1'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">absteigend</a></li>
            <li><a href="{{ url('/datasets?orderBy=offeror_national_id'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">Anbieter Stammzahl</a>&nbsp;<a href="{{ url('/datasets?orderBy=offeror_national_id&desc=1'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">absteigend</a></li>
            <li><a href="{{ url('/datasets?orderBy=val_total').($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"") }}">Wert</a>&nbsp;<a href="{{ url('/datasets?orderBy=val_total&desc=1'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">absteigend</a></li>
            <li><a href="{{ url('/datasets?orderBy=id').($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"") }}">ID</a>&nbsp;<a href="{{ url('/datasets?orderBy=id&desc=1'.($showAll?"&showAll":"").($cpvFilter?"&cpvFilter=$cpvFilter":"")) }}">absteigend</a></li>
        </ul>
    </div>
    @if($cpvFilter && $cpv)
        <div class="filter" style="margin-bottom: 20px;">
            <strong>Filter</strong>
            <ul style="list-style: none; margin: 0; padding: 0">
                <li>CPV: {{ $cpv->code }} {{ $cpv->name }}</li>
            </ul>
        </div>
    @endif

    <table>
        <tr>
            <th>Id</th>
            <th>Vers.&nbsp;&nbsp;</th>
            <th>Datum&nbsp;&nbsp;</th>
            <th>Stammzahl</th>
            <th>Anbieter</th>
            <th>Titel</th>
            <th>Wert</th>
        </tr>
        @foreach($datasets as $dataset)

            <tr>
                <td><a href="{{ url('/datasets/'.$dataset->id) }}">{{ $dataset->id }}</a></td>
                <td>v{{ $dataset->version }}</td>
                <td>{{ (new \Carbon\Carbon($dataset->scraped_at))->format('d.m') }}</td>
                <td>{{ ui_shorten($dataset->offeror->national_id,20) }}</td>
                <td>{{ ui_shorten($dataset->offeror->name) }}</td>
                <td>{{ ui_shorten($dataset->title) }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ $dataset->valTotalFormatted }}</td>
            </tr>

        @endforeach
    </table>
    <div class="pagination-wrapper">
        @if($showAll)
            <span class="show-less-link">
                <a href="{{ url('/datasets') }}">weniger Ergebnisse anzeigen</a>
            </span>
            @else
            {{ $datasets->appends(request()->query())->links() }}
            <span class="show-all-link">
                <a href="{{ url('/datasets?showAll&'.$paramsString) }}">alle Ergebnisse anzeigen</a>
            </span>
        @endif
    </div>
@stop