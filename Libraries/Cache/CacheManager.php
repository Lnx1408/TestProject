<?php
namespace Libraries\Cache;

class CacheManager {
    private static $instance = null;
    private $cacheDir;
    private $defaultTtl = 604800; // 7 días en segundos

    private function __construct() {
        $this->cacheDir = dirname(__DIR__, 2) . '/Storage/cache/';
        
        // Crear el directorio de caché si no existe
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Guardar un valor en caché
     * 
     * @param string $key Clave única para identificar el valor
     * @param mixed $value Valor a almacenar
     * @param int|null $ttl Tiempo de vida en segundos (null para usar el valor por defecto)
     * @return bool Éxito de la operación
     */
    public function set($key, $value, $ttl = null) {
        $cacheFile = $this->getCacheFilePath($key);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'expires_at' => time() + $ttl,
            'data' => $value
        ];
        
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    /**
     * Obtener un valor de la caché
     * 
     * @param string $key Clave del valor a obtener
     * @param mixed $default Valor por defecto si no existe o ha expirado
     * @return mixed Valor almacenado o valor por defecto
     */
    public function get($key, $default = null) {
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        // Verificar si ha expirado
        if (time() > $data['expires_at']) {
            $this->delete($key);
            return $default;
        }
        
        return $data['data'];
    }
    
    /**
     * Verificar si una clave existe y no ha expirado
     * 
     * @param string $key Clave a verificar
     * @return bool Resultado de la verificación
     */
    public function has($key) {
        $cacheFile = $this->getCacheFilePath($key);
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        // Verificar si ha expirado
        if (time() > $data['expires_at']) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Eliminar una clave de la caché
     * 
     * @param string $key Clave a eliminar
     * @return bool Éxito de la operación
     */
    public function delete($key) {
        $cacheFile = $this->getCacheFilePath($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Eliminar todas las claves de la caché
     * 
     * @return bool Éxito de la operación
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Obtener o calcular un valor
     * 
     * @param string $key Clave única
     * @param callable $callback Función para calcular el valor si no existe
     * @param int|null $ttl Tiempo de vida en segundos
     * @return mixed Valor almacenado o calculado
     */
    public function remember($key, callable $callback, $ttl = null) {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Obtener la ruta del archivo de caché
     * 
     * @param string $key Clave del valor
     * @return string Ruta completa del archivo
     */
    private function getCacheFilePath($key) {
        $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        return $this->cacheDir . $safeKey . '.cache';
    }
    
    // Evitar la clonación
    private function __clone() {}
    
    // Evitar la deserialización
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}