#!/usr/bin/env python3
"""Build a checked, deterministic offline reference. No network or device access."""
# SPDX-License-Identifier: AGPL-3.0-or-later
import argparse
import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
import posixpath
import re
import shutil
import subprocess
import sys
from urllib.parse import unquote, urlsplit
import zipfile

ROOT = Path(__file__).resolve().parents[1]
CATEGORIES = ("aosp", "carrier", "google", "misc", "oem")


def load_json(file):
    return json.loads(Path(file).read_text(encoding="utf-8"))


def safe_clean(directory, root=ROOT):
    """Only remove the three known generated trees, never linked paths."""
    directory, root = Path(directory).absolute(), Path(root).resolve()
    allowed = {root / "dist", root / "browser/src", root / "browser/book"}
    if directory not in allowed or directory.resolve() != directory:
        raise ValueError(f"Refusing unexpected or linked build directory: {directory}")
    if not directory.exists():
        return
    for item in [directory, *directory.rglob("*")]:
        if item.is_symlink() or item.resolve() != item.absolute():
            raise ValueError(f"Refusing linked build entry: {item}")
    shutil.rmtree(directory)


class Links(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []

    def handle_starttag(self, tag, attrs):
        for key, value in attrs:
            if key in ("href", "src") and value:
                self.links.append(value)


def validate_links(files):
    checks = 0
    broken = []
    for name, content in files.items():
        if not name.startswith("reference/") or not name.endswith(".html"):
            continue
        parser = Links()
        parser.feed(content.decode("utf-8"))
        for link in parser.links:
            parsed = urlsplit(link)
            if parsed.scheme or parsed.netloc or not parsed.path:
                continue
            target = posixpath.normpath(posixpath.join(posixpath.dirname(name), unquote(parsed.path)))
            if parsed.path.endswith("/"):
                target += "/index.html"
            if target not in files:
                broken.append(f"Broken local link in {name}: {link}")
            checks += 1
    if broken:
        raise ValueError("\n".join(broken[:20]) + f"\n{len(broken)} broken local links.")
    return checks


def write_archive(target, files, version):
    manifest = {
        "project": "Android Debloat List",
        "version": version,
        "dataset_snapshot": "2026-05-14",
        "files": [{"path": name, "bytes": len(data), "sha256": hashlib.sha256(data).hexdigest()}
                  for name, data in sorted(files.items())],
    }
    entries = dict(files)
    entries["manifest.json"] = (json.dumps(manifest, indent=2, ensure_ascii=False) + "\n").encode()
    with zipfile.ZipFile(target, "x", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for name, data in sorted(entries.items()):
            if name.startswith("/") or "\\" in name or ".." in name.split("/"):
                raise ValueError(f"Unsafe archive path: {name}")
            info = zipfile.ZipInfo(name, date_time=(2026, 9, 9, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data, compresslevel=9)
    with zipfile.ZipFile(target) as archive:
        if archive.testzip() is not None:
            raise ValueError("Archive CRC verification failed")
    return manifest


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--php", default=shutil.which("php"))
    parser.add_argument("--mdbook", default=shutil.which("mdbook"))
    args = parser.parse_args()
    if not args.php or not args.mdbook:
        parser.error("Install PHP and mdBook, or supply --php and --mdbook paths.")
    version = (ROOT / "VERSION").read_text(encoding="utf-8").strip()
    if not re.fullmatch(r"\d+\.\d+\.\d+", version):
        raise ValueError("Invalid VERSION")
    book_version = subprocess.run([args.mdbook, "--version"], check=True, capture_output=True, text=True)
    if book_version.stdout.strip() != "mdbook v0.5.4":
        raise ValueError("This release is verified with mdBook 0.5.4. Use that version.")
    (ROOT / "build").mkdir(exist_ok=True)
    log_path = ROOT / "build/release-build.log"
    with log_path.open("w", encoding="utf-8") as log:
        def run(command):
            result = subprocess.run(command, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT)
            if result.returncode:
                raise RuntimeError(f"Command failed ({result.returncode}). Read {log_path}")
        run([args.php, "scripts/lint.php"])
        run([args.php, "scripts/test.php"])
        run([sys.executable, "scripts/test_build.py"])
        for directory in ("browser/src", "browser/book", "dist"):
            safe_clean(ROOT / directory)
        run([args.php, "scripts/browser_generator.php"])
        run([args.mdbook, "build", "browser"])

    files = {}
    def add_tree(directory, prefix):
        for file in sorted(directory.rglob("*")):
            if file.is_symlink() or file.resolve() != file.absolute():
                raise ValueError(f"Refusing linked source: {file}")
            if file.is_file() and "__pycache__" not in file.parts and not file.name.endswith(".pyc"):
                files[prefix + file.relative_to(directory).as_posix()] = file.read_bytes()

    add_tree(ROOT / "browser/book", "reference/")
    for category in CATEGORIES:
        files[f"data/{category}.json"] = (ROOT / f"{category}.json").read_bytes()
        files[f"source/{category}.json"] = files[f"data/{category}.json"]
    for folder in ("schema", "suggestions"):
        add_tree(ROOT / folder, f"data/{folder}/")
    for folder in ("schema", "suggestions", "scripts", "docs", "assets"):
        add_tree(ROOT / folder, f"source/{folder}/")
    for file in ("README.md", "CHANGELOG.md", "ROADMAP.md", "VERSION", "LICENSE", "COPYING", "CONTRIBUTING.md", ".gitignore", ".gitattributes", "THIRD-PARTY-NOTICES.txt"):
        files[f"source/{file}"] = (ROOT / file).read_bytes()
    for file in (ROOT / "browser").iterdir():
        if file.is_file():
            files["source/browser/" + file.name] = file.read_bytes()
    files["README.md"] = (ROOT / "docs/OFFLINE.md").read_bytes()
    files["LICENSE"] = (ROOT / "LICENSE").read_bytes()
    files["THIRD-PARTY-NOTICES.txt"] = (ROOT / "THIRD-PARTY-NOTICES.txt").read_bytes()
    add_tree(ROOT / "docs/licenses", "licenses/")
    checks = validate_links(files)
    expected = sum(len(load_json(ROOT / f"{name}.json")) for name in CATEGORIES)
    actual = sum(name.startswith("reference/bloatware/") and name.endswith(".html") for name in files)
    if actual != expected:
        raise ValueError(f"Expected {expected} package pages, found {actual}")
    (ROOT / "dist").mkdir()
    target = ROOT / f"dist/android-debloat-list-v{version}-offline.zip"
    manifest = write_archive(target, files, version)
    digest = hashlib.sha256(target.read_bytes()).hexdigest()
    (ROOT / "dist/SHA256SUMS.txt").write_text(f"{digest}  {target.name}\n", encoding="utf-8")
    print(json.dumps({"version": version, "package_pages": actual, "local_links_checked": checks,
                      "files": len(manifest["files"]), "bytes": target.stat().st_size, "sha256": digest}))


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, RuntimeError, subprocess.CalledProcessError, zipfile.BadZipFile) as error:
        print(f"Build failed: {error}", file=sys.stderr)
        raise SystemExit(1)
