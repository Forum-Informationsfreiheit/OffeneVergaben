@extends('layouts.earlybird')

@section('body')
    <h2>Bekanntgaben</h2>

    <div style="margin-top: 10px; margin-bottom: 20px">
        @if(request()->has('showMoreText'))
        <a href="{{ url('bekanntgaben') .($showAll ? '&showAll' : '') }}">
            mit gekürzten Texten darstellen
        </a>
        @else
            <a href="{{ url('bekanntgaben?showMoreText=1') .($showAll ? '&showAll=1' : '') }}">
            mit vollständigen Texten darstellen
        </a>
        @endif
    </div>

    <table>
        <tr>
            <th>Id</th>
            <th>Vers.&nbsp;&nbsp;</th>
            <th>Datum&nbsp;&nbsp;</th>
            <th>Stammzahl</th>
            <th>Anbieter</th>
            <th>Stammzahl</th>
            <th>Kontraktor</th>
            <th>Titel</th>
            <th>Gebote&nbsp;</th>
            <th>Wert</th>
        </tr>
        @foreach($datasets as $dataset)

            <tr>
                <td><a href="{{ url('/datasets/'.$dataset->id) }}">{{ $dataset->id }}</a></td>
                <td>v{{ $dataset->version }}</td>
                <td>{{ (new \Carbon\Carbon($dataset->scraped_at))->format('d.m') }}</td>
                <td>{{ ui_shorten($dataset->offeror->organization->nationalId,20) }}</td>
                <td><a href="{{ url('/bekanntgaben?offerorFilter='.$dataset->offeror->organization->id) }}">{{ $showMoreText ? $dataset->offeror->name : ui_shorten($dataset->offeror->name) }}</a></td>
                <td>{{ ui_shorten($dataset->contractor->organization->nationalId,20) }}</td>
                <td><a href="{{ url('/bekanntgaben?contractorFilter='.$dataset->contractor->organization->id) }}">{{ $showMoreText ? $dataset->contractor->name : ui_shorten($dataset->contractor->name) }}</a></td>
                <td>{{ $showMoreText ? $dataset->title : ui_shorten($dataset->title) }}</td>
                <td>{{ $dataset->nb_tenders_received }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ $dataset->valTotalFormatted }}</td>
            </tr>

        @endforeach
    </table>
    <div class="pagination-wrapper">
        @if($showAll)
            <span class="show-less-link">
                <a href="{{ url('/bekanntgaben') }}">weniger Ergebnisse anzeigen</a>
            </span>
        @else
            {{ $datasets->appends(request()->query())->links() }}
            <span class="show-all-link">
                <a href="{{ url('/bekanntgaben?showAll=1' . ($showMoreText ? '&showMoreText=1' : '')) }}">alle Ergebnisse anzeigen</a>
            </span>
        @endif
    </div>
@stop