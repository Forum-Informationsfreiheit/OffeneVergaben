<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Datasource;
use App\Origin;
use Illuminate\Http\Request;

class EarlyBirdController extends Controller
{
    public function origins() {
        $origins = Origin::all();

        return view('earlybird.origins',compact('origins'));
    }

    public function datasets(Request $request) {

        $order = $request->has('orderBy') ? $request->input('orderBy') : 'offerors.name';
        $direction = $request->has('desc') ? 'desc' : 'asc';

        $query = Dataset::select([
            'datasets.*',
            'offerors.name as offeror_name',
            'offerors.national_id as offeror_national_id',
        ])->join('offerors', 'datasets.id', '=', 'offerors.dataset_id');
        $query->where('offerors.is_extra',0);
        $query->orderBy($order,$direction);

        $showAll = $request->has('showAll');
        if ($showAll) {
            $datasets = $query->get();
        } else {
            $datasets = $query->paginate(200);
        }

        $paramsString = "orderBy=$order" . ($direction == "desc" ? "&desc=1" : "");

        return view('earlybird.datasets',compact('datasets','showAll','paramsString'));
    }

    public function dataset($id, Request $request) {
        $showAll = $request->has('showAll');

        $dataset = Dataset::findOrFail($id);

        $fields = $this->getDatasetFieldDump($dataset);

        return view('earlybird.dataset',compact('dataset','fields','showAll'));
    }

    protected function getDatasetFieldDump($dataset) {

        $fields = [
            'Origin*' => $dataset->datasource->origin->name,
            'Version*' => 'v'.$dataset->version,
            'Art' => $dataset->type->toString(),
            'CPVs' => $dataset->cpvs->map(function($c) { return $c->toString(); })->toArray(),
            'NUTS' => $dataset->nuts_code,
            'Contracting Bodies' => $dataset->offerors->map(function($o) { return $o->toHtmlString(); })->toArray(),
            'Contractors' => $dataset->contractors->map(function($o) { return $o->toHtmlString(); })->toArray(),
            'Procedures' => $dataset->procedures->map(function($p) { return $p->name; })->toArray(),
            'URL document' => $dataset->url_document,
            'URL is restricted' => $this->formatBoolean($dataset->url_is_restricted),
            'URL participation' => $dataset->url_participation,
            'URL revocation' => $dataset->url_revocation,
            'URL revocation statement.' => $dataset->url_revocation_statement,
            'Contract type' => $dataset->contract_type,
            'Title' => $dataset->titleFormatted,
            'Description' => $dataset->descriptionFormatted,
            'Date start' => $this->formatDate($dataset->date_start),
            'Date end' => $this->formatDate($dataset->date_end),
            'Duration (days)' => $dataset->duration,
            'DT receipt tenders' => $this->formatDateTime($dataset->datetime_receipt_tenders),
            'Lot ?' => $this->formatBoolean($dataset->is_lot),
            'Framework ?' => $this->formatBoolean($dataset->is_framework),
            'Date conclusion contract' => $this->formatDate($dataset->date_conclusion_contract),
            'Tenders received' => $dataset->nb_tenders_received,
            'Number of SME tenders' => $dataset->nb_sme_tender,
            'Number of SME contractors' => $dataset->nb_sme_contractor,
            'Val total' => $dataset->valTotalFormatted,
            'Val total before' => $dataset->valTotalBeforeFormatted,
            'Val total after' => $dataset->valTotalAfterFormatted,
            'Info modifications' => $dataset->infoModificationsFormatted,
            'Justification' => $dataset->justificationFormatted,
            'Date first publication' => $this->formatDate($dataset->date_first_publication),
            'DT last change' => $this->formatDateTime($dataset->datetime_last_change),
            'Deadline standstill' => $this->formatDate($dataset->deadline_stanstill),
            'RD notification' => $this->formatBoolean($dataset->rd_notification),
            'Obj. Mod. title' => $dataset->ocm_title,
            'Obj. Mod. contract type' => $dataset->ocm_contract_type,
            'Procedure description' => $dataset->procedureDescriptionFormatted,
            'Threshold' => $this->formatBoolean($dataset->threshold),
            'Participants' => $dataset->nb_participants,
            'Participants SME' => $dataset->nb_participants_sme,
        ];

        return $fields;
    }

    protected function formatDate($date) {
        if (!$date) return null;

        if (is_string($date)) {
            dump("String instead of date received");
            dump($date);
            return $date;
        }

        return $date->format('d.m.Y');
    }
    protected function formatDateTime($dateTime) {
        if (!$dateTime) return null;

        return $dateTime->format('d.m.Y H:i');
    }
    protected function formatBoolean($bool) {
        if ($bool === null) {
            return null;
        }

        return $bool ? 'Ja' : 'Nein';
    }
}
