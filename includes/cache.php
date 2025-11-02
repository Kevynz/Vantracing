<?php
/**
 * VanTracing - Simple File Cache System
 * Sistema de Cache de Arquivos Simples
 * 
 * A lightweight file-based caching system for improved performance
 * Sistema de cache baseado em arquivos para melhor performance
 * 
 * @package VanTracing
 * @version 2.0
 * @author Kevyn
 */

class SimpleCache {
    
    private $cache_dir;
    private $default_ttl;
    private $enabled;
    
    /**
     * Constructor
     * Construtor
     */
    public function __construct($cache_dir = null, $default_ttl = 3600) {
        $this->cache_dir = $cache_dir ?: __DIR__ . '/../cache/';
        $this->default_ttl = $default_ttl;
        $this->enabled = getenv('CACHE_ENABLED') !== 'false';
        
        // Create cache directory if it doesn't exist / Criar diretório de cache se não existir
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Generate cache key
     * Gerar chave de cache
     */
    private function getCacheKey($key) {
        return md5($key) . '.cache';
    }
    
    /**
     * Get cache file path
     * Obter caminho do arquivo de cache
     */
    private function getCacheFilePath($key) {
        return $this->cache_dir . $this->getCacheKey($key);
    }
    
    /**
     * Store data in cache
     * Armazenar dados no cache
     */
    public function set($key, $data, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?: $this->default_ttl;
        $cache_file = $this->getCacheFilePath($key);
        
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $success = file_put_contents(
            $cache_file, 
            serialize($cache_data), 
            LOCK_EX
        ) !== false;
        
        if ($success) {
            // Log cache write / Registrar escrita no cache
            error_log("Cache SET: {$key} (TTL: {$ttl}s)");
        }
        
        return $success;
    }
    
    /**
     * Retrieve data from cache
     * Recuperar dados do cache
     */
    public function get($key, $default = null) {
        if (!$this->enabled) {
            return $default;
        }
        
        $cache_file = $this->getCacheFilePath($key);
        
        if (!file_exists($cache_file)) {
            return $default;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        if (!$cache_data || !is_array($cache_data)) {
            $this->delete($key);
            return $default;
        }
        
        // Check if cache has expired / Verificar se o cache expirou
        if (time() > $cache_data['expires']) {
            $this->delete($key);
            error_log("Cache EXPIRED: {$key}");
            return $default;
        }
        
        error_log("Cache HIT: {$key}");
        return $cache_data['data'];
    }
    
    /**
     * Check if cache key exists and is valid
     * Verificar se a chave de cache existe e é válida
     */
    public function has($key) {
        if (!$this->enabled) {
            return false;
        }
        
        $cache_file = $this->getCacheFilePath($key);
        
        if (!file_exists($cache_file)) {
            return false;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        if (!$cache_data || time() > $cache_data['expires']) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Delete cache entry
     * Deletar entrada do cache
     */
    public function delete($key) {
        $cache_file = $this->getCacheFilePath($key);
        
        if (file_exists($cache_file)) {
            unlink($cache_file);
            error_log("Cache DELETE: {$key}");
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear all cache
     * Limpar todo o cache
     */
    public function clear() {
        if (!is_dir($this->cache_dir)) {
            return true;
        }
        
        $files = glob($this->cache_dir . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        error_log("Cache CLEAR: {$deleted} files deleted");
        return $deleted;
    }
    
    /**
     * Get or set cache with callback
     * Obter ou definir cache com callback
     */
    public function remember($key, $callback, $ttl = null) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = call_user_func($callback);
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Get cache statistics
     * Obter estatísticas do cache
     */
    public function getStats() {
        if (!is_dir($this->cache_dir)) {
            return ['files' => 0, 'size' => 0];
        }
        
        $files = glob($this->cache_dir . '*.cache');
        $total_size = 0;
        $valid_files = 0;
        $expired_files = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $cache_data = unserialize(file_get_contents($file));
            if ($cache_data && time() <= $cache_data['expires']) {
                $valid_files++;
            } else {
                $expired_files++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $valid_files,
            'expired_files' => $expired_files,
            'total_size' => $total_size,
            'directory' => $this->cache_dir,
            'enabled' => $this->enabled
        ];
    }
    
    /**
     * Clean expired cache entries
     * Limpar entradas de cache expiradas
     */
    public function cleanExpired() {
        if (!is_dir($this->cache_dir)) {
            return 0;
        }
        
        $files = glob($this->cache_dir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $cache_data = unserialize(file_get_contents($file));
            
            if (!$cache_data || time() > $cache_data['expires']) {
                unlink($file);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            error_log("Cache CLEANUP: {$cleaned} expired files removed");
        }
        
        return $cleaned;
    }
}

// Global cache instance / Instância global do cache
$GLOBALS['cache'] = new SimpleCache();

/**
 * Helper function to get global cache instance
 * Função auxiliar para obter instância global do cache
 */
function cache() {
    return $GLOBALS['cache'];
}

/**
 * Cache a database query result
 * Cachear resultado de consulta do banco de dados
 */
function cache_query($sql, $params = [], $ttl = 300) {
    $cache_key = 'query_' . md5($sql . serialize($params));
    
    return cache()->remember($cache_key, function() use ($sql, $params) {
        global $conn;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, $ttl);
}

/**
 * Cache user data
 * Cachear dados do usuário
 */
function cache_user_data($user_id, $ttl = 600) {
    $cache_key = "user_{$user_id}";
    
    return cache()->remember($cache_key, function() use ($user_id) {
        global $conn;
        
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, $ttl);
}

/**
 * Invalidate user cache
 * Invalidar cache do usuário
 */
function invalidate_user_cache($user_id) {
    cache()->delete("user_{$user_id}");
}

/**
 * Cache API response
 * Cachear resposta da API
 */
function cache_api_response($endpoint, $params = [], $ttl = 300) {
    $cache_key = 'api_' . md5($endpoint . serialize($params));
    
    return cache()->get($cache_key);
}

/**
 * Set API response cache
 * Definir cache de resposta da API
 */
function set_api_response_cache($endpoint, $params, $response, $ttl = 300) {
    $cache_key = 'api_' . md5($endpoint . serialize($params));
    
    return cache()->set($cache_key, $response, $ttl);
}

// Schedule cache cleanup (if running via cron) / Agendar limpeza do cache (se executado via cron)
if (php_sapi_name() === 'cli') {
    register_shutdown_function(function() {
        cache()->cleanExpired();
    });
}
?>