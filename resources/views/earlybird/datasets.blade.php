@extends('layouts.earlybird')

@section('body')
    <h2>Aktive Datasets</h2>

    <table>
        <tr>
            <th>id</th>
            <th>version</th>
            <th>titel</th>
            <th>auftragswert</th>
        </tr>
        @foreach($datasets as $dataset)

            <tr>
                <td><a href="{{ url('/datasets/'.$dataset->id) }}">{{ $dataset->id }}</a></td>
                <td>v{{ $dataset->version }}</td>
                <td>{{ $dataset->title }}</td>
                <td style="text-align: right">{{ $dataset->valTotalFormatted }}</td>
            </tr>

        @endforeach
    </table>
@stop