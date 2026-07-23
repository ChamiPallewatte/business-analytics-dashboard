<?php
// unzip.php - Robust extractor script for Hostinger deployment
header('Content-Type: text/plain');

$secretKey = 'Dr//fa7uRvBA78A4rhHTCjcMwD5QU44wGVx/Nw95VxU=';
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $secretKey) {
    http_response_code(403);
    die("ERROR: Unauthorized access key\n");
}

$zipFile = __DIR__ . '/deploy.zip';

if (!file_exists($zipFile)) {
    echo "SUCCESS: Archive already extracted or processed\n";
    @unlink(__FILE__);
    exit(0);
}

$extracted = false;

// Attempt 1: System CLI unzip command
if (function_exists('exec')) {
    @exec('unzip -o ' . escapeshellarg($zipFile) . ' 2>&1', $output, $returnVar);
    if ($returnVar === 0) {
        $extracted = true;
    }
}

// Attempt 2: PHP ZipArchive fallback
if (!$extracted && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $res = $zip->open($zipFile);
    if ($res === true) {
        $zip->extractTo(__DIR__);
        $zip->close();
        $extracted = true;
    }
}

if ($extracted) {
    // Purge cached config files to force fresh .env load
    @unlink(__DIR__ . '/bootstrap/cache/config.php');
    @unlink(__DIR__ . '/bootstrap/cache/routes-v7.php');
    @unlink(__DIR__ . '/bootstrap/cache/services.php');
    @unlink(__DIR__ . '/bootstrap/cache/packages.php');

    @unlink($zipFile);
    @unlink(__FILE__);
    echo "SUCCESS: Extracted deploy.zip successfully and cleaned up archive.\n";
} else {
    echo "ERROR: Failed to extract deploy.zip.\n";
}
