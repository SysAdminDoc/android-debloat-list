# Build and verify

This is a data repository with a static reference browser. It doesn't build an APK or control an Android device.

## Tools

- [PHP](https://www.php.net/downloads.php) 8.2 or later. This release was checked with PHP 8.5.10.
- [mdBook](https://rust-lang.github.io/mdBook/guide/installation.html) 0.5.4.
- Python 3.11 or later for the standard-library release builder.

Install the tools explicitly. No script installs software at runtime, and no hosted workflow is required.

```sh
php scripts/lint.php
php scripts/test.php
python scripts/build.py
```

If the tools aren't on PATH, use `python scripts/build.py --php /path/to/php --mdbook /path/to/mdbook`. On Windows, pass paths to the corresponding `.exe` files.

The build checks inputs before cleaning its own generated directories. It removes only `browser/src/`, `browser/book/` and `dist/`, refuses symbolic links, then generates the reference and writes a versioned offline ZIP. Tests also run before generation. Linter diagnostics are saved in `build/lint-results.txt`; build output is in `build/release-build.log`.

## Run the reference

Extract the offline ZIP, then open `reference/index.html` in a browser. The bundled search works from local files with JavaScript enabled. No server, account or phone connection is needed. External references and the optional App Manager link leave the offline reference only when you follow them.

For a source preview:

```sh
php scripts/browser_generator.php
mdbook build browser
```

Open `browser/book/index.html`. Use the theme button to switch between dark and light. Use the search button to find an exact package ID; labels aren't unique.

## Release checklist

1. Update `VERSION`, the README badge, `browser/book.toml` and `CHANGELOG.md`. Keep dataset freshness separate from the presentation version.
2. Run the full build. Verify all generated package pages and local links, then exercise search and navigation in the extracted download.
3. Capture the actual browser in dark, light and narrow layouts. Preserve every attempt under `assets/concepts/` and record selected file hashes.
4. Commit the reviewed source. Create a source ZIP from that exact Git commit, publish both ZIP files with SHA-256 checksums, then download them again and verify their hashes.

The release builder doesn't push, deploy or contact a device. `scripts/publish_browser.sh` only builds locally. The old script's forced upstream push has been removed.

`scripts/update_uad.php` is a historical import helper pinned to old UAD-NG commits. It writes category files. It is not part of a normal build and must not be used as an automatic updater.

## Verification limits

Structural checks don't verify every package's behavior on every ROM. No phone was debloated for this release, and no replacement app was installed. The package data and suggested-app records are unchanged from the fork's 2026-05-14 snapshot.

The two schemas describe field types. The local checks additionally reject duplicate package IDs, unsafe output paths, unknown classification values and missing suggestion references. Three pairs of IDs differ only by capitalization; their generated filenames include a deterministic suffix to preserve all records on Windows. Missing optional labels and warnings remain visible data limitations.

The bundled search index is about 20 MB. mdBook reports its size during the build. Search was exercised offline in the packaged reference; first-search speed depends on the reader's browser and hardware.
