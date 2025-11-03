<?php
/**
 * VanTracing Cache System / Sistema de Cache VanTracing
 * 
 * High-performance caching system with multiple storage backends
 * Sistema de cache de alta performance com múltiplos backends de armazenamento
 * 
 * @package VanTracing
 * @author Kevyn
 * @version 2.0
 */

interface CacheInterface {
    public function get($key);
    public function set($key, $value, $ttl = 3600);
    public function delete($key);
    public function clear();
    public function exists($key);
}

/**
 * File-based cache implementation / Implementação de cache baseada em arquivos
 */
class FileCache implements CacheInterface {
    private $cache_dir;
    private $default_ttl;
    
    public function __construct($cache_dir = null, $default_ttl = 3600) {
        $this->cache_dir = $cache_dir ?: __DIR__ . '/../cache/';
        $this->default_ttl = $default_ttl;
        
        $this->createCacheDirectory();
    }
    
    private function createCacheDirectory() {
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Create .htaccess to protect cache files / Criar .htaccess para proteger arquivos de cache
        $htaccess_file = $this->cache_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Require all denied\n");
        }
    }
    
    public function get($key) {
        $file_path = $this->getFilePath($key);
        
        if (!file_exists($file_path)) {
            return null;
        }
        
        $data = file_get_contents($file_path);
        $cache_data = json_decode($data, true);
        
        if (!$cache_data || !isset($cache_data['expires'], $cache_data['value'])) {
            $this->delete($key);
            return null;
        }
        
        // Check if cache has expired / Verificar se cache expirou
        if ($cache_data['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cache_data['value'];
    }
    
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $file_path = $this->getFilePath($key);
        
        $cache_data = [
            'value' => $value,
            'created' => time(),
            'expires' => time() + $ttl,
            'ttl' => $ttl
        ];
        
        $result = file_put_contents($file_path, json_encode($cache_data), LOCK_EX);
        return $result !== false;
    }
    
    public function delete($key) {
        $file_path = $this->getFilePath($key);
        
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        
        return true;
    }
    
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    public function exists($key) {
        return $this->get($key) !== null;
    }
    
    private function getFilePath($key) {
        $safe_key = preg_replace('/[^a-zA-Z0-9._-]/', '_', $key);
        return $this->cache_dir . $safe_key . '.cache';
    }
    
    /**
     * Clean expired cache files / Limpar arquivos de cache expirados
     */
    public function cleanExpired() {
        $files = glob($this->cache_dir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cache_data = json_decode($data, true);
            
            if (!$cache_data || $cache_data['expires'] < time()) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics / Obter estatísticas do cache
     */
    public function getStats() {
        $files = glob($this->cache_dir . '*.cache');
        $total_size = 0;
        $expired = 0;
        $valid = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $data = file_get_contents($file);
            $cache_data = json_decode($data, true);
            
            if (!$cache_data || $cache_data['expires'] < time()) {
                $expired++;
            } else {
                $valid++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_size_bytes' => $total_size,
            'total_size_human' => $this->formatBytes($total_size)
        ];
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Main VanTracing Cache Manager / Gerenciador Principal de Cache VanTracing
 */
class VanTracingCache {
    private static $instance;
    private $cache_backend;
    private $config;
    
    private function __construct() {
        $this->config = $this->loadConfig();
        $this->cache_backend = new FileCache(
            $this->config['cache_dir'],
            $this->config['default_ttl']
        );
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig() {
        return [
            'cache_dir' => __DIR__ . '/../cache/',
            'default_ttl' => (int)(getenv('CACHE_TTL') ?: 3600), // 1 hour default
            'enabled' => getenv('CACHE_ENABLED') !== 'false',
            'prefixes' => [
                'user' => 'user_',
                'route' => 'route_',
                'location' => 'location_',
                'database' => 'db_',
                'api' => 'api_'
            ]
        ];
    }
    
    /**
     * Get cached value / Obter valor do cache
     */
    public static function get($key, $default = null) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled']) {
            return $default;
        }
        
        $value = $instance->cache_backend->get($key);
        return $value !== null ? $value : $default;
    }
    
    /**
     * Set cache value / Definir valor do cache
     */
    public static function set($key, $value, $ttl = null) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled']) {
            return false;
        }
        
        return $instance->cache_backend->set($key, $value, $ttl);
    }
    
    /**
     * Delete cache value / Deletar valor do cache
     */
    public static function delete($key) {
        $instance = self::getInstance();
        return $instance->cache_backend->delete($key);
    }
    
    /**
     * Check if key exists / Verificar se chave existe
     */
    public static function exists($key) {
        $instance = self::getInstance();
        
        if (!$instance->config['enabled']) {
            return false;
        }
        
        return $instance->cache_backend->exists($key);
    }
    
    /**
     * Clear all cache / Limpar todo cache
     */
    public static function clear() {
        $instance = self::getInstance();
        return $instance->cache_backend->clear();
    }
    
    /**
     * Cache database query result / Cache resultado de consulta do banco
     */
    public static function cacheQuery($sql, $params, $result, $ttl = 900) {
        $key = self::generateQueryKey($sql, $params);
        return self::set($key, $result, $ttl);
    }
    
    /**
     * Get cached query result / Obter resultado de consulta em cache
     */
    public static function getCachedQuery($sql, $params) {
        $key = self::generateQueryKey($sql, $params);
        return self::get($key);
    }
    
    /**
     * Cache user data / Cache dados do usuário
     */
    public static function cacheUser($user_id, $user_data, $ttl = 1800) {
        $key = self::getInstance()->config['prefixes']['user'] . $user_id;
        return self::set($key, $user_data, $ttl);
    }
    
    /**
     * Get cached user data / Obter dados de usuário em cache
     */
    public static function getCachedUser($user_id) {
        $key = self::getInstance()->config['prefixes']['user'] . $user_id;
        return self::get($key);
    }
    
    /**
     * Invalidate user cache / Invalidar cache do usuário
     */
    public static function invalidateUser($user_id) {
        $key = self::getInstance()->config['prefixes']['user'] . $user_id;
        return self::delete($key);
    }
    
    /**
     * Cache route data / Cache dados de rota
     */
    public static function cacheRoute($route_id, $route_data, $ttl = 600) {
        $key = self::getInstance()->config['prefixes']['route'] . $route_id;
        return self::set($key, $route_data, $ttl);
    }
    
    /**
     * Get cached route data / Obter dados de rota em cache
     */
    public static function getCachedRoute($route_id) {
        $key = self::getInstance()->config['prefixes']['route'] . $route_id;
        return self::get($key);
    }
    
    /**
     * Cache location data / Cache dados de localização
     */
    public static function cacheLocation($location_key, $location_data, $ttl = 300) {
        $key = self::getInstance()->config['prefixes']['location'] . $location_key;
        return self::set($key, $location_data, $ttl);
    }
    
    /**
     * Get cached location data / Obter dados de localização em cache
     */
    public static function getCachedLocation($location_key) {
        $key = self::getInstance()->config['prefixes']['location'] . $location_key;
        return self::get($key);
    }
    
    /**
     * Remember pattern - get from cache or execute callback / Padrão remember - obter do cache ou executar callback
     */
    public static function remember($key, $callback, $ttl = null) {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        
        if ($value !== null) {
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Generate cache key for database query / Gerar chave de cache para consulta do banco
     */
    private static function generateQueryKey($sql, $params) {
        $normalized_sql = preg_replace('/\s+/', ' ', trim($sql));
        $key_data = $normalized_sql . serialize($params);
        return self::getInstance()->config['prefixes']['database'] . md5($key_data);
    }
    
    /**
     * Get cache statistics / Obter estatísticas do cache
     */
    public static function getStats() {
        $instance = self::getInstance();
        
        if ($instance->cache_backend instanceof FileCache) {
            return $instance->cache_backend->getStats();
        }
        
        return ['error' => 'Statistics not available for this cache backend'];
    }
    
    /**
     * Clean expired cache entries / Limpar entradas de cache expiradas
     */
    public static function cleanExpired() {
        $instance = self::getInstance();
        
        if ($instance->cache_backend instanceof FileCache) {
            return $instance->cache_backend->cleanExpired();
        }
        
        return 0;
    }
    
    /**
     * Warm up cache with common data / Pré-aquecer cache com dados comuns
     */
    public static function warmUp() {
        // This would typically be called during deployment or startup
        // to populate cache with frequently accessed data
        // Isso normalmente seria chamado durante deployment ou inicialização
        // para popular cache com dados frequentemente acessados
        
        $warmed = 0;
        
        // Example: Pre-cache active routes
        // Exemplo: Pré-cache de rotas ativas
        // $active_routes = Database::getActiveRoutes();
        // foreach ($active_routes as $route) {
        //     self::cacheRoute($route['id'], $route);
        //     $warmed++;
        // }
        
        return $warmed;
    }
}

/**
 * Performance monitoring decorator / Decorador de monitoramento de performance
 */
class CachedDatabaseQuery {
    private $pdo;
    private $cache_ttl;
    
    public function __construct($pdo, $cache_ttl = 900) {
        $this->pdo = $pdo;
        $this->cache_ttl = $cache_ttl;
    }
    
    /**
     * Execute query with caching / Executar consulta com cache
     */
    public function query($sql, $params = [], $force_refresh = false) {
        $start_time = microtime(true);
        
        // Check cache first / Verificar cache primeiro
        if (!$force_refresh) {
            $cached_result = VanTracingCache::getCachedQuery($sql, $params);
            if ($cached_result !== null) {
                // Log cache hit / Registrar acerto no cache
                if (class_exists('VanTracingLogger')) {
                    log_performance('cache_hit', microtime(true) - $start_time, 'ms', [
                        'query_type' => 'database',
                        'cache_key' => 'query_' . md5($sql . serialize($params))
                    ]);
                }
                return $cached_result;
            }
        }
        
        // Execute query / Executar consulta
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $execution_time = microtime(true) - $start_time;
            
            // Cache the result / Cache o resultado
            VanTracingCache::cacheQuery($sql, $params, $result, $this->cache_ttl);
            
            // Log performance / Registrar performance
            if (class_exists('ApiLogger')) {
                ApiLogger::logDatabaseQuery($sql, $execution_time, count($result));
            }
            
            return $result;
            
        } catch (Exception $e) {
            // Log error / Registrar erro
            if (class_exists('VanTracingLogger')) {
                log_error('Database Query Error', [
                    'sql' => $sql,
                    'params' => $params,
                    'error' => $e->getMessage()
                ], 'database');
            }
            
            throw $e;
        }
    }
}

/**
 * Helper functions / Funções auxiliares
 */

function cache_get($key, $default = null) {
    return VanTracingCache::get($key, $default);
}

function cache_set($key, $value, $ttl = null) {
    return VanTracingCache::set($key, $value, $ttl);
}

function cache_delete($key) {
    return VanTracingCache::delete($key);
}

function cache_remember($key, $callback, $ttl = null) {
    return VanTracingCache::remember($key, $callback, $ttl);
}

function cache_user($user_id, $user_data = null, $ttl = 1800) {
    if ($user_data !== null) {
        return VanTracingCache::cacheUser($user_id, $user_data, $ttl);
    }
    return VanTracingCache::getCachedUser($user_id);
}

function invalidate_user_cache($user_id) {
    return VanTracingCache::invalidateUser($user_id);
}
?>