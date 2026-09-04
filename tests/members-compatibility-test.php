<?php

define('ABSPATH', __DIR__);

class WP_Post {
    public $post_content = '';
}

$test_is_admin = false;
$test_is_feed = false;
$test_post = new WP_Post();

function is_admin() {
    global $test_is_admin;
    return $test_is_admin;
}

function is_feed() {
    global $test_is_feed;
    return $test_is_feed;
}

function get_the_ID() {
    return 42;
}

function get_post($post_id) {
    global $test_post;
    return 42 === $post_id ? $test_post : null;
}

function has_shortcode($content, $tag) {
    return false !== strpos($content, '[' . $tag . ']');
}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/includes/class-plugin.php';

$reflection = new ReflectionClass('Olama_Student_Gateway_Plugin');
$plugin = $reflection->newInstanceWithoutConstructor();
$denial = '<div class="members-access-error">Denied</div>';

$test_post->post_content = '[olama_student_gateway]';
assert_same(
    '[olama_student_gateway]',
    $plugin->replace_members_denial_with_gateway($denial),
    'The current gateway shortcode should replace the Members denial.'
);

$test_post->post_content = '[olama_family_gateway]';
assert_same(
    '[olama_student_gateway]',
    $plugin->replace_members_denial_with_gateway($denial),
    'The legacy gateway shortcode should replace the Members denial.'
);

$test_post->post_content = 'Ordinary protected content';
assert_same(
    $denial,
    $plugin->replace_members_denial_with_gateway($denial),
    'Unrelated protected content must remain protected.'
);

$test_post->post_content = '[olama_student_gateway]';
$test_is_admin = true;
assert_same(
    $denial,
    $plugin->replace_members_denial_with_gateway($denial),
    'Admin content must not be changed.'
);

$test_is_admin = false;
$test_is_feed = true;
assert_same(
    $denial,
    $plugin->replace_members_denial_with_gateway($denial),
    'Feed content must not expose the gateway.'
);

echo "Members compatibility tests passed.\n";
