# How to read an entry

Use the full package ID to identify an app. Its display name may vary by language or device. This reference falls back to the ID when a label hasn't been recorded.

Three pairs of recorded IDs differ only by capitalization. They're kept as separate entries. Their page filenames include a short suffix so that Windows can store both; the displayed package IDs are unchanged.

## Classifications

| Label | Stored value | Meaning |
| --- | --- | --- |
| Listed for removal | `delete` | The entry recommends removal. Check whether that recommendation fits your device. |
| Replacement needed | `replace` | Review the functions you'll need to replace. A matching suggestion may not exist. |
| Caution | `caution` | Investigate the notes and device-specific dependencies before changing anything. |
| Unsafe to remove | `unsafe` | Keep the package installed unless you have specific evidence and a recovery plan. |

These are recorded assessments, not results from a test on your phone.

## Recorded facts and general guidance

A package page separates **Recorded notes** from the general risk message shown above them. If an entry has a warning, it's shown under **Recorded warning**.

The v0.1.0 snapshot has 4,074 records without a readable label. There are 278 unsafe records without a separate warning and 68 records with an empty description. Those gaps remain visible; the reference doesn't fill them with guesses.

Dependencies appear only when the record supplies them. An absent list doesn't prove that other apps are independent of this package. A dependency not found in the dataset is shown as text, not a broken link.

## Replacement notes

A replacement list may be empty. Listed alternatives and store codes are historical notes. Check current availability and the project's maintenance before installing an alternative. Inclusion is not an endorsement.

## External links

Recorded references open their source websites. **Open in App Manager** hands the package ID to a compatible app if one is installed on the device. The reference itself has no device permissions.

Browsing and search work locally with JavaScript enabled. Nothing is uploaded by this reference. External links require a connection and are governed by the destination's own behavior.

## Contribute evidence

Report the exact package ID, your ROM and version, and what you observed. Separate direct observations from assumptions. Don't include account data, device identifiers or private logs.

[Report a package correction](https://github.com/SysAdminDoc/android-debloat-list/issues/new/choose) in this fork, or read the [upstream project](https://github.com/MuntashirAkon/android-debloat-list) when contributing upstream.
