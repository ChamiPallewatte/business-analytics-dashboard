<?php
// unzip.php - One-click extractor script for Hostinger deployment
header('Content-Type: text/plain');

$secretKey = 'Dr//fa7uRvBA78A4rhHTCjcMwD5QU44wGVx/Nw95VxU=';
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $secretKey) {
    http_response_code(403);
    die("ERROR: Unauthorized access key\n");
}

$zipFile = __DIR__ . '/deploy.zip';

if (!file_exists($zipFile)) {
    die("ERROR: deploy.zip file not found in " . __DIR__ . "\n");
}

if (!class_exists('ZipArchive')) {
    die("ERROR: PHP ZipArchive extension is not enabled on server\n");
}

$zip = new ZipArchive();
$res = $zip->open($zipFile);

if ($res === true) {
    $zip->extractTo(__DIR__);
    $zip->close();
    
    // Clean up deploy.zip and unzip.php self
    @unlink($zipFile);
    @unlink(__FILE__);
    
    echo "SUCCESS: Extracted deploy.zip successfully and cleaned up archive.\n";
} else {
    echo "ERROR: Failed to open deploy.zip (Error code: {$res})\n";
}
