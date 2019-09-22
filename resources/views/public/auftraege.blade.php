@extends('public.layouts.default')

@section('page:content')
    <h1 class="page-title">Aufträge</h1>
    <div class="filter collapsed">
        <div class="row">
            <div class="col-md-3">filter spalte 1</div>
            <div class="col-md-3">filter spalte 2</div>
            <div class="col-md-3">filter spalte 3</div>
            <div class="col-md-3">filter spalte 4</div>
        </div>
    </div>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Auftraggeber</th>
                <th>Bezeichnung</th>
                <th>Verfügbar seite</th>
                <th>Angebotsfrist</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>1</td>
                    <td>2</td>
                    <td>3</td>
                    <td>4</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop