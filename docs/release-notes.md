# Android Debloat List v0.1.0

This fork now has a searchable offline reference and a README that explains the data before you use it. The download includes all 5,481 package records, the JSON source and license notices.

Package-removal classifications haven't changed. The dataset remains the fork's 2026-05-14 snapshot, with missing labels and warning text disclosed in the reference.

## Checked locally

- 45 PHP regression checks and ten packaging tests pass.
- All 5,481 package pages are present, including six IDs that differ from another record only by capitalization.
- 144,228 local links resolve in the offline package.
- The extracted reference passes 15 interaction checks. Search opens the correct record, the light theme survives reload, and offline browsing makes no HTTP requests.
- Selected screenshots come from the extracted reference. Earlier captures and all 71 original source files are preserved in the concept archive.

The reference cannot inspect or modify a phone. No device was debloated, no replacement app was installed, and no recommendation was retested across Android ROMs for this release. External references and the App Manager handoff were not opened during acceptance testing.

The offline ZIP is a static document bundle, not a signed installer. Its checksums verify the downloaded bytes; they aren't a software-signing certificate. No separate hosted website or store listing was deployed.
