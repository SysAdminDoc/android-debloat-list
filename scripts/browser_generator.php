<?php
/* SPDX-License-Identifier: AGPL-3.0-or-later */
require_once __DIR__ . '/reference.php';

function generate_reference(string $root): void {
    $data = load_reference($root);
    $paths = package_paths($data['all']);
    $src = "$root/browser/src";
    $write = function (string $file, string $content) use ($src): void {
        $parts = explode('/', $file);
        $directory = $src;
        if (is_link($directory)) throw new RuntimeException("Refusing linked output: $directory");
        if (!is_dir($directory) && !mkdir($directory, 0777, true)) throw new RuntimeException("Cannot create $directory");
        foreach (array_slice($parts, 0, -1) as $part) {
            $directory .= "/$part";
            if (is_link($directory)) throw new RuntimeException("Refusing linked output: $directory");
            if (!is_dir($directory) && !mkdir($directory)) throw new RuntimeException("Cannot create $directory");
        }
        if (is_link("$src/$file")) throw new RuntimeException("Refusing linked output: $file");
        if (file_put_contents("$src/$file", $content) === false) throw new RuntimeException("Cannot write $file");
    };
    $version = trim(file_get_contents("$root/VERSION"));
    if (!preg_match('/^\d+\.\d+\.\d+$/D', $version)) throw new RuntimeException('Invalid VERSION');
    $overview = file_get_contents("$root/browser/README.md");
    $overview = str_replace(['{{VERSION}}', '{{PACKAGES}}', '{{SUGGESTION_LISTS}}'], [$version, number_format(count($data['all'])), count($data['suggestions'])], $overview);
    $write('README.md', $overview);
    $write('reading-guide.md', file_get_contents("$root/browser/reading-guide.md"));
    $summary = "# Summary\n\n[Reference overview](README.md)\n\n[How to read an entry](reading-guide.md)\n\n# Packages\n\n";
    foreach ($data['categories'] as $type => $items) {
        usort($items, fn($a, $b) => strnatcasecmp($a['label'] ?? $a['id'], $b['label'] ?? $b['id']) ?: strcmp($a['id'], $b['id']));
        $label = ADL_CATEGORIES[$type];
        $count = count($items);
        $summary .= "- [$label ($count)](categories/$type.md)\n";
        $index = "# $label\n\n$count package records. Search by exact ID to distinguish apps with similar names.\n\n";
        foreach ($items as $item) {
            $id = $item['id'];
            $page = $paths[$id];
            $name = markdown_label($item['label'] ?? $id);
            $summary .= "  - [$name](bloatware/$page.md)\n";
            $index .= "- [$name](../bloatware/$page.md) <code>" . html($id) . "</code>\n";
            $write("bloatware/$page.md", render_package($type, $item, $data['all'], $paths));
        }
        $write("categories/$type.md", $index);
    }
    $summary .= "\n# Replacement notes\n\n- [Replacement categories](suggestions/index.md)\n";
    $suggestionIndex = "# Replacement categories\n\nHistorical alternatives from 47 categories. Twelve lists are empty. Read each entry's limitations and check current availability before installing an app.\n\n";
    foreach ($data['suggestions'] as $name => $items) {
        $title = ucwords(str_replace('_', ' ', $name));
        $summary .= "  - [$title](suggestions/$name.md)\n";
        $suggestionIndex .= "- [$title]($name.md): " . count($items) . " recorded alternatives\n";
        $write("suggestions/$name.md", render_suggestions($name, $items));
    }
    $write('suggestions/index.md', $suggestionIndex);
    $summary .= "\n# About\n\n- [License and origins](license.md)\n";
    $write('license.md', "# License and origins\n\nThis is the SysAdminDoc fork of [Android Debloat List](https://github.com/MuntashirAkon/android-debloat-list) by Muntashir Al-Islam and contributors. It incorporates UAD-NG-derived records. The fork is not automatically synchronized with upstream.\n\nCopyright (C) 2022 Muntashir Al-Islam. Licensed under AGPL-3.0-or-later. The complete license and corresponding source are included in the download.\n\n[Read the full license](../LICENSE)\n\nThe reference renderer is [mdBook](https://github.com/rust-lang/mdBook). Its bundled notices are included in THIRD-PARTY-NOTICES.txt at the download root.\n");
    $write('SUMMARY.md', $summary);
    echo count($data['all']) . " package pages and " . count($data['suggestions']) . " suggestion pages generated.\n";
}

try {
    generate_reference(dirname(__DIR__));
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
