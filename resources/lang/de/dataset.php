<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | Everything Dataset
    */

    'domain' => [
        'federal' => 'Bundesebene',
    ],

    'contract_types' => [
        'SERVICES' => 'Dienstleistungsauftrag',
        'WORKS' => 'Bauauftrag',
        'SUPPLIES' => 'Lieferauftrag',
    ],

    'ocm_contract_types' => [
        'SERVICES' => 'Dienstleistungskonzession',
        'WORKS' => 'Baukonzession',
        'SUPPLIES' => 'Lieferkonzession',
    ],

    'val_total' => [
        'KD_7_2_Z1' => 'Wert der Konzession',
        'KD_8_2_Z1' => 'Auftragswert',
        'KD_8_2_Z2' => 'Preisgeld',
    ],

    'val_total_after' => [
        'KD_7_2_Z2' => 'Wert der Konzession zusätzliche Leistung',
        'KD_8_2_Z3' => 'Auftragswert zusätzliche Leistung',
    ],

    'val_total_before' => [
        'KD_7_2_Z2' => 'Wert der Konzession vor Veränderung',
        'KD_8_2_Z3' => 'Auftragswert vor Veränderung',
    ],

    'duration' => [
        'KD_7_1_Z1' => 'Laufzeit der Konzession',
        'KD_7_1_Z2' => 'Laufzeit der Konzession',
        'KD_7_2_Z1' => 'Vertragslaufzeit',
        'KD_8_1_Z1' => 'Bei Dauerschuldverhältnissen Laufzeit des Vertrages',
        'KD_8_1_Z2' => 'Bei Dauerschuldverhältnissen Laufzeit des Vertrages',
        'KD_8_1_Z4' => 'Bei Dauerschuldverhältnissen Laufzeit des Vertrages',
        'KD_8_2_Z1' => 'Vertragslaufzeit (Bei Dauerschuldverhältnissen oder Rahmenvereinbarungen)',
    ],

    'date_end' => [
        'KD_8_1_Z1' => 'Endzeitpunkt / Erfüllungszeitpunkt',
        'KD_8_1_Z2' => 'Endzeitpunkt / Erfüllungszeitpunkt',
        'KD_8_1_Z4' => 'Endzeitpunkt / Erfüllungszeitpunkt',
        'KD_8_2_Z1' => 'Erfüllungszeitpunkt (Bei Zielschuldverhältnissen)',
    ],

    // eForms notice subtypes (datasets.source_subtype), see App\DOMEforms. Official German names from the eForms SDK
    // 1.13 (translations/notice_de.xml, keys notice|name|<subtype>), see https://github.com/OP-TED/eForms-SDK
    'eforms_subtypes' => [
        '1' => 'Bekanntmachung der Veröffentlichung einer Vorinformation in einem Beschafferprofil – allgemeine Richtlinie',
        '2' => 'Bekanntmachung der Veröffentlichung einer regelmäßigen nicht verbindlichen Bekanntmachung in einem Beschafferprofil – Sektorenrichtlinie',
        '3' => 'Bekanntmachung der Veröffentlichung einer Vorinformation in einem Beschafferprofil – Richtlinie für Beschaffung im Bereich Verteidigung',
        '4' => 'Vorinformation nur zu Informationszwecken – allgemeine Richtlinie',
        '5' => 'Regelmäßige nicht verbindliche Bekanntmachung nur zu Informationszwecken – Sektorenrichtlinie',
        '6' => 'Vorinformation nur zu Informationszwecken – Richtlinie für Beschaffung im Bereich Verteidigung',
        '7' => 'Vorinformation zum Zweck der Verkürzung der Frist für den Eingang der Angebote – allgemeine Richtlinie',
        '8' => 'Regelmäßige nicht verbindliche Bekanntmachung zum Zweck der Verkürzung der Frist für den Eingang der Angebote – Sektorenrichtlinie',
        '9' => 'Vorinformation zum Zweck der Verkürzung der Frist für den Eingang der Angebote – Richtlinie für die Beschaffung im Bereich Verteidigung',
        '10' => 'Vorinformation als Aufruf zum Wettbewerb – allgemeine Richtlinie, Standardregelung',
        '11' => 'Regelmäßige nicht verbindliche Bekanntmachung als Aufruf zum Wettbewerb – Sektorenrichtlinie, Standardregelung',
        '12' => 'Vorinformation als Aufruf zum Wettbewerb – allgemeine Richtlinie, Sonderregelung',
        '13' => 'Regelmäßige nicht verbindliche Bekanntmachung als Aufruf zum Wettbewerb – Sektorenrichtlinie, Sonderregelung',
        '14' => 'Vorinformation als Aufruf zum Wettbewerb – Konzessionsrichtlinie, Sonderregelung',
        '15' => 'Bekanntmachung über das Bestehen eines Qualifizierungssystems – Sektorenrichtlinie',
        '16' => 'Auftragsbekanntmachung – allgemeine Richtlinie, Standardregelung',
        '17' => 'Auftragsbekanntmachung – Sektorenrichtlinie, Standardregelung',
        '18' => 'Auftragsbekanntmachung – Richtlinie für Beschaffung im Bereich Verteidigung, Standardregelung',
        '19' => 'Konzessionsbekanntmachung – Konzessionsrichtlinie, Standardregelung',
        '20' => 'Auftragsbekanntmachung – allgemeine Richtlinie, Sonderregelung',
        '21' => 'Auftragsbekanntmachung – Sektorenrichtlinie, Sonderregelung',
        '22' => 'Bekanntmachung über Unteraufträge – Richtlinie für Beschaffung im Bereich Verteidigung',
        '23' => 'Wettbewerbsbekanntmachung – allgemeine Richtlinie, Wettbewerb',
        '24' => 'Wettbewerbsbekanntmachung – Sektorenrichtlinie, Wettbewerb',
        '25' => 'Bekanntmachung für die Zwecke der freiwilligen Ex-Ante-Transparenz – allgemeine Richtlinie',
        '26' => 'Bekanntmachung für die Zwecke der freiwilligen Ex-Ante-Transparenz – Sektorenrichtlinie',
        '27' => 'Bekanntmachung für die Zwecke der freiwilligen Ex-Ante-Transparenz – Richtlinie für Beschaffung im Bereich Verteidigung',
        '28' => 'Bekanntmachung für die Zwecke der freiwilligen Ex-Ante-Transparenz – Konzessionsrichtlinie',
        '29' => 'Vergabebekanntmachung – allgemeine Richtlinie, Standardregelung',
        '30' => 'Vergabebekanntmachung – Sektorenrichtlinie, Standardregelung',
        '31' => 'Vergabebekanntmachung – Richtlinie für Beschaffung im Bereich Verteidigung, Standardregelung',
        '32' => 'Zuschlagsbekanntmachung – Konzessionsrichtlinie, Standardregelung',
        '33' => 'Vergabebekanntmachung – allgemeine Richtlinie, Sonderregelung',
        '34' => 'Vergabebekanntmachung – Sektorenrichtlinie, Sonderregelung',
        '35' => 'Zuschlagsbekanntmachung – Konzessionsrichtlinie, Sonderregelung',
        '36' => 'Bekanntmachung über das Ergebnis des Wettbewerbs – allgemeine Richtlinie, Wettbewerb',
        '37' => 'Bekanntmachung über das Ergebnis des Wettbewerbs – Sektorenrichtlinie, Wettbewerb',
        '38' => 'Bekanntmachung über Auftragsänderung – allgemeine Richtlinie',
        '39' => 'Bekanntmachung über Auftragsänderung – Sektorenrichtlinie',
        '40' => 'Bekanntmachung über Auftragsänderung – Konzessionsrichtlinie',
        'CEI' => 'Aufruf zur Interessenbekundung',
        'E1' => 'Freiwillige Bekanntmachung über vorherige Marktkonsultation',
        'E2' => 'Freiwillige Vorabinformation',
        'E3' => 'Freiwillige Auftragsbekanntmachung',
        'E4' => 'Freiwillige Vergabebekanntmachung',
        'E5' => 'Freiwillige Bekanntmachung über die Auftragsausführung',
        'E6' => 'Freiwillige Bekanntmachung einer Auftragsänderung',
        'T01' => 'Vorinformation für öffentliche Dienstleistungsaufträge',
        'T02' => 'Bekanntmachung über vergebene öffentliche Dienstleistungsaufträge',
    ]
];
