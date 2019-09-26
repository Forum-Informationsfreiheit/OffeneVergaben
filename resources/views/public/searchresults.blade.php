@extends('public.layouts.default')

@section('body:class','searchresults')

@section('page:content')
    <h1 class="page-title">
        Such-Ergebnisse
    </h1>
    @if($totalItems > 100)
    <div class="row">
        <div class="col">
            <div class="results-meta">
                <span class="count">
                    <strong>Mehr als 100 Ergebnisse gefunden, bitte die Suche einschränken!</strong>
                </span>
            </div>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col">
            <ul class="results">
                @foreach($organizations as $org)
                    <li class="result-item">
                        <span class="result-text">
                            {!! ui_highlight_tokens($org->name,$tokens,'strong') !!}
                        </span>
                        @if($org->is_offeror)
                            <a class="badge badge-primary" href="{{ route('public::show-auftraggeber',$org->id) }}">Auftraggeber</a>
                        @endif
                        @if($org->is_contractor)
                            <a class="badge badge-danger" href="{{ route('public::lieferant',$org->id) }}">Lieferant</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @if($totalItems > 100)
        <div class="row">
            <div class="col">
                <div class="results-meta" style="margin-top: 20px;">
                <span class="count">
                    <strong>Mehr als 100 Ergebnisse gefunden, bitte die Suche einschränken!</strong>
                </span>
                </div>
            </div>
        </div>
    @endif
@stop