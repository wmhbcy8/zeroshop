<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$php = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'php.exe';
$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$includeLiveClone = in_array('--include-live-clone', $argv, true);

$checks = [
    ['script' => 'verify_clone_templates.php', 'args' => []],
    ['script' => 'verify_content_distribution_static.php', 'args' => [$baseUrl]],
    ['script' => 'verify_ai_collector_distribution.php', 'args' => [$baseUrl]],
    ['script' => 'verify_site_scope_and_publish_restore.php', 'args' => [$baseUrl]],
    ['script' => 'verify_deploy_package_flow.php', 'args' => [$baseUrl]],
];

if ($includeLiveClone) {
    $checks[] = ['script' => 'verify_live_clone_flow.php', 'args' => [$baseUrl]];
}

if (!is_file($php)) {
    fwrite(STDERR, "PHP runtime not found: {$php}\n");
    exit(1);
}

$failed = false;
$startedAt = microtime(true);

foreach ($checks as $check) {
    $script = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $check['script'];
    if (!is_file($script)) {
        echo "FAIL\t{$check['script']}\tscript not found\n";
        $failed = true;
        continue;
    }
    $start = microtime(true);
    $command = escapeshellarg($php) . ' ' . escapeshellarg($script);
    foreach ($check['args'] as $arg) {
        $command .= ' ' . escapeshellarg((string)$arg);
    }
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    foreach ($output as $line) {
        echo $line . PHP_EOL;
    }
    $duration = number_format(microtime(true) - $start, 2);
    echo ($code === 0 ? 'PASS' : 'FAIL') . "\t{$check['script']}\t{$duration}s" . PHP_EOL;
    if ($code !== 0) {
        $failed = true;
        break;
    }
}

$total = number_format(microtime(true) - $startedAt, 2);
echo ($failed ? 'FAIL' : 'PASS') . "\tverify_all\t{$total}s" . PHP_EOL;
if (!$includeLiveClone) {
    echo "INFO\tverify_all\tlive clone verification skipped; add --include-live-clone to test external source URLs" . PHP_EOL;
}

exit($failed ? 1 : 0);
