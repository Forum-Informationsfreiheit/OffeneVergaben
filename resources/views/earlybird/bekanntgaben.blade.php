@extends('layouts.earlybird')

@section('body')
    <h2>Bekanntgaben</h2>

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
                <td><a href="{{ url('/bekanntgaben?offerorFilter='.$dataset->offeror->organization->id) }}">{{ ui_shorten($dataset->offeror->name) }}</a></td>
                <td>{{ ui_shorten($dataset->contractor->organization->nationalId,20) }}</td>
                <td><a href="{{ url('/bekanntgaben?contractorFilter='.$dataset->contractor->organization->id) }}">{{ ui_shorten($dataset->contractor->name) }}</a></td>
                <td>{{ ui_shorten($dataset->title) }}</td>
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
                <a href="{{ url('/bekanntgaben?showAll') }}">alle Ergebnisse anzeigen</a>
            </span>
        @endif
    </div>
@stop