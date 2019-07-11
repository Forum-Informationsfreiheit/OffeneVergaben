@extends('layouts.earlybird')

@section('body')
    <h2>CPV Codes</h2>
    <div class="sort" style="margin-bottom: 20px;">
        <strong>Sortieren nach</strong>
        <ul style="list-style: none; margin: 0; padding: 0">
            <li><a href="{{ url('/cpvs?orderBy=code') }}">Code</a></li>
            <li><a href="{{ url('/cpvs?orderBy=datasets_count&desc=1') }}">Beliebteste</a></li>
        </ul>
    </div>
    <table style="width: 80%">
        <tr>
            <th>code</th>
            <th>name</th>
            <th>Anzahl</th>
        </tr>
        @foreach($cpvs as $cpv)
            <tr>
                <td>{{ $cpv->code }}</td>
                <td>{{ $cpv->name }}</td>
                <td style="text-align: right">
                    <a href="{{ url('/datasets?cpvFilter='.$cpv->code) }}">{{ $cpv->datasets_count ? $cpv->datasets_count : 0 }}</a>
                </td>
            </tr>
        @endforeach
    </table>
    <div class="pagination-wrapper">
        {{ $cpvs->appends(request()->query())->links() }}
    </div>
@stop