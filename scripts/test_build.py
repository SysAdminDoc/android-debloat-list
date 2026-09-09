#!/usr/bin/env python3
# SPDX-License-Identifier: AGPL-3.0-or-later
import hashlib
import json
from pathlib import Path
import tempfile
import unittest
import zipfile
from build import load_json, safe_clean, validate_links, write_archive


class BuildChecks(unittest.TestCase):
    def test_json_is_utf8_independent_of_system_locale(self):
        with tempfile.TemporaryDirectory() as temp:
            file = Path(temp) / "data.json"
            file.write_text('{"label": "Андроид 日本語"}', encoding="utf-8")
            self.assertEqual(load_json(file)["label"], "Андроид 日本語")

    def test_same_inputs_produce_same_archive(self):
        with tempfile.TemporaryDirectory() as temp:
            a, b = Path(temp) / "a.zip", Path(temp) / "b.zip"
            files = {"reference/index.html": b"<h1>Example</h1>", "LICENSE": b"Example license"}
            write_archive(a, files, "0.1.0")
            write_archive(b, files, "0.1.0")
            self.assertEqual(a.read_bytes(), b.read_bytes())

    def test_manifest_and_crc_match_every_file(self):
        with tempfile.TemporaryDirectory() as temp:
            output = Path(temp) / "result.zip"
            files = {"reference/index.html": b"<h1>Example</h1>", "LICENSE": b"Example license"}
            write_archive(output, files, "0.1.0")
            with zipfile.ZipFile(output) as archive:
                self.assertIsNone(archive.testzip())
                for item in json.loads(archive.read("manifest.json"))["files"]:
                    content = archive.read(item["path"])
                    self.assertEqual(item["bytes"], len(content))
                    self.assertEqual(item["sha256"], hashlib.sha256(content).hexdigest())

    def test_existing_archive_is_not_overwritten(self):
        with tempfile.TemporaryDirectory() as temp:
            output = Path(temp) / "result.zip"
            output.write_bytes(b"Keep this file")
            with self.assertRaises(FileExistsError):
                write_archive(output, {}, "0.1.0")
            self.assertEqual(output.read_bytes(), b"Keep this file")

    def test_archive_traversal_rejected(self):
        for name in ("../escape", "/absolute", "a/../../escape", "a\\escape"):
            with self.subTest(name=name), tempfile.TemporaryDirectory() as temp:
                with self.assertRaises(ValueError):
                    write_archive(Path(temp) / "result.zip", {name: b"example"}, "0.1.0")

    def test_local_links_include_license_and_anchor_targets(self):
        files = {"reference/index.html": b'<a href="record.html#notes">Read</a><a href="../LICENSE">License</a>',
                 "reference/record.html": b'<a href="https://example.org">External</a>',
                 "LICENSE": b"Example"}
        self.assertEqual(validate_links(files), 2)

    def test_broken_local_link_fails(self):
        with self.assertRaisesRegex(ValueError, "Broken local link"):
            validate_links({"reference/index.html": b'<img src="missing.png">'})

    def test_relative_directory_link_resolves_to_index(self):
        files = {"reference/404.html": b'<a href="./">Home</a>', "reference/index.html": b"Example"}
        self.assertEqual(validate_links(files), 1)

    def test_clean_is_limited_to_named_generated_trees(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp).resolve()
            protected = root / "source"
            protected.mkdir()
            marker = protected / "keep.txt"
            marker.write_text("keep")
            with self.assertRaises(ValueError):
                safe_clean(protected, root)
            self.assertTrue(marker.exists())
            build = root / "dist"
            build.mkdir()
            (build / "old.zip").write_bytes(b"generated")
            safe_clean(build, root)
            self.assertFalse(build.exists())

    def test_parent_traversal_cannot_be_a_clean_target(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp).resolve()
            with self.assertRaises(ValueError):
                safe_clean(root / "dist/../source", root)


if __name__ == "__main__":
    unittest.main(verbosity=2)
