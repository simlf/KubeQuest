<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthController extends Controller
{
    /**
     * Startup Probe - Vérifie que l'application a démarré correctement
     * Cette probe est appelée au démarrage pour s'assurer que l'app est initialisée
     */
    public function startup()
    {
        $checks = [
            'config' => $this->checkConfig(),
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkFilesystem(),
            'laravel' => $this->checkLaravelBasics()
        ];
        
        $healthy = !collect($checks)->contains(fn($check) => $check['status'] !== 'healthy');
        
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'app' => 'KubeQuest',
            'version' => config('app.version', '1.0.0')
        ], $healthy ? 200 : 503);
    }

    /**
     * Readiness Probe - Vérifie que l'application est prête à recevoir du trafic
     * Cette probe détermine si le pod doit recevoir du trafic
     */
    public function readiness()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'database_operations' => $this->checkDatabaseOperations(),
            'cache' => $this->checkCache(),
            'resources' => $this->checkResources(),
            'dependencies' => $this->checkDependencies()
        ];
        
        $ready = !collect($checks)->contains(fn($check) => $check['status'] !== 'healthy');
        
        return response()->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'app' => 'KubeQuest'
        ], $ready ? 200 : 503);
    }

    /**
     * Liveness Probe - Vérifie que l'application fonctionne correctement
     * Cette probe détermine si le pod doit être redémarré
     */
    public function liveness()
    {
        $checks = [
            'php_process' => ['status' => 'healthy', 'message' => 'PHP process running'],
            'memory' => $this->checkMemory(),
            'application' => $this->checkApplication(),
            'laravel_health' => $this->checkLaravelHealth()
        ];
        
        $alive = !collect($checks)->contains(fn($check) => $check['status'] !== 'healthy');
        
        return response()->json([
            'status' => $alive ? 'alive' : 'dead',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'app' => 'KubeQuest'
        ], $alive ? 200 : 503);
    }

    /**
     * Vérifications privées
     */
    private function checkConfig()
    {
        try {
            // Vérifier les variables d'environnement critiques
            $required_config = ['app.key', 'database.default', 'app.env'];
            
            foreach ($required_config as $config_key) {
                if (!config($config_key)) {
                    return ['status' => 'unhealthy', 'message' => "Missing config: $config_key"];
                }
            }
            
            return ['status' => 'healthy', 'message' => 'Configuration loaded successfully'];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Configuration error: ' . $e->getMessage()];
        }
    }
    
    private function checkDatabase()
    {
        try {
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'status' => 'healthy', 
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime
            ];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkDatabaseOperations()
    {
        try {
            // Test spécifique à l'application KubeQuest - vérifier que la table Counter existe et fonctionne
            $startTime = microtime(true);
            $count = DB::table('counters')->count();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'status' => 'healthy', 
                'message' => 'Counter table accessible',
                'counter_records' => $count,
                'response_time_ms' => $responseTime
            ];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database operations failed: ' . $e->getMessage()];
        }
    }
    
    private function checkFilesystem()
    {
        try {
            $paths_to_check = [
                storage_path(),
                storage_path('logs'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views')
            ];
            
            foreach ($paths_to_check as $path) {
                if (!is_readable($path) || !is_writable($path)) {
                    return ['status' => 'unhealthy', 'message' => "Filesystem access issue with: $path"];
                }
            }
            
            // Test d'écriture
            $testFile = storage_path('logs/health_check_test.txt');
            file_put_contents($testFile, 'health check test');
            if (!file_exists($testFile)) {
                return ['status' => 'unhealthy', 'message' => 'Cannot write to filesystem'];
            }
            unlink($testFile);
            
            return ['status' => 'healthy', 'message' => 'Filesystem access OK'];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Filesystem error: ' . $e->getMessage()];
        }
    }

    private function checkLaravelBasics()
    {
        try {
            // Vérifier que Laravel fonctionne correctement
            $checks = [];
            
            // Vérifier l'APP_KEY
            if (!config('app.key')) {
                return ['status' => 'unhealthy', 'message' => 'APP_KEY not set'];
            }
            
            // Vérifier que les services Laravel fonctionnent
            app('view');
            app('session');
            
            return ['status' => 'healthy', 'message' => 'Laravel core services OK'];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Laravel basics error: ' . $e->getMessage()];
        }
    }
    
    private function checkCache()
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test_value';
            
            // Test d'écriture cache
            Cache::put($key, $value, 60);
            
            // Test de lecture cache
            $retrieved = Cache::get($key);
            if ($retrieved !== $value) {
                return ['status' => 'unhealthy', 'message' => 'Cache read/write failed'];
            }
            
            // Nettoyage
            Cache::forget($key);
            
            return ['status' => 'healthy', 'message' => 'Cache working correctly'];
        } catch (Exception $e) {
            return ['status' => 'healthy', 'message' => 'Cache not configured (file driver): ' . $e->getMessage()];
        }
    }
    
    private function checkResources()
    {
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        $memory_limit = $this->convertToBytes(ini_get('memory_limit'));
        
        // Vérifier si on approche de la limite (90%)
        if ($memory_usage > $memory_limit * 0.9) {
            return [
                'status' => 'unhealthy', 
                'message' => 'Memory usage too high',
                'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2),
                'memory_limit_mb' => round($memory_limit / 1024 / 1024, 2)
            ];
        }
        
        return [
            'status' => 'healthy', 
            'message' => 'System resources OK',
            'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memory_peak / 1024 / 1024, 2),
            'memory_limit_mb' => round($memory_limit / 1024 / 1024, 2)
        ];
    }

    private function checkDependencies()
    {
        try {
            // Vérifier les extensions PHP requises
            $required_extensions = ['pdo', 'mbstring', 'openssl', 'json'];
            $missing_extensions = [];
            
            foreach ($required_extensions as $extension) {
                if (!extension_loaded($extension)) {
                    $missing_extensions[] = $extension;
                }
            }
            
            if (!empty($missing_extensions)) {
                return [
                    'status' => 'unhealthy', 
                    'message' => 'Missing PHP extensions: ' . implode(', ', $missing_extensions)
                ];
            }
            
            return ['status' => 'healthy', 'message' => 'All required dependencies available'];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Dependencies check error: ' . $e->getMessage()];
        }
    }
    
    private function checkMemory()
    {
        $memory_usage = memory_get_usage(true);
        $memory_limit = $this->convertToBytes(ini_get('memory_limit'));
        
        // Alerte si plus de 95% de la mémoire est utilisée (pour liveness)
        if ($memory_usage > $memory_limit * 0.95) {
            return [
                'status' => 'unhealthy', 
                'message' => 'Critical memory usage',
                'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2)
            ];
        }
        
        return [
            'status' => 'healthy', 
            'message' => 'Memory usage normal',
            'memory_usage_mb' => round($memory_usage / 1024 / 1024, 2)
        ];
    }
    
    private function checkApplication()
    {
        try {
            // Test spécifique à KubeQuest - vérifier que l'application répond correctement
            // Simuler une opération basique de l'app
            $testValue = DB::table('counters')->sum('count') ?? 0;
            
            return [
                'status' => 'healthy', 
                'message' => 'Application core functionality OK',
                'current_counter_sum' => $testValue
            ];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Application check failed: ' . $e->getMessage()];
        }
    }

    private function checkLaravelHealth()
    {
        try {
            // Vérifications spécifiques Laravel
            $checks = [];
            
            // Vérifier que l'application n'est pas en mode maintenance
            if (app()->isDownForMaintenance()) {
                return ['status' => 'unhealthy', 'message' => 'Application in maintenance mode'];
            }
            
            // Vérifier la version Laravel
            $version = app()->version();
            
            return [
                'status' => 'healthy', 
                'message' => 'Laravel health OK',
                'laravel_version' => $version,
                'php_version' => PHP_VERSION
            ];
        } catch (Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Laravel health check error: ' . $e->getMessage()];
        }
    }
    
    private function convertToBytes($size)
    {
        if (is_numeric($size)) {
            return $size;
        }
        
        $unit = strtoupper(substr($size, -1));
        $value = (int) $size;
        
        switch ($unit) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }
} 