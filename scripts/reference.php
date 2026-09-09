<?php
/* SPDX-License-Identifier: AGPL-3.0-or-later */

const ADL_CATEGORIES = ['aosp' => 'AOSP', 'carrier' => 'Carrier', 'google' => 'Google', 'misc' => 'Other packages', 'oem' => 'Device makers'];
const ADL_REMOVALS = ['delete' => 'Listed for removal', 'replace' => 'Replacement needed', 'caution' => 'Caution', 'unsafe' => 'Unsafe to remove'];

function html(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function html_lines(string $value): string {
    return str_replace(["\r\n", "\r", "\n"], '<br>', html($value));
}

function markdown_label(string $value): string {
    return str_replace(['\\', '[', ']', "\n", "\r"], ['\\\\', '\[', '\]', ' ', ' '], html($value));
}

function read_records(string $file): array {
    $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !array_is_list($data)) throw new RuntimeException("$file must contain a JSON array");
    return $data;
}

function valid_id(mixed $id): bool {
    return is_string($id) && preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.]*$/D', $id) === 1 && !str_contains($id, '..');
}

function validate_reference(array $categories, array $suggestions): array {
    $all = [];
    foreach ($categories as $type => $items) {
        if (!isset(ADL_CATEGORIES[$type]) || !array_is_list($items)) throw new RuntimeException("Invalid category: $type");
        foreach ($items as $item) {
            if (!is_array($item) || !valid_id($item['id'] ?? null)) throw new RuntimeException("Invalid package ID in $type");
            $id = $item['id'];
            if (isset($all[$id])) throw new RuntimeException("Duplicate package ID: $id");
            if (!isset($item['description']) || !is_string($item['description'])) throw new RuntimeException("Missing description: $id");
            if (!is_string($item['removal'] ?? null) || !isset(ADL_REMOVALS[$item['removal']])) throw new RuntimeException("Invalid removal: $id");
            foreach (['label', 'warning', 'suggestions'] as $field) {
                if (array_key_exists($field, $item) && !is_string($item[$field])) throw new RuntimeException("Invalid $field: $id");
            }
            foreach (['dependencies', 'required_by', 'web', 'tags'] as $field) {
                if (!isset($item[$field])) continue;
                if (!is_array($item[$field]) || !array_is_list($item[$field])) throw new RuntimeException("Invalid $field: $id");
                foreach ($item[$field] as $value) if (!is_string($value)) throw new RuntimeException("Invalid $field item: $id");
            }
            if (isset($item['suggestions']) && !array_key_exists($item['suggestions'], $suggestions)) throw new RuntimeException("Missing suggestion: $id");
            $all[$id] = $item + ['category' => $type];
        }
    }
    foreach ($suggestions as $name => $items) {
        if (!preg_match('/^[a-z0-9_]+$/D', $name) || !array_is_list($items)) throw new RuntimeException("Invalid suggestion list: $name");
        $seen = [];
        foreach ($items as $item) {
            if (!is_array($item) || !valid_id($item['id'] ?? null)) throw new RuntimeException("Invalid suggestion ID: $name");
            foreach (['label', 'repo'] as $field) if (!is_string($item[$field] ?? null)) throw new RuntimeException("Missing suggestion $field: $name");
            foreach (['reason', 'source'] as $field) if (isset($item[$field]) && !is_string($item[$field])) throw new RuntimeException("Invalid suggestion $field: $name");
            if (isset($seen[$item['id']])) throw new RuntimeException("Duplicate suggestion: $name");
            $seen[$item['id']] = true;
        }
    }
    return $all;
}

function load_reference(string $root): array {
    $categories = [];
    foreach (ADL_CATEGORIES as $type => $label) $categories[$type] = read_records("$root/$type.json");
    $suggestions = [];
    foreach (glob("$root/suggestions/*.json") as $file) $suggestions[basename($file, '.json')] = read_records($file);
    $all = validate_reference($categories, $suggestions);
    return ['categories' => $categories, 'suggestions' => $suggestions, 'all' => $all];
}

function external_link(string $url, string $label): string {
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host']) || !in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) return html($label);
    return '<a href="' . html($url) . '">' . html($label) . '</a>';
}

function package_paths(array $all): array {
    $counts = array_count_values(array_map('strtolower', array_keys($all)));
    $paths = [];
    foreach ($all as $id => $item) {
        $paths[$id] = $counts[strtolower($id)] > 1 ? $id . '--' . substr(hash('sha256', $id), 0, 10) : $id;
    }
    return $paths;
}

function render_package(string $type, array $item, array $all, ?array $paths = null): string {
    $paths ??= package_paths($all);
    $id = html($item['id']);
    $name = html($item['label'] ?? $item['id']);
    $removal = $item['removal'];
    $status = ADL_REMOVALS[$removal];
    $category = ADL_CATEGORIES[$type];
    $description = trim($item['description']);
    $description = $description === '' ? 'No description is recorded in this snapshot.' : $description;
    $content = '<p class="eyebrow">Package reference</p>' . "\n<h1>$name</h1>\n";
    $content .= '<p class="package-id"><code>' . $id . "</code></p>\n";
    $content .= '<div class="tags"><span>' . $category . '</span><span data-tag="' . $removal . '">' . $status . "</span></div>\n";
    if ($removal === 'unsafe') {
        $content .= '<aside class="risk-note danger"><strong>Keep this package installed.</strong><p>General guidance: this entry is marked unsafe. Removal can break device functions. This reference has not tested your phone or ROM.</p></aside>' . "\n";
    } elseif ($removal === 'caution') {
        $content .= '<aside class="risk-note"><strong>Investigate before changing it.</strong><p>Read the recorded notes and check device-specific dependencies. Missing information is not evidence of safety.</p></aside>' . "\n";
    } else {
        $content .= '<aside class="risk-note"><strong>A recorded classification, not a device-safety guarantee.</strong><p>Check your exact package, ROM and recovery options before considering removal or replacement.</p></aside>' . "\n";
    }
    if (trim($item['warning'] ?? '') !== '') $content .= '<h2>Recorded warning</h2><div class="package-description">' . html_lines($item['warning']) . "</div>\n";
    $content .= '<h2>Recorded notes</h2><div class="package-description">' . html_lines($description) . "</div>\n";
    foreach (['dependencies' => 'Depends on', 'required_by' => 'Required by'] as $field => $heading) {
        if (empty($item[$field])) continue;
        $content .= "<h2>$heading</h2>\n<ul>\n";
        foreach ($item[$field] as $dependency) {
            $link = isset($all[$dependency]) ? '<a href="' . html($paths[$dependency]) . '.html"><code>' . html($dependency) . '</code></a>' : '<code>' . html($dependency) . '</code> (not listed in this snapshot)';
            $content .= "<li>$link</li>\n";
        }
        $content .= "</ul>\n";
    }
    if (isset($item['suggestions'])) {
        $label = ucwords(str_replace('_', ' ', $item['suggestions']));
        $content .= '<h2>Replacement notes</h2><p><a href="../suggestions/' . html($item['suggestions']) . '.html">' . html($label) . "</a>. Availability and suitability have not been rechecked for this release.</p>\n";
    }
    if (!empty($item['web'])) {
        $content .= "<h2>Recorded references</h2>\n<ul>\n";
        foreach ($item['web'] as $url) $content .= '<li>' . external_link($url, $url) . "</li>\n";
        $content .= "</ul>\n";
    }
    $content .= '<div class="record-footer"><p>Source: <a href="https://github.com/SysAdminDoc/android-debloat-list/blob/master/' . $type . '.json">' . $type . '.json</a>. Dataset snapshot: 2026-05-14.</p>';
    $content .= '<p><a href="app-manager://details?id=' . rawurlencode($item['id']) . '">Open in App Manager</a> requires a compatible app on your device. This reference cannot remove packages.</p>';
    return $content . '<p><a href="../index.html">Reference overview</a> · <a href="../reading-guide.html">How to read an entry</a></p></div>' . "\n";
}

function render_suggestions(string $name, array $items): string {
    $title = html(ucwords(str_replace('_', ' ', $name)));
    $content = "<h1>$title</h1>\n<p>Historical replacement notes from the dataset. Check current availability, source and maintenance before installing anything.</p>\n";
    if (!$items) return $content . '<p class="risk-note">No replacement is listed for this category. An empty list is not a recommendation.</p>';
    foreach ($items as $item) {
        $content .= '<h2>' . html($item['label']) . '</h2><p><code>' . html($item['id']) . "</code></p>\n";
        if (isset($item['reason'])) $content .= '<div class="package-description">' . html_lines($item['reason']) . "</div>\n";
        $content .= '<p>' . external_link($item['repo'], 'Source repository') . "</p>\n";
        $stores = [];
        foreach (['f' => 'F-Droid', 'g' => 'Google Play', 'a' => 'Amazon Appstore', 's' => 'Samsung Galaxy Store'] as $code => $label) {
            if (str_contains($item['source'] ?? '', $code)) $stores[] = $label;
        }
        if ($stores) $content .= '<p>Recorded store hints: ' . implode(', ', $stores) . '. Not an availability check.</p>';
    }
    return $content;
}
