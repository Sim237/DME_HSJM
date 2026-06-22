<?php
/**
 * SimCare+ — Dossier Médical Électronique (DME)
 * Copyright (c) 2024-2026 Franck Simeni. Tous droits réservés.
 * Développé pour la gestion hospitalière, et le bien être numérique des patients.
 *
 * Toute reproduction, modification ou distribution de ce logiciel,
 * en tout ou en partie, sans autorisation écrite préalable de l'auteur
 * est strictement interdite et constitue une contrefaçon.
 *
 * Protected under OAPI Agreement — Annexe VII · Berne Convention
 */


class CacheService {
    private static $instance = null;
    private $redis = null;
    private $enabled = false;
    
    private function __construct() {
        if (extension_loaded('redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->enabled = true;
            } catch (Exception $e) {
                $this->enabled = false;
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if (!$this->enabled) return null;
        
        try {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function set($key, $value, $ttl = 3600) {
        if (!$this->enabled) return false;
        
        try {
            return $this->redis->setex($key, $ttl, json_encode($value));
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function delete($key) {
        if (!$this->enabled) return false;
        
        try {
            return $this->redis->del($key);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function flush() {
        if (!$this->enabled) return false;
        
        try {
            return $this->redis->flushDB();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function remember($key, $ttl, $callback) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}
