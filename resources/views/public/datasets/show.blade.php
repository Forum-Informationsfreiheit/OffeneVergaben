@extends('public.layouts.default')

@section('body:class','dataset')

@section('page:content')
    <h1 class="page-title">
        <!-- auftrag oder ausschreibung ??? -->
        {{ $dataset->title }}
    </h1>
    <div class="row">
        <div class="col">
            <table class="table ov-table ov-table-vertical table-sm table-bordered">
                <tbody>
                    <tr>
                        <th>Bezeichnung</th>
                        <td>{{ $dataset->title }}</td>
                    </tr>
                    <tr>
                        <th>Kategorie (CPV Hauptteil)</th>
                        <td>{{ $dataset->cpv->toString() }}</td>
                    </tr>
                    @if($dataset->nuts)
                    <tr>
                        <th>NUTS</th>
                        <td>{{ $dataset->nuts->toString() }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Auftraggeber</th>
                        <td><a href="{{ route('public::show-auftraggeber',$dataset->offeror->organization_id) }}">{{ $dataset->offeror->name }}</a></td>
                    </tr>
                    @if($dataset->contractors)
                    <tr>
                        <th>Lieferant</th>
                        <td>
                            <ul>
                            @foreach($dataset->contractors as $contractor)
                                <li>
                                    <a href="{{ route('public::lieferant',$contractor->organization_id) }}">{{ $contractor->name }}</a>
                                </li>
                            @endforeach
                            </ul>
                        </td>
                    </tr>
                    @endif
                    @if($dataset->procedures)
                    <tr>
                        <th>Verfahrensart</th>
                        <td>
                            {{ procedure_label($dataset->procedures->pluck('code')->toArray()) }}
                        </td>
                    </tr>
                    @endif
                    @if($dataset->url_document)
                    <tr>
                        <th>URL</th>
                        <td><a target="_blank" href="{{ $dataset->url_document }}">{{ $dataset->url_document }}</a></td>
                    </tr>
                        <!-- todo: other urls like participation and revocation ??? -->
                    @endif
                    @if($dataset->contract_type)
                        <tr>
                            <th>Art des Auftrags</th>
                            <td>{{ __('dataset.contract_types.'.$dataset->contract_type) }}</td>
                        </tr>
                    @endif
                    @if($dataset->description)
                        <tr>
                            <th>Beschreibung</th>
                            <td>{{ $dataset->description }}</td>
                        </tr>
                    @endif
                    @if($dataset->date_start)
                        <tr>
                            <th>Angabe des geplanten Ausführungsbeginns</th>
                            <td>{{ $dataset->date_start->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->date_end)
                        <tr>
                            <th>{{ $dataset->label('date_end') }}</th>
                            <td>{{ $dataset->date_end->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->duration)
                        <tr>
                            <th>{{ $dataset->label('duration') }}</th>
                            <td>{{ $dataset->duration }}</td>
                        </tr>
                    @endif
                    @if($dataset->datetime_receipt_tenders)
                        <tr>
                            <th>Schlusstermin für den Eingang</th>
                            <td>{{ $dataset->datetime_receipt_tenders->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->is_lot)
                        <tr>
                            <th>Aufgeteilt in Lose</th>
                            <td>ja</td>
                        </tr>
                    @endif
                    @if($dataset->is_framework)
                        <tr>
                            <th>Rahmenvereinbarung</th>
                            <td>ja</td>
                        </tr>
                    @endif
                    @if($dataset->date_conclusion_contract)
                        <tr>
                            <th>Tag Vertragsabschluss</th>
                            <td>{{ $dataset->date_conclusion_contract->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->nb_tenders_received)
                        <tr>
                            <th>Anzahl eingegangener Angebote</th>
                            <td>{{ $dataset->nb_tenders_received }}</td>
                        </tr>
                    @endif
                    @if($dataset->nb_sme_tender)
                        <tr>
                            <th>Anzahl eingegangener Angebote (KMU)</th>
                            <td>{{ $dataset->nb_sme_tender }}</td>
                        </tr>
                    @endif
                    @if($dataset->nb_sme_contractor)
                        <tr>
                            <th>Angabe/Anzahl KMU der Auftragnehmer ist</th>
                            <td>{{ $dataset->nb_sme_contractor }}</td>
                        </tr>
                    @endif
                    @if($dataset->val_total)
                        <tr>
                            <th>{{ $dataset->label('val_total') }}</th>
                            <td>{{ $dataset->valTotalFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->val_total_before)
                        <tr>
                            <th>{{ $dataset->label('val_total_before') }}</th>
                            <td>{{ $dataset->valTotalBeforeFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->val_total_after)
                        <tr>
                            <th>{{ $dataset->label('val_total_after') }}</th>
                            <td>{{ $dataset->valTotalAfterFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->infoModificationsFormatted)
                        <tr>
                            <th>Gründe Notwendigkeit</th>
                            <td>{{ $dataset->infoModificationsFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->justificationFormatted)
                        <tr>
                            <th>Beschreibung der maßgeblichen Gründe</th>
                            <td>{{ $dataset->justificationFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->date_first_publication)
                        <tr>
                            <th>Tag der erstmaligen Verfügbarkeit</th>
                            <td>{{ $dataset->date_first_publication->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->datetime_last_change)
                        <tr>
                            <th>Letzte Änderung der Ausschreibung</th>
                            <td>{{ $dataset->datetime_last_change->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->deadline_standstill)
                        <tr>
                            <th>Ende der Stillhaltefrist bei Widerrufsentscheidung</th>
                            <td>{{ $dataset->deadline_standstill->format('d.m.Y') }}</td>
                        </tr>
                    @endif
                    @if($dataset->rd_notification)
                        <tr>
                            <th>Auf dem Gebiet der Forschung und Entwicklung</th>
                            <td>ja</td>
                        </tr>
                    @endif
                    @if($dataset->ocm_title)
                        <tr>
                            <th>Bezeichnung der/des Konzession/Auftrages</th>
                            <td>{{ $dataset->ocm_title }}</td>
                        </tr>
                    @endif
                    @if($dataset->ocm_contract_type)
                        <tr>
                            <th>Art der Konzession</th>
                            <td>{{ $dataset->ocm_contract_type }}</td>
                        </tr>
                    @endif
                    @if($dataset->procedureDescriptionFormatted)
                        <tr>
                            <th>Beschreibung Verfahrensablauf</th>
                            <td>{{ $dataset->procedureDescriptionFormatted }}</td>
                        </tr>
                    @endif
                    @if($dataset->threshold != null)
                        <tr>
                            <th>Angabe ob Auftragswert OSB oder USB</th>
                            <td>{{ $dataset->threshold === true ? 'Oberschwellenbereich (OSB)' : 'Unterschwellenbereich (USB)' }}</td>
                        </tr>
                    @endif
                    @if($dataset->nb_participants)
                        <tr>
                            <th>Anzahl Teilnehmer</th>
                            <td>{{ $dataset->nb_participants }}</td>
                        </tr>
                    @endif
                    @if($dataset->nb_participants_sme)
                        <tr>
                            <th>Anzahl KMU</th>
                            <td>{{ $dataset->nb_participants_sme }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('body:append')
@stop