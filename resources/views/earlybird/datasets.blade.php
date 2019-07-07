@extends('layouts.earlybird')

@section('body')
    <h2>Aktive Datasets</h2>

    <div class="sort" style="margin-bottom: 20px;">
        <strong>Sortieren nach</strong>
        <ul style="list-style: none; margin: 0; padding: 0">
            <li><a href="{{ url('/datasets?orderBy=offeror_name'.($showAll?"&showAll":"")) }}">Anbieter Name</a>&nbsp;<a href="{{ url('/datasets?orderBy=offeror_name&desc=1'.($showAll?"&showAll":"")) }}">absteigend</a></li>
            <li><a href="{{ url('/datasets?orderBy=offeror_national_id'.($showAll?"&showAll":"")) }}">Anbieter Stammzahl</a>&nbsp;<a href="{{ url('/datasets?orderBy=offeror_national_id&desc=1'.($showAll?"&showAll":"")) }}">absteigend</a></li>
            <li><a href="{{ url('/datasets?orderBy=val_total').($showAll?"&showAll":"") }}">Wert</a>&nbsp;<a href="{{ url('/datasets?orderBy=val_total&desc=1'.($showAll?"&showAll":"")) }}">absteigend</a></li>
        </ul>
    </div>

    <table>
        <tr>
            <th>Id</th>
            <th>Vers.&nbsp;&nbsp;</th>
            <th>Stammzahl</th>
            <th>Anbieter</th>
            <th>Titel</th>
            <th>Wert</th>
        </tr>
        @foreach($datasets as $dataset)

            <tr>
                <td><a href="{{ url('/datasets/'.$dataset->id) }}">{{ $dataset->id }}</a></td>
                <td>v{{ $dataset->version }}</td>
                <td>{{ ui_shorten($dataset->offeror->national_id,20) }}</td>
                <td>{{ ui_shorten($dataset->offeror->name) }}</td>
                <td>{{ ui_shorten($dataset->title) }}</td>
                <td style="text-align: right">{{ $dataset->valTotalFormatted }}</td>
            </tr>

        @endforeach
    </table>
    <style>
        .pagination-wrapper {
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .pagination-wrapper:after {
            display: block;
            content: '';
            clear: left;
        }
        ul.pagination {
            list-style: none;
        }
        ul.pagination li {
            float: left;
            padding-right: 5px;
        }
        .show-all-link {
            display: inline-block;
            margin-left: 20px;
        }
    </style>
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