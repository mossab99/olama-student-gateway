<?php

if (!defined('ABSPATH')) {
    exit;
}

interface Olama_Student_Gateway_Provider_Interface {
    public function key();

    public function is_available();

    public function get_data(array $context, array $args = array());
}

