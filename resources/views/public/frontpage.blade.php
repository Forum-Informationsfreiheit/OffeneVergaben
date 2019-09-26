@extends('public.layouts.frontpage')

@section('body:class','frontpage')

@section('lead')
    <div class="container">
        <p class="lead">
            Das <a class="lead-link" href="https://www.informationsfreiheit.at">Forum Informationsfreiheit</a> engagiert sich für mehr Transparenz in der Verwaltung.
            Auf offenevergaben.at machen wir Auftragsvergaben der öffentlichen Hand, die ab 2019
            als Open Data veröffentlicht werden, für Journalist*innen, Bürgerinitiativen, NGOs und
            interessierte Bürger*innen nachvollziehbar.
        </p>
        <form class="search-form-lg" action="{{ route('public::suchen') }}" method="GET">
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" placeholder="Nach Lieferanten oder Auftraggebern suchen..." aria-label="Recipient's username" aria-describedby="button-addon2" name="suche">
                <div class="input-group-append">
                    <button class="btn btn-lg btn-outline-secondary" type="submit" id="button-addon2">
                        @svg('/img/icons/suche_start.svg','search')
                    </button>
                </div>
            </div>
        </form>
    </div>
@stop

@section('overview')
    <div class="container">
        <div class="row">
            <div class="col-lg">
                <div class="box">
                    <h3><a href="{{ route('public::auftraggeber') }}">Wer vergibt am meisten öffentliche Aufträge?</a></h3>
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link" id="offerors-sum-tab" data-toggle="tab" href="#offerorsSum" role="tab" aria-controls="home" aria-selected="true">
                                @svg('/img/icons/auftragsvolumen.svg','sum')&nbsp;&nbsp;Nach Auftragsvolumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" id="offerors-count-tab" data-toggle="tab" href="#offerorsCount" role="tab" aria-controls="home" aria-selected="true">
                                @svg('/img/icons/auftragsanzahl.svg','sum')&nbsp;&nbsp;Nach Anzahl Aufträgen
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" id="fifTabContentOfferors">
                        <div class="tab-pane fade" id="offerorsSum" role="tabpanel" aria-labelledby="offerors-sum-tab">
                            <ol class="top-ten">
                                @foreach($topOfferorsBySum as $topOfferorBySum)
                                    <li><a href="{{ route('public::show-auftraggeber',$topOfferorBySum->id) }}" title="{{ $topOfferorBySum->name }}">{{ ui_shorten($topOfferorBySum->name,45) }}</a><span class="float-right">{{ ui_format_money($topOfferorBySum->sum_total_val) }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="tab-pane fade show active" id="offerorsCount" role="tabpanel" aria-labelledby="offerors-count-tab">
                            <ol class="top-ten">
                                @foreach($topOfferorsByCount as $topOfferorByCount)
                                    <li><a href="{{ route('public::show-auftraggeber',$topOfferorByCount->id) }}">{{ ui_shorten($topOfferorByCount->name,60) }}</a> ({{ $topOfferorByCount->datasets_count }})</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg">
                <div class="box">
                    <h3><a href="{{ route('public::lieferanten') }}">Wer erhält am meisten öffentliche Aufträge?</a></h3>
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link" id="contractors-sum-tab" data-toggle="tab" href="#contractorsSum" role="tab" aria-controls="home" aria-selected="true">
                                @svg('/img/icons/auftragsvolumen.svg','sum')&nbsp;&nbsp;Nach Auftragsvolumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" id="contractors-count-tab" data-toggle="tab" href="#contractorsCount" role="tab" aria-controls="home" aria-selected="true">
                                @svg('/img/icons/auftragsanzahl.svg','sum')&nbsp;&nbsp;Nach Anzahl Aufträgen
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" id="fifTabContentContractors">
                        <div class="tab-pane fade" id="contractorsSum" role="tabpanel" aria-labelledby="contractors-sum-tab">
                            <ol class="top-ten">
                                @foreach($topContractorsBySum as $topContractorBySum)
                                    <li><a href="{{ route('public::lieferant',$topContractorBySum->id) }}" title="{{ $topContractorBySum->name }}">{{ ui_shorten($topContractorBySum->name,45) }}</a><span class="float-right">{{ ui_format_money($topContractorBySum->sum_total_val) }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="tab-pane fade show active" id="contractorsCount" role="tabpanel" aria-labelledby="contractors-count-tab">
                            <ol class="top-ten">
                                @foreach($topContractorsByCount as $topContractorByCount)
                                    <li><a href="{{ route('public::lieferant',$topContractorByCount->id) }}">{{ ui_shorten($topContractorByCount->name,60) }}</a> ({{ $topContractorByCount->datasets_count }})</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php /*
        <div class="row">
            <div class="col-md">
                <div class="box">
                    <h3>Wo werden am meisten Aufträge vergeben?</h3>
                    <div class="box-fake-inner">
                        <span>todo</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="box">
                    <h3>Auftragsvergaben nach Branchen - wer führt?</h3>
                    <div class="box-fake-inner">
                        <span>todo</span>
                    </div>
                </div>
            </div>
        </div>
        */ ?>
    </div>
@stop

@section('features')
    <div class="container">
        <div class="row">
            <div class="col">
                <h3>Was bietet offenevergaben.at?</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                    <span class="mx-auto icon-wrapper filter">
                        @svg('img/icons/filter.svg','filter')
                    </span>
                    </div>
                    <h4>Datenfilter</h4>
                    <p>Lorem ipsum... </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                    <span class="mx-auto icon-wrapper notification">
                        @svg('img/icons/benachrichtigung_w.svg','notification')
                    </span>
                    </div>
                    <h4>Benachrichtigungen</h4>
                    <p>Lorem ipsum... </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                    <span class="mx-auto icon-wrapper export">
                        @svg('img/icons/export_w.svg','export')
                    </span>
                    </div>
                    <h4>Exportmöglichkeiten</h4>
                    <p>Lorem ipsum... </p>
                </div>
            </div>
        </div>
    </div>
@stop