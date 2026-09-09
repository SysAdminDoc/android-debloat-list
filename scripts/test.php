<?php
/* SPDX-License-Identifier: AGPL-3.0-or-later */
require_once __DIR__ . '/reference.php';

$passed = 0;
function check(bool $condition, string $name): void {
    global $passed;
    if (!$condition) throw new RuntimeException("FAIL: $name");
    ++$passed;
    echo "PASS: $name\n";
}
function rejects(callable $operation, string $name): void {
    try { $operation(); } catch (Throwable $error) { check(true, $name); return; }
    check(false, $name);
}

try {
    $root = dirname(__DIR__);
    $data = load_reference($root);
    check(count($data['all']) === 5481, 'Documented package count');
    $paths = package_paths($data['all']);
    check(count(array_unique(array_map('strtolower', array_values($paths)))) === 5481, 'All package paths remain unique on case-insensitive filesystems');
    check($paths['com.sec.android.kies'] !== $paths['com.sec.android.Kies'], 'Capitalization-only IDs keep separate records');
    check($paths['com.android.systemui'] === 'com.android.systemui', 'Unambiguous package URLs remain unchanged');
    check(count($data['suggestions']) === 47, 'Documented suggestion category count');
    check(count(array_filter($data['all'], fn($i) => !isset($i['label']))) === 4074, 'Documented missing labels');
    check(count(array_filter($data['all'], fn($i) => $i['removal'] === 'unsafe' && empty($i['warning']))) === 278, 'Documented missing unsafe warnings');
    check(count(array_filter($data['all'], fn($i) => $i['description'] === '')) === 68, 'Documented empty descriptions');
    check(count(array_filter($data['suggestions'], fn($i) => $i === [])) === 12, 'Empty suggestion lists remain valid');

    $fixture = ['id' => 'com.example.package', 'description' => 'Example notes', 'removal' => 'delete'];
    check(count(validate_reference(['aosp' => [$fixture]], [])) === 1, 'Optional label and warning are optional');
    rejects(fn() => validate_reference(['aosp' => [$fixture, $fixture]], []), 'Duplicate ID in one list rejected');
    rejects(fn() => validate_reference(['aosp' => [$fixture], 'oem' => [$fixture]], []), 'Duplicate ID across lists rejected');
    foreach (['../escape', 'x/y', 'x\\y', 'x..y', '', 123] as $id) {
        rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['id' => $id])]], []), 'Unsafe or invalid output ID rejected: ' . json_encode($id));
    }
    rejects(fn() => validate_reference(['other' => [$fixture]], []), 'Unknown category rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['removal' => 'guaranteed-safe'])]], []), 'Unknown classification rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['description' => null])]], []), 'Missing description rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['label' => []])]], []), 'Invalid optional label rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['dependencies' => 'wrong'])]], []), 'Invalid dependency array rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['web' => [123]])]], []), 'Non-string reference rejected');
    rejects(fn() => validate_reference(['aosp' => [array_replace($fixture, ['suggestions' => 'missing'])]], []), 'Missing suggestion link rejected');
    rejects(fn() => validate_reference([], ['../escape' => []]), 'Unsafe suggestion filename rejected');

    $unsafe = $data['all']['com.android.systemui'];
    $page = render_package('aosp', $unsafe, $data['all']);
    check(str_contains($page, 'Keep this package installed.'), 'Unsafe record without warning gets general guidance');
    check(!str_contains($page, 'Recorded warning'), 'Missing package warning is not fabricated');
    check(str_contains($page, '<h1>com.android.systemui</h1>'), 'Missing label falls back to exact ID');
    $html = render_package('aosp', array_replace($fixture, ['label' => '<script>alert(1)</script>', 'description' => '<img src="https://example.invalid/pixel">', 'warning' => '<iframe></iframe>']), []);
    check(!str_contains($html, '<script>') && !str_contains($html, '<img ') && !str_contains($html, '<iframe>'), 'Record text cannot inject active HTML or remote images');
    check(str_contains($html, '&lt;script&gt;'), 'Escaped source text remains readable');
    check(html_lines("First\n\nSecond") === 'First<br><br>Second', 'Blank lines cannot split an HTML block during Markdown rendering');
    check(!str_contains(render_package('aosp', $fixture, []), 'Safe to delete'), 'Removal label does not promise safety');
    check(!str_contains(external_link('javascript:alert(1)', 'Reference'), 'href='), 'Executable reference URL is not linked');
    check(str_contains(external_link('https://example.org/?a=1&b=2', 'Reference'), '&amp;'), 'Reference attributes are escaped');
    $dep = array_replace($fixture, ['dependencies' => ['com.android.systemui', 'not.in.snapshot']]);
    $html = render_package('aosp', $dep, $data['all']);
    check(str_contains($html, 'href="com.android.systemui.html"'), 'Known dependency gets a working local target');
    check(str_contains($html, 'not listed in this snapshot'), 'Unknown dependency is clearly labelled');
    check(str_contains(render_suggestions('clocks', []), 'No replacement is listed'), 'Empty replacement category is explicit');
    check(str_contains(render_package('aosp', array_replace($fixture, ['description' => '']), []), 'No description is recorded'), 'Empty description is explicit');

    $schema = json_decode(file_get_contents("$root/schema/bloatware_list.json"), true, 512, JSON_THROW_ON_ERROR);
    check(!in_array('label', $schema['items']['required'], true), 'Formal schema agrees with optional labels');
    $version = trim(file_get_contents("$root/VERSION"));
    check(preg_match('/^\d+\.\d+\.\d+$/D', $version) === 1, 'Version format');
    check(str_contains(file_get_contents("$root/README.md"), 'version-' . $version . '-'), 'README version matches');
    check(str_contains(file_get_contents("$root/browser/book.toml"), "Android Debloat List v$version"), 'Browser version matches');
    check(str_contains(file_get_contents("$root/CHANGELOG.md"), "Android Debloat List v$version"), 'Changelog version matches');
    check(hash_file('sha256', "$root/LICENSE") === hash_file('sha256', "$root/COPYING"), 'Original AGPL license retained exactly');
    echo "$passed tests passed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
