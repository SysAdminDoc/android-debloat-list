# Data format

Package records live in five UTF-8 JSON arrays. An ID must be unique across all five files. This fork uses the Android Debloat List format, not the UAD-NG format.

| Field | Type | Meaning |
| --- | --- | --- |
| `id` | string, required | Exact Android package identifier. |
| `description` | string, required | Research notes about the package. |
| `removal` | string, required | One of the four recorded classifications below. |
| `label` | string, optional | Readable English name. The browser falls back to the ID. |
| `dependencies` | string array, optional | Packages this record says the app depends on. |
| `required_by` | string array, optional | Packages this record says depend on the app. |
| `warning` | string, optional | Additional consequences or qualifications. |
| `web` | string array, optional | References and investigation links. |
| `suggestions` | string, optional | Filename without `.json` in `suggestions/`. |
| `tags` | string array, optional | Reserved categories. No tags are currently defined. |
| `suppress` | string, optional | Linter exceptions, currently `LabelSameAsId`. |

The formal schemas are [bloatware_list.json](../schema/bloatware_list.json) and [suggestion_list.json](../schema/suggestion_list.json). Labels are optional in both the data contract and the browser. The initial schema incorrectly required a label even though most imported records have none.

## Read classifications as research, not instructions

| Stored value | Browser wording | How to read it |
| --- | --- | --- |
| `delete` | Listed for removal | The entry recommends removal. It isn't a guarantee for your device. |
| `replace` | Replacement needed | Check the replacement's required functions before considering a change. |
| `caution` | Caution | Read the notes and investigate dependencies first. |
| `unsafe` | Unsafe to remove | Keep it installed unless you have device-specific evidence and a recovery plan. |

The v0.1.0 data snapshot contains 278 `unsafe` records without a separate `warning`. The browser supplies a clearly labelled general warning, not invented package-specific findings. Missing dependencies don't mean that an app has none.

## Replacement suggestions

Each file in `suggestions/` is an array. The filename is its suggestion ID.

| Field | Type | Meaning |
| --- | --- | --- |
| `id` | string, required | Suggested app's package identifier. |
| `label` | string, required | Readable app name. |
| `repo` | string, required | Source repository link. |
| `reason` | string, optional | Limitations or reasons for an exception. |
| `source` | string, optional | Store codes: `f` F-Droid, `g` Google Play, `a` Amazon Appstore, `s` Samsung Galaxy Store. |

Store codes are historical hints, not proof that an app is still available. A suggestion is not an endorsement. Review its current source and maintenance status before installing it.

The inherited suggestion criteria are retained in [CONTRIBUTING.md](../CONTRIBUTING.md).
