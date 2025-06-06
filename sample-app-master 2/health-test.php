<?php
/**
 * Test script to verify health checks
 * Run to test endpoints before deployment
 */

$base_url = 'http://localhost:8000'; // Adjust according to your configuration

$endpoints = [
    '/health/startup',
    '/health/ready',
    '/health/live',
    '/api/health/startup',
    '/api/health/ready', 
    '/api/health/live'
];

echo "🏥 Health Check Endpoints Testing\n";
echo "=================================\n\n";

foreach ($endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "Testing: $url\n";
    
    $start_time = microtime(true);
    $response = @file_get_contents($url);
    $end_time = microtime(true);
    
    if ($response === false) {
        echo "❌ ERROR: Cannot access $endpoint\n";
    } else {
        $response_time = round(($end_time - $start_time) * 1000, 2);
        $data = json_decode($response, true);
        
        if ($data && isset($data['status'])) {
            $status_icon = $data['status'] === 'healthy' || $data['status'] === 'ready' || $data['status'] === 'alive' ? '✅' : '❌';
            echo "$status_icon Status: {$data['status']} ({$response_time}ms)\n";
            
            if (isset($data['checks'])) {
                foreach ($data['checks'] as $check_name => $check_result) {
                    $check_icon = $check_result['status'] === 'healthy' ? '  ✓' : '  ✗';
                    echo "$check_icon $check_name: {$check_result['message']}\n";
                }
            }
        } else {
            echo "❌ Invalid response\n";
        }
    }
    echo "\n";
}

echo "Testing completed!\n";
echo "\n📝 To use these endpoints with Kubernetes:\n";
echo "- Startup: /health/startup\n";
echo "- Readiness: /health/ready\n";
echo "- Liveness: /health/live\n";
?> 