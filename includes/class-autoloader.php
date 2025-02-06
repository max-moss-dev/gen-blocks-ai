<?php

namespace GENB;

class Autoloader {
    public function __construct() {
        spl_autoload_register([$this, 'autoload']);
    }

    public function autoload($class) {
        // Only handle classes in our namespace
        if (strpos($class, 'GENB\\') !== 0) {
            return;
        }

        $class = str_replace('GENB\\', '', $class);
        $class = str_replace('_', '-', strtolower($class));
        $file = GENB_PLUGIN_DIR . 'includes/class-' . $class . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}

new Autoloader();