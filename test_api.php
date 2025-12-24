<?php
// Test script to check API response
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = '1';

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::create('/api/medecin/2/disponibilites', 'GET');

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
