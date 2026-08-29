<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Filter_Provider implements Olama_Student_Gateway_Provider_Interface {
    private $key;
    private $availability_callback;

    public function __construct($key, $availability_callback = null) {
        $this->key = sanitize_key($key);
        $this->availability_callback = $availability_callback;
    }

    public function key() {
        return $this->key;
    }

    public function is_available() {
        if (is_callable($this->availability_callback)) {
            return (bool) call_user_func($this->availability_callback);
        }
        return false !== has_filter('olama_student_gateway_' . $this->key . '_data');
    }

    public function get_data(array $context, array $args = array()) {
        return apply_filters('olama_student_gateway_' . $this->key . '_data', array(), $context, $args);
    }
}
