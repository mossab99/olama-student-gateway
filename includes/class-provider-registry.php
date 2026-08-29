<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Provider_Registry {
    private $providers = array();

    public function register(Olama_Student_Gateway_Provider_Interface $provider) {
        $key = sanitize_key($provider->key());
        if (!$key) {
            return false;
        }
        $this->providers[$key] = $provider;
        return true;
    }

    public function get($key) {
        $key = sanitize_key($key);
        return isset($this->providers[$key]) ? $this->providers[$key] : null;
    }

    public function available($key) {
        $provider = $this->get($key);
        return $provider && $provider->is_available();
    }

    public function data($key, array $context, array $args = array()) {
        $provider = $this->get($key);
        if (!$provider) {
            return new WP_Error('olama_gateway_provider_missing', __('The requested information provider is not registered.', 'olama-student-gateway'));
        }
        if (!$provider->is_available()) {
            return new WP_Error('olama_gateway_provider_unavailable', __('This information is not available yet.', 'olama-student-gateway'));
        }
        try {
            return $provider->get_data($context, $args);
        } catch (Throwable $error) {
            do_action('olama_student_gateway_provider_error', $key, $error, $context, $args);
            return new WP_Error('olama_gateway_provider_failed', __('The information could not be loaded. Please try again later.', 'olama-student-gateway'));
        }
    }

    public function keys() {
        return array_keys($this->providers);
    }
}

