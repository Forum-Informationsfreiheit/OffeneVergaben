@extends('public.layouts.frontpage')

@section('body:class','frontpage')

@section('lead')
    <div class="container">
        @if(false) <!-- old beta text -->
        <p class="lead">
            OffeneVergaben.at ist ein zivilgesellschaftliches Projekt des <a class="lead-link" href="https://www.informationsfreiheit.at">Forum Informationsfreiheit</a> und derzeit noch in einer Test-Phase. Wir machen Auftragsvergaben der öffentlichen Hand über 50.000 Euro nachvollziehbar. Dafür verwenden wir seit März 2019 verfügbare <a href="https://www.data.gv.at/suche/?searchterm=&tagFilter_sub%5B%5D=Ausschreibung">offene Daten</a> der Auftraggeber. Über Feedback an <a href="mailto:info@offenevergaben.at">info@offenevergaben.at</a> freuen wir uns.
        </p>
        @endif
        <p class="lead">
            OffeneVergaben.at ist ein zivilgesellschaftliches Projekt des <a class="lead-link" href="https://www.informationsfreiheit.at">Forum Informationsfreiheit</a>. Wir machen Auftragsvergaben der öffentlichen Hand über 50.000 Euro nachvollziehbar. Dafür verwenden wir seit März 2019 verfügbare <a href="https://www.data.gv.at/suche/ausschreibungen-laut-bvergg2018">offene Daten</a> der Auftraggeber. Über Feedback an <a href="mailto:info@offenevergaben.at">info@offenevergaben.at</a> freuen wir uns.
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
                                    <li><a href="{{ route('public::show-auftraggeber',$topOfferorByCount->id) }}" title="{{ $topOfferorByCount->name }}">{{ ui_shorten($topOfferorByCount->name,58) }}</a> ({{ $topOfferorByCount->datasets_count }})</li>
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
                                    <li><a href="{{ route('public::lieferant',$topContractorByCount->id) }}" title="{{ $topContractorByCount->name }}">{{ ui_shorten($topContractorByCount->name,56) }}</a> ({{ $topContractorByCount->datasets_count }})</li>
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

@section('news')
    <div class="container">
        <div class="row">
            <div class="col">
                <h3 class="mb-3">
                    Neuigkeiten
                </h3>
            </div>
        </div>
        <div class="row">
            @foreach($posts as $post)
            <div class="col-md-4">
                <div class="teaser-wrapper">
                    <div class="teaser-meta">
                        {{ $post->published_at->format('d.m.Y') }}
                    </div>
                    @if($post->image)
                    <a href="{{ route('public::show-post',$post->slug) }}">
                        <img class="teaser-image mb-3" src="{{ url($post->image) }}">
                    </a>
                    @endif
                    <h4>{{ $post->title }}</h4>
                    <div class="teaser-text" title="{!! strip_tags($post->summary) !!}">
                        {!! ui_shorten(strip_tags($post->summary),160) !!}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
@stop

@section('features')
    <div class="container">
        <div class="row">
            <div class="col">
                <h3>
                    OffeneVergaben.at bietet folgende Features
                </h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                        <a href="{{ route('public::auftraege') }}">
                           <span class="mx-auto icon-wrapper filter mb-4">@svg('img/icons/filter.svg','filter')</span>
                        </a>
                    </div>
                    <h4>Filter & Visualisierungen</h4>
                    <p>Such- und Filtermöglichkeiten</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                        <a href="{{ url('neuigkeiten/offenevergabenat-neue-filter-und-email-benachrichtigungen') }}">
                            <span class="mx-auto icon-wrapper notification mb-4">@svg('img/icons/benachrichtigung_w.svg','notification')</span>
                        </a>
                    </div>
                    <h4>Benachrichtigungen</h4>
                    <p>per Email individuelle Updates erhalten</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-wrapper">
                    <div class="icon justify-content-center">
                        <a href="{{ route('public::downloads') }}">
                            <span class="mx-auto icon-wrapper export mb-4">@svg('img/icons/export_w.svg','export')</span>
                        </a>
                    </div>
                    <h4>Daten-Export</h4>
                    <p>für tiefergehende Analysen</p>
                </div>
            </div>
        </div>
    </div>
@stop