@extends('layouts.earlybird')

@section('body')
    <h2>Origins (= Daten Provider)</h2>

    <table>
        <tr>
            <th>id</th>
            <th>data.gv.at id</th>
            <th>name</th>
            <th>url</th>
            <th>aktiv</th>
        </tr>
        @foreach($origins as $origin)
            <tr>
                <td>{{ $origin->id }}</td>
                <td>{{ $origin->reference_id }}</td>
                <td>{{ $origin->name }}</td>
                <td>{{ $origin->url }}</td>
                <td>{{ $origin->scrape ? "1" : "0" }}</td>
            </tr>
        @endforeach
    </table>
@stop