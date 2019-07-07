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

    public function datasets() {
        $datasets = Dataset::current()->orderBy('val_total','desc')->get();

        //dd(count($sources));

        //dd($sources[0]->dataset);

        return view('earlybird.datasets',compact('datasets'));
    }

    public function dataset($id, Request $request) {
        $showAll = $request->has('showAll');

        $dataset = Dataset::findOrFail($id);

        $fields = $this->getDatasetFieldDump($dataset);

        return view('earlybird.dataset',compact('dataset','fields','showAll'));
    }

    protected function getDatasetFieldDump($dataset) {

        $fields = [
            'Origin' => $dataset->datasource->origin->name,
            'Version' => 'v'.$dataset->version,
            'Art' => $dataset->type->toString(),
            'CPVs' => $dataset->cpvs->map(function($c) { return $c->toString(); })->toArray(),
            'NUTS' => $dataset->nuts_code,
            'Contracting Bodies' => $dataset->offerors->map(function($o) { return $o->toHtmlString(); })->toArray(),
            'Contractors' => $dataset->contractors->map(function($o) { return $o->toHtmlString(); })->toArray(),
            'Procedures' => $dataset->procedures->map(function($p) { return $p->name; })->toArray(),
            'URL document' => $dataset->url_document,
            'URL is restricted' => $dataset->url_is_restricted,
            'URL participation' => $dataset->url_participation,
            'URL revocation' => $dataset->url_revocation,
            'URL revocation statement.' => $dataset->url_revocation_statement,
            'Contract type' => $dataset->contract_type,
            'Title' => $dataset->title,
            'Description' => $dataset->description,
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
            'Info modifications' => $dataset->info_modifications,
            'Justification' => $dataset->justification,
            'Date first publication' => $this->formatDate($dataset->date_first_publication),
            'DT last change' => $this->formatDateTime($dataset->datetime_last_change),
            'Deadline standstill' => $this->formatDate($dataset->deadline_stanstill),
            'RD notification' => $this->formatBoolean($dataset->rd_notification),
            'Obj. Mod. title' => $dataset->ocm_title,
            'Obj. Mod. contract type' => $dataset->ocm_contract_type,
            'Procedure description' => $dataset->procedure_description,
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
