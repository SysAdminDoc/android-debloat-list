# Contributing

This is the SysAdminDoc fork of [Android Debloat List](https://github.com/MuntashirAkon/android-debloat-list). Submit a correction here through [the issue forms](https://github.com/SysAdminDoc/android-debloat-list/issues/new/choose). To contribute to the original project, use its own issue tracker.

Read the [data format](docs/schema.md) before editing. Minor wording fixes still need to preserve the record's meaning. New packages and changed removal assessments need supporting evidence.

## Add or correct a package

Use the exact package ID. Include the device model and ROM version, the package's observed purpose, and sources that support the proposed change. Keep personal identifiers and private account data out of reports.

Choose the existing category that fits: `aosp.json`, `carrier.json`, `google.json`, `misc.json` or `oem.json`. Keep new entries in ID order and don't duplicate an ID across files. Some older imports aren't sorted; don't reorder unrelated records.

Add a readable label when known. For an unsafe classification, include a warning that explains the consequence when you have evidence. Don't invent details to fill an empty field. The existing snapshot has missing labels and warnings, which the reference discloses.

Run the local linter and tests described in [Build and verify](docs/development.md). Structural checks don't replace a device-specific investigation.

## Replacement suggestions

The filename in `suggestions/` is the suggestion ID. A category can be referenced by several packages, so keep it short and relevant. Use ASCII names without spaces. Make a separate proposal for each suggested app and explain why it improves the existing choice.

The upstream criteria remain:

- Prefer an existing suggestion if it already offers comparable or better functions. Keep the number of alternatives small.
- The source and software must be free/libre and open source under an OSI-approved license. Non-free assets need an explanation in `reason`.
- Check whether the interface and required functions can replace the original app. Explain important gaps.
- Crucial apps, including messaging and password tools, require review evidence. If an unaudited alternative is listed, its `reason` needs an appropriate warning.
- Suggested apps shouldn't include trackers. Any justified exception needs a warning, and optional crash reporting should be off by default.
- Inclusion isn't an endorsement or certification. Suggested apps must not advertise their inclusion in this list; those entries will be removed.

The upstream contribution policy specifically requires formal review for `app_stores`, `email_clients` and `vpn_services`. Don't claim a suggestion passed that review unless you can link to the evidence.
