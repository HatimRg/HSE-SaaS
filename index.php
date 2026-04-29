<?php
/**
 * Laravel Application Entry Point for XAMPP
 * 
 * This file redirects all requests to the public/index.php
 * In production, configure your web server to point directly to the public/ folder
 */

// Check if the request is for a real file or directory in public
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicPath = __DIR__ . '/public' . $uri;

// If the file exists in public, serve it directly
if ($uri !== '/' && file_exists($publicPath)) {
    // Set appropriate content type for common file extensions
    $extension = pathinfo($publicPath, PATHINFO_EXTENSION);
    $contentTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    if (isset($contentTypes[$extension])) {
        header('Content-Type: ' . $contentTypes[$extension]);
    }
    
    readfile($publicPath);
    exit;
}

// Otherwise, redirect to public/index.php
require __DIR__ . '/public/index.php';
