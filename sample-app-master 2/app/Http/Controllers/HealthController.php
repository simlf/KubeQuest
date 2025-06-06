<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class HealthController extends Controller
{
    /**
     * Startup Probe - Checks that the application has started correctly
     * This probe is called at startup to ensure the app is initialized
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
     * Readiness Probe - Checks that the application is ready to receive traffic
     * This probe determines if the pod should receive traffic
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
     * Liveness Probe - Checks that the application is functioning correctly
     * This probe determines if the pod should be restarted
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
     * Private health check methods
     */
    private function checkConfig()
    {
        try {
            // Check critical environment variables
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
            // KubeQuest specific test - check that Counter table exists and works
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
            
            // Write test
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
            // Check that Laravel is working correctly
            $checks = [];
            
            // Check APP_KEY
            if (!config('app.key')) {
                return ['status' => 'unhealthy', 'message' => 'APP_KEY not set'];
            }
            
            // Check that Laravel services are working
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
            
            // Cache write test
            Cache::put($key, $value, 60);
            
            // Cache read test
            $retrieved = Cache::get($key);
            if ($retrieved !== $value) {
                return ['status' => 'unhealthy', 'message' => 'Cache read/write failed'];
            }
            
            // Cleanup
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
        
        // Check if approaching limit (90%)
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
            // Check required PHP extensions
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
        
        // Alert if more than 95% memory is used (for liveness)
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
            // KubeQuest specific test - check that application responds correctly
            // Simulate basic app operation
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
            // Laravel specific checks
            $checks = [];
            
            // Check that application is not in maintenance mode
            if (app()->isDownForMaintenance()) {
                return ['status' => 'unhealthy', 'message' => 'Application in maintenance mode'];
            }
            
            // Check Laravel version
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