# Upgrading

Steps needed on existing installations when updating the app. Newest changes first.

## 2026-10: eForms notices

Since October 2026 some publishers (so far vemap and Monopolverwaltung) publish EU eForms notices instead of Kerndaten. `fif:process` now reads both formats (`app/DOMEforms.php` next to `app/DOMKerndaten.php`) and stores them in the same tables.

### Run the migrations

```sh
php artisan migrate --force
```

The production entrypoint does this itself if `RUN_MIGRATIONS=true` is set. The migrations:

- add `datasets.source_format` (`kerndaten` or `eforms`), `datasets.source_subtype` (the eForms notice subtype, e.g. `29` or `E4`; empty for Kerndaten) `datasets.further_information` (JSON, see below) and `datasets.search_text` with a full-text index (lot titles and descriptions of eForms notices, searched by the dataset search). eForms notices also get a `KD_*` type, the subtype keeps the more specific eForms type. Existing datasets get `kerndaten`. MySQL rebuilds `datasets` for this (it has full-text indexes, so the columns can't be added instantly): about a minute for 260,000 datasets on a dev machine, during which the table can't be written. Run it while `fif:process` is not running.
- add the procedure codes `OTH_SINGLE`, `OTH_MULT`, `COMP_TEND` and `EXP_INT_RAIL` to `procedures`. eForms gives no Austrian procedure type, only "other single-stage" or "other two-stage procedure" (`oth-single`, `oth-mult`); `comp-tend` and `exp-int-rail` are procedures for public passenger transport (regulation 1370/2007) without Kerndaten equivalent. Without these rows, eForms datasets can't be stored (foreign key of `dataset_procedure`).

Deploy the migrations before `fif:process` runs with the new code.

### Records that failed before are processed on the next run

`fif:process` processes all kerndaten records that have not been processed, so nothing has to be triggered by hand:

- eForms records stopped the whole processing run with a `TypeError`, so records after them were not processed either.
- AGES records start with an XML comment and could not be parsed at all.

Expect a larger first run. Records of other, unknown formats are skipped with a warning in `storage/logs/processor-<date>.log`.

### Further information of eForms datasets

eForms notices contain much more than the app has columns for (addresses, estimated values, award criteria, …). Everything the parser doesn't map is stored in `datasets.further_information`, with the official German names of the eForms SDK, and shown on the dataset page under "Weitere Angaben aus der Bekanntmachung".

The names come from extracts of the SDK in `resources/eforms-sdk/<version>/` (see the README there). Nothing to do on deployment.

### Updating existing datasets from their xml

When the parser improves, existing datasets can be updated in place from the xml in the scraper database:

```sh
php artisan fif:update-from-xml --further-information --dry-run    # show what would change
php artisan fif:update-from-xml --further-information              # all eForms datasets
php artisan fif:update-from-xml --offerors --dataset_id=42         # contracting bodies of one dataset
php artisan fif:update-from-xml --offerors --format=all            # of all datasets (takes a while)
```

- `--further-information` re-creates `datasets.further_information` and `datasets.search_text` (eForms only), e.g. after more fields got mapped or a new SDK version was imported.
- `--deadline` re-derives `datasets.datetime_receipt_tenders`. eForms notices of two-stage procedures only give a deadline for requests to participate (BT-1311), which is used since this version.
- `--offerors` re-derives the contracting bodies: national id, contact data, reference number, and the organization they are linked to (same matching as `fif:process`). Example: EVN's eForms notices give the buyer's company register number only as endpoint (BT-509), which is used since this version.

Unlike re-running `fif:process`, this creates no new dataset version: datasets keep their ids and urls, and subscribers are not notified again. Use `--dry-run` first; a dry run doesn't create organizations either. If organization links change, the command lists the affected organizations, whose statistics then need `php artisan fif:update-organization-stats --organization=<id>`.

Not needed for this deployment: there are no eForms datasets on live yet.

### Unparsed elements are logged

For each record, elements and attributes that the parser does not read are logged as a warning (`… record contains unparsed elements`) with their XPaths. For eForms notices that list is long (addresses, review bodies, values per lot, …) and shows what could still be mapped.
