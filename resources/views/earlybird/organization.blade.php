@extends('layouts.earlybird')

@section('body')
    <h2>Organization {{ $org->id }}</h2>
    <table>
        <tr>
            <td>GLN</td>
            <td>{{ $org->gln }}</td>
        </tr>
        <tr>
            <td>FN</td>
            <td>{{ $org->fn }}</td>
        </tr>
        <tr>
            <td>GKZ</td>
            <td>{{ $org->gkz }}</td>
        </tr>
        <tr>
            <td>Andere ID</td>
            <td>{{ $org->ukn }}</td>
        </tr>
        <tr>
            <td>Bezeichnung</td>
            <td>{{ $org->name }}</td>
        </tr>
        <tr>
            <td>hinzugefügt am</td>
            <td>{{ $org->created_at->format('d.m.Y') }}</td>
        </tr>
    </table>
@stop