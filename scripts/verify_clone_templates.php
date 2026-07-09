<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$php = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
$generator = $root . DIRECTORY_SEPARATOR . 'worker' . DIRECTORY_SEPARATOR . 'GenerateSite.php';
$templates = [
    'clone-www-ld199-com-260709165632-3530' => ['min_length' => 5000, 'min_images' => 5],
    'clone-www-chuyunai-com-cn-260709165648-a5d6' => ['min_length' => 12000, 'min_images' => 10],
    'clone-aifuoil-com-260709165929-3207' => ['min_length' => 5000, 'min_images' => 5],
];

if (!is_file($php) || !is_file($generator)) {
    fwrite(STDERR, "Missing PHP runtime or generator.\n");
    exit(1);
}

$failed = false;
$rows = [];
foreach ($templates as $key => $rules) {
    $out = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'template_previews' . DIRECTORY_SEPARATOR . 'verify_' . $key;
    remove_path($out);
    putenv('HJ_TEMPLATE_KEY=' . $key);
    putenv('HJ_PUBLIC_PATH=' . $out);
    putenv('HJ_SITE_ID=10001');
    $command = '"' . $php . '" "' . $generator . '"';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    $index = $out . DIRECTORY_SEPARATOR . 'index.html';
    $html = is_file($index) ? (string)file_get_contents($index) : '';
    preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
    $row = [
        'key' => $key,
        'title' => trim(strip_tags($titleMatch[1] ?? '')),
        'length' => strlen($html),
        'images' => preg_match_all('/<img\b/i', $html),
        'redirect' => preg_match('/http-equiv=["\']refresh["\']|window\.location\.(replace|href|assign)\s*\(/i', $html) === 1,
        'zeroshop' => str_contains($html, 'ZeroShop'),
        'test_marker' => str_contains($html, 'HUJIAN_TEST_STATIC_MIRROR_OVERRIDE_260709'),
        'code' => $code,
    ];
    $row['ok'] = $code === 0
        && $row['length'] >= (int)$rules['min_length']
        && $row['images'] >= (int)$rules['min_images']
        && !$row['redirect']
        && !$row['zeroshop']
        && !$row['test_marker'];
    $failed = $failed || !$row['ok'];
    $rows[] = $row;
}
putenv('HJ_TEMPLATE_KEY');
putenv('HJ_PUBLIC_PATH');
putenv('HJ_SITE_ID');

foreach ($rows as $row) {
    echo sprintf(
        "%s\t%s\tlen=%d\timg=%d\tredirect=%s\tzeroshop=%s\tok=%s\n",
        $row['key'],
        $row['title'],
        $row['length'],
        $row['images'],
        $row['redirect'] ? 'yes' : 'no',
        $row['zeroshop'] ? 'yes' : 'no',
        $row['ok'] ? 'yes' : 'no'
    );
}

exit($failed ? 1 : 0);

function remove_path(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}
