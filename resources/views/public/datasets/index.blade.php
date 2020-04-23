@extends('public.layouts.default')

@section('body:class','datasets')

@section('page:content')
    <div class="row">
        <div class="col">
            <h1 class="page-title">
                Aufträge
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div id="filterWrapper" class="filter-wrapper {{-- $filters->hasAny() ? '' : 'collapsed' --}}">
                <div class="filter-head">
                    <a href="#" id="filterToggle" class="filter-toggle" data-status="hidden">
                <span class="icon-wrapper filter">
                    @svg('/img/icons/filter.svg','filter')
                </span>
                        <span class="action-text">Ergebnisse einschränken</span>
                    </a>
                </div>
                <div class="filter-body">
                    @include('public.datasets.partials.filter')
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="float-left results-meta">
                {{-- Auf hunderter runden --}}
                <span class="count">{{ $totalItems > 100 ? 'ungefähr ' . round($totalItems,-2) : $totalItems }} Ergebnisse</span>
            </div>
            <div class="float-right">
                @svg('/img/icons/benachrichtigung.svg','subscribe')&nbsp;&nbsp;
                @if( $filters->hasAny() )
                    <a id="subscribeToggle" href="#">Benachrichtigung aktivieren</a>
                    @else
                    <span class="text-muted">Benachrichtigung aktivieren</span>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div id="subscribeWrapper" class="subscribe-wrapper {{ $errors->subscription->isEmpty() ? 'collapsed' : '' }}">
                <div class="subscribe-head">
                    <span class="icon-wrapper subscribe">
                        @svg('/img/icons/benachrichtigung.svg','subscribe')&nbsp;&nbsp;
                    </span>
                    <span class="action-text">Benachrichtigung einrichten</span>
                </div>
                <div class="subscribe-body">
                    @if($errors->subscription->any())
                        <div class="alert alert-danger">
                            <ul class="m-0">
                                @foreach ($errors->subscription->all() as $subError)
                                    <li>{{ $subError }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form id="subscribeForm" method="POST" action="{{ route('public::subscribe') }}">
                        <div class="row">
                            <div class="col-md-12">
                                <p>
                                    <span style="color: blue">TODO</span> Kurzer Erklärungstext... Tägliche Benachrichtigung via Email, Bestätigen des Abonnements etc.
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subscribeTitle" style="display: none;">Bezeichnung</label>
                                    <input name="title" type="text" class="form-control {{ $errors->subscription->has('title') ? 'is-invalid' : '' }}" id="subscribeTitle" aria-describedby="subscribeTitleHelp" placeholder='z.B. "Aufträge ab 1 Mio. €"' value="{{ old('title') }}">
                                    <small id="subscribeTitleHelp" class="form-text text-muted">Aussagekräfitge Bezeichnung</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subscribeEmail" style="display: none;">Email</label>
                                    <input name="email" type="email" class="form-control {{ $errors->subscription->has('email') ? 'is-invalid' : '' }}" id="subscribeEmail" aria-describedby="subscribeEmailHelp" placeholder='z.B. "marlene.musterfrau@provider.at"' value="{{ old('email') }}">
                                    <small id="subscribeEmailHelp" class="form-text text-muted">Benachrichtigungen an diese Email Adresse schicken</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input name="confirm" class="form-check-input {{ $errors->subscription->has('confirm') ? 'is-invalid' : '' }}" {!! old('confirm') ? 'checked="checked"' : '' !!} type="checkbox" value="1" id="subscribeCheck" autocomplete="off">
                                    <label class="form-check-label" for="subscribeCheck">
                                        Ich habe die Datenschutzerklärung gelesen und stimme dieser zu
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                {{ csrf_field() }}
                                <button class="btn btn-primary mt-3" type="submit">Aktivieren</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table class="table ov-table table-sm table-bordered table-striped">
                <thead>
                <tr>
                    <th>
                        Bezeichnung
                        @include('public.datasets.partials.sort',['field' => 'title'])
                    </th>
                    <th>
                        Auftraggeber
                        @include('public.datasets.partials.sort',['field' => 'offeror'])
                    </th>
                    <th>
                        Lieferant
                        @include('public.datasets.partials.sort',['field' => 'contractor'])
                    </th>
                    <th>
                        Bieter
                        @include('public.datasets.partials.sort',['field' => 'nb_tenders_received'])
                    </th>
                    <th>
                        Summe
                        @include('public.datasets.partials.sort',['field' => 'val_total'])
                    </th>
                    <th>
                        Aktualisiert
                        @include('public.datasets.partials.sort',['field' => 'item_lastmod'])
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $item)
                    <tr data-id="{{ $item->id }}">
                        <td class="name">
                            <a href="{{ route('public::auftrag',$item->id) }}">{{ $item->title }}</a>
                        </td>
                        <td class="name">
                            <a href="{{ route('public::show-auftraggeber',$item->offeror->organization_id) }}">{{ $item->offeror->name }}</a>
                            @if($item->offerors_count > 1)
                                <span class="badge badge-pill badge-light">+&nbsp;{{ $item->offerors_count -1 }} weitere Auftraggeber</span>
                            @endif
                        </td>
                        <td class="name">
                            @if($item->contractor)
                                <a href="{{ route('public::lieferant',$item->contractor->organization_id) }}">{{ $item->contractor->name }}</a>
                                @if($item->contractors_count && $item->contractors_count > 1)
                                    <span class="badge badge-pill badge-light">+&nbsp;{{ $item->contractors_count -1 }} weitere Lieferanten</span>
                                @endif
                                @else
                                &nbsp;
                            @endif
                        </td>
                        <td class="nb">{{ $item->nb_tenders_received }}</td>
                        <td class="value">{{ $item->valTotalFormatted }}</td>
                        {{-- <td class="date" title="{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y h:i') : '' }}">{{ $item->datetime_last_change ? $item->datetime_last_change->format('d.m.Y') : '' }}</td> --}}
                        <td class="date" title="{{ $item->item_lastmod ? $item->item_lastmod->format('d.m.Y h:i') : '' }}">{{ $item->item_lastmod ? $item->item_lastmod->format('d.m.Y') : '' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination-wrapper">
                {{ $data->appends(request()->query())->links('public.partials.pagination', [ 'ulClass' => [ "mx-auto", "justify-content-center" ] ]) }}
            </div>
        </div>
    </div>
@stop

@section('body:append')
    <script>
        var $filterRoot   = $('#filterWrapper');
        var $filterToggle = $('#filterToggle');
        $filterToggle.on('click',function() {
            $filterRoot.toggleClass('collapsed');
        });

        var $subscribeRoot =  $('#subscribeWrapper');
        var $subscribeToggle = $('#subscribeToggle');
        $subscribeToggle.on('click',function(evt) {
            $subscribeRoot.toggleClass('collapsed');

            evt.preventDefault();
            return false;
        });
    </script>
@stop