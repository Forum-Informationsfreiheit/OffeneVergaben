@extends('layouts.earlybird')

@section('body')
    <h2>Organizations</h2>
    <div class="sort" style="margin-bottom: 20px;">
        <ul style="list-style: none; margin: 0; padding: 0">
            <li><a href="{{ url('/organizations?filterBy=offerors') }}">nur Auftraggeber</a></li>
            <li><a href="{{ url('/organizations?filterBy=contractors') }}">nur Auftragnehmer</a></li>
        </ul>
    </div>
    <div class="search" style="margin-bottom: 20px;">
        <label>
            <form method="GET" action="{{ url('/organizations') }}">
                <input type="text" name="search" style="width: 300px; padding: 5px;" value="{{ request()->has('search') ? request('search') : '' }}">&nbsp;Organisation suchen
            </form>
        </label>
    </div>
    <table style="width: 80%">
        <tr>
            <th>Auftraggeber</th>
            <th>Auftragnehmer</th>
            <th>FN</th>
            <th>GLN</th>
            <th>GKZ</th>
            <th>Andere</th>
            <th>Name</th>
        </tr>
        @foreach($organizations as $org)
            <tr>
                <td>
                    @if($org->offerors->count() > 0)
                        <a href="{{ url('/datasets?offerorFilter='.$org->id) }}">{{ $org->offerors->count() }}</a>
                    @endif
                </td>
                <td>
                    @if($org->contractors->count() > 0)
                        <a href="{{ url('/bekanntgaben?contractorFilter='.$org->id) }}">{{ $org->contractors->count() }}</a>
                    @endif
                </td>
                <td>{{ $org->fn }}</td>
                <td>{{ $org->gln }}</td>
                <td>{{ $org->gkz }}</td>
                <td>{{ $org->ukn }}</td>
                <td>{{ $org->name }}</td>
            </tr>
        @endforeach
    </table>
    <div class="pagination-wrapper">
        {{ $organizations->appends(request()->query())->links() }}
    </div>
@stop