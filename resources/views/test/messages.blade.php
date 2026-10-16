@extends('public.layouts.default')

@section('page:title','Meldungen')

@section('body:class','page')

@section('page:content')
    <h1>Meldungen der öffentlichen Seiten</h1>
    <p>
        Die öffentlichen Seiten sind cachebar und haben keine Session, daher gibt es keine Flash-Meldungen:
        das Ergebnis einer Aktion zeigt immer die Antwort auf die Aktion selbst.
        Die Texte stehen in <code>App\Support\PublicMessages</code>, diese Seite gibt es nur außerhalb von production.
    </p>
    <table class="table ov-table table-sm table-bordered">
        <thead>
        <tr>
            <th>Schlüssel</th>
            <th>Text</th>
            <th>Wann</th>
            <th>Wo angezeigt</th>
        </tr>
        </thead>
        <tbody>
        @foreach($catalog as $key => $entry)
            <tr>
                <td><code>{{ $key }}</code></td>
                <td><span class="badge badge-{{ $entry[0] }}">{{ $entry[0] == 'success' ? 'Erfolg' : 'Fehler' }}</span> {{ $entry[1] }}</td>
                <td>{{ $entry[2] }}</td>
                <td>{{ $entry[3] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Feldfehler im Subscribe-Formular</h2>
    <p>Texte aus <code>resources/lang/de/validation.php</code>, angezeigt in der Fehlerliste der Subscribe-Box (HTTP 422, Feld rot markiert).</p>
    <table class="table ov-table table-sm table-bordered">
        <thead>
        <tr>
            <th>Feld</th>
            <th>Regel</th>
            <th>Text</th>
        </tr>
        </thead>
        <tbody>
        @foreach($validationMessages as $row)
            <tr>
                <td><code>{{ $row[0] }}</code></td>
                <td>{{ $row[1] }}</td>
                <td>{{ $row[2] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@stop
