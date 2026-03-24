<?php

class CompressionMiddleware {
    
    public static function enable() {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
        
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
    }
    
    public static function compressOutput($buffer) {
        $buffer = preg_replace('/\s+/', ' ', $buffer);
        $buffer = preg_replace('/>\s+</', '><', $buffer);
        return $buffer;
    }
}
