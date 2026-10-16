# eForms SDK extracts

Extracts of the [eForms SDK](https://github.com/OP-TED/eForms-SDK), one directory per SDK minor version (as named by `cbc:CustomizationID` in the notices, e.g. `eforms-sdk-1.13`). Used by `App\EformsFurtherInformation` for the "further information" of eForms datasets: fields that the app has no column for, with their official German names.

- `fields.json`: fields (business terms) with XPath, type, business entity, code list; repeating business entities with the XPath of their node
- `labels-de.json`: German names of fields, business entities and codes

Generated with `php artisan eforms:import-sdk <path to the SDK>`; don't edit by hand. To add a new SDK version, download its release from GitHub and run the command; notices of a version without directory fall back to the newest one.

The eForms SDK is © European Union, Publications Office of the European Union, licensed under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/). The files here are extracts, converted to JSON.
