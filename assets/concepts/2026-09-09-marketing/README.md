# Reference presentation archive

This folder preserves the evidence behind the v0.1.0 README and browser update.

- `original-source-3035d9c.zip` contains all 71 tracked files at commit `3035d9ca1336fb014b67ca0688e8ddd7591fc963`.
- `originals-index.json` records their exact Git-blob sizes and SHA-256 hashes.
- `baseline/` contains seven genuine headless captures of the original reference presentation. The removed `multilingual` configuration key was omitted only to let mdBook 0.5.4 build it. Text, layout and data were otherwise unchanged.
- Every subsequent capture attempt is retained in its own numbered folder. Verification files state the source and capture conditions.

## Identity decision

Retain a text-led identity for this reference project. It isn't an Android app, and an app-style shield or device-cleaner logo would imply a product this repository doesn't ship. The standard book favicon remains a navigation aid, not a new project logo.

The selected marketing images must be real views of the packaged reference, with readable text and clear risk labels. No generated interface mockup or invented device result is used.

## Review record

- `browser-r1/` retains the first seven redesigned views. The narrow dark capture had a mixed-theme code background caused by the capture setup, so this attempt wasn't selected.
- `browser-r2/` retains seven corrected-theme views from the local reference build.
- `browser-r3/` contains seven captures from the verified, extracted offline ZIP. The overview and unsafe-package views are selected without image editing; their hashes are recorded in `selection.json`.
- `acceptance-r1/` records five views and 15 interaction checks, including actual search, saved theme and all six capitalization-sensitive records. No network request or device action occurred.
- `readme-review-r1/` and `readme-review-r2/` contain dark/light desktop and narrow README reviews, plus the safety and source sections. The second attempt groups badges on one line. These are GitHub Markdown API renders with a local review stylesheet, not screenshots of a live GitHub page.

The two selected screenshots explain what the reference does and how to read an unsafe entry. Both are readable at README width and match the shipped browser layout. The README now distinguishes this fork from upstream, names the dataset date and leads with a usable offline download.

Build checks also caught obsolete mdBook settings, multiline HTML parsing, an offline 404 link, a skipped license chapter and Windows text-decoding behavior. The old generator overwrote three case-only package pairs on Windows. Distinct output paths now preserve all 5,481 records without changing the source package IDs.
