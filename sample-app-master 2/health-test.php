<?php
/**
 * Script de test pour vérifier les health checks
 * À exécuter pour tester les endpoints avant déploiement
 */

$base_url = 'http://localhost:8000'; // Adapter selon votre configuration

$endpoints = [
    '/health/startup',
    '/health/ready',
    '/health/live',
    '/api/health/startup',
    '/api/health/ready', 
    '/api/health/live'
];

echo "🏥 Test des Health Check Endpoints\n";
echo "==================================\n\n";

foreach ($endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "Testing: $url\n";
    
    $start_time = microtime(true);
    $response = @file_get_contents($url);
    $end_time = microtime(true);
    
    if ($response === false) {
        echo "❌ ERREUR: Impossible d'accéder à $endpoint\n";
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
            echo "❌ Réponse invalide\n";
        }
    }
    echo "\n";
}

echo "Test terminé!\n";
echo "\n📝 Pour utiliser ces endpoints avec Kubernetes:\n";
echo "- Startup: /health/startup\n";
echo "- Readiness: /health/ready\n";
echo "- Liveness: /health/live\n";
?> 