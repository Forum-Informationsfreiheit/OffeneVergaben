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
        <div class="col-md-6">
            <h3>Auftraggeber & Lieferanten</h3>
            @if(count($organizations))
            <ul class="results">
                @foreach($organizations as $org)
                    <li class="result-item organizations">
                        <span class="result-text">
                            {!! ui_highlight_tokens($org->name,$tokens,'strong') !!} <small><span class="identifiers">{{ join(',',$org->identifiers) }}</span></small>
                        </span>
                        @if($org->is_identified)
                        @endif
                        @if($org->is_offeror)
                            <a class="badge badge-primary" href="{{ route('public::show-auftraggeber',$org->id) }}">Auftraggeber</a>
                        @endif
                        @if($org->is_contractor)
                            <a class="badge badge-danger" href="{{ route('public::lieferant',$org->id) }}">Lieferant</a>
                        @endif
                    </li>
                @endforeach
            </ul>
            @else
                <em>keine Ergebnisse gefunden</em>
            @endif
        </div>
        <div class="col-md-6">
            <h3>Aufträge</h3>
            @if(count($datasets))
            <ul class="results">
                @foreach($datasets as $dataset)
                    <li class="result-item datasets">
                        <span class="result-text">
                            <a href="{{ route('public::auftrag',$dataset->id) }}">{!! ui_highlight_tokens($dataset->title,$tokens,'strong') !!}</a>
                        </span>
                        @if($dataset->title !== $dataset->description)
                            <span class="result-text dataset-text">
                                {!! ui_highlight_tokens(ui_shorten($dataset->description,150),$tokens,'strong') !!}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @else
                <em>keine Ergebnisse gefunden</em>
            @endif
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