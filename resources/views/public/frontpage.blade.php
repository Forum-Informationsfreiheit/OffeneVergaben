@extends('public.layouts.frontpage')

@section('body:class','frontpage')

@section('lead')
    <div class="container">
        <p class="lead">
            Das Forum Informationsfreiheit engagiert sich für mehr Transparenz in der Verwaltung.
            Auf offenevergaben.at machen wir Auftragsvergaben der öffentlichen Hand, die ab 2019
            als Open Data veröffentlicht werden, für Journalist*innen, Bürgerinitiativen, NGOs und
            interessierte Bürger*innen nachvollziehbar.
        </p>
    </div>
@stop

@section('overview')
    <div class="container">
        <div class="row">
            <div class="col-md">
                <div class="box">
                    <h3>Wer vergibt am meisten öffentliche Aufträge?</h3>
                    <ol class="top-ten">
                        @foreach($topOfferorsByCount as $topOfferorByCount)
                            <li><a href="{{ route('public::show-auftraggeber',$topOfferorByCount->id) }}">{{ ui_shorten($topOfferorByCount->name,60) }}</a> ({{ $topOfferorByCount->datasets_count }})</li>
                        @endforeach
                    </ol>
                </div>
            </div>
            <div class="col-md">
                <div class="box">
                    <h3>Wer erhält am meisten öffentliche Aufträge?</h3>
                    <ol class="top-ten">
                        @foreach($topContractorsByCount as $topContractorByCount)
                            <li><a href="{{ route('public::lieferant',$topContractorByCount->id) }}">{{ ui_shorten($topContractorByCount->name,60) }}</a> ({{ $topContractorByCount->datasets_count }})</li>
                        @endforeach
                    </ol>
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