<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Volltextindizes fuer die Suche auf /aufträge.
 *
 * Vorher lief die Suche ueber LIKE "%wort%" auf title, description und den Namen in
 * offerors/contractors. Fuehrende Wildcards schliessen jeden Index aus, jede Suche hat
 * also alle drei Tabellen komplett gelesen. Mit den Volltextindizes und der UNION-Form
 * in DatasetFilter::search() faellt count(*) von ~1.8s auf ~0.4s.
 *
 * Achtung, die Trefferdefinition aendert sich: MATCH sucht wortweise (mit "*" als
 * Prefix), LIKE hat Teilzeichenketten gefunden. Bei "wiener" waren das auf dem
 * Benchmark-Stand 5234 statt 5250 Datensaetze.
 */
class AddFulltextSearchIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE datasets    ADD FULLTEXT ft_title_desc (title, description)');
        DB::statement('ALTER TABLE offerors    ADD FULLTEXT ft_name (name)');
        DB::statement('ALTER TABLE contractors ADD FULLTEXT ft_name (name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE contractors DROP INDEX ft_name');
        DB::statement('ALTER TABLE offerors    DROP INDEX ft_name');
        DB::statement('ALTER TABLE datasets    DROP INDEX ft_title_desc');
    }
}
