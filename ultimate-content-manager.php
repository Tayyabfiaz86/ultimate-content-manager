<?php
/**
 * Plugin Name: Ultimate Content Manager
 * Description: A plugin that includes multiple content management tools such as surveys, tests, reactions, and more.
 * Version: 1.0.0
 * Author: Tayyab Fiaz
 * Text Domain: ultimate-content-manager
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('UCM_PATH', plugin_dir_path(__FILE__));
define('UCM_URL', plugin_dir_url(__FILE__));

// require_once plugin_dir_path(__FILE__) . 'includes/class-ucm-admin.php';

// Include the shortcode class
require_once plugin_dir_path(__FILE__) . 'includes/class-ucm-shortcodes.php';

// Initialize the admin class
// new UCM_Admin();

// Initialize the shortcode class
new UCM_Shortcodes();

// Include necessary files
require_once UCM_PATH . 'includes/functions.php';
require_once UCM_PATH . 'includes/class-ucm-admin.php';
require_once UCM_PATH . 'includes/class-ucm-frontend.php';

// Enqueue admin and frontend assets
add_action('admin_enqueue_scripts', 'ucm_enqueue_admin_assets');
add_action('wp_enqueue_scripts', 'ucm_enqueue_frontend_assets');

function ucm_enqueue_admin_assets() {
    wp_enqueue_style('ucm-admin-style', UCM_URL . 'admin/css/admin-style.css');
    // wp_enqueue_script('ucm-admin-script', UCM_URL . 'admin/js/admin-script.js', array('jquery'), false, true);
}

function enqueue_admin_scripts() {
    wp_enqueue_script(
        'ucm-admin-script', // Handle
        UCM_URL . 'admin/js/admin-script.js', // Corrected path to the script file
        array('jquery'), // Dependencies
        false, // Version
        true // Load in footer
    );

    // Localize the script with the AJAX URL
    wp_localize_script('ucm-admin-script', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');


function ucm_enqueue_frontend_assets() {
    wp_enqueue_style('ucm-frontend-style', UCM_URL . 'frontend/css/frontend-style.css');
    wp_enqueue_script('ucm-frontend-script', UCM_URL . 'frontend/js/frontend-script.js', array('jquery'), false, true);
}

/**
 * Front-end preview handler: renders shortcode inside theme when ?ucm_preview=1&id=..&type=..
 */
add_action('template_redirect', 'ucm_handle_frontend_preview');
function ucm_handle_frontend_preview() {
    if (empty($_GET['ucm_preview'])) {
        return;
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

    if (!$id || !in_array($type, array('survey','test'), true)) {
        return;
    }

    // Render within theme header/footer so preview matches front-end appearance
    status_header(200);
    nocache_headers();
    // Ensure shortcodes are available
    echo get_header();
    echo do_shortcode('[ucm_' . esc_attr($type) . ' id="' . intval($id) . '"]');
    echo get_footer();
    exit;
}




// Create database tables on plugin activation
register_activation_hook(__FILE__, 'ucm_create_tables');

function ucm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tables = [
        "CREATE TABLE {$wpdb->prefix}ucm_surveys (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            failed_percentage BIGINT(20) UNSIGNED DEFAULT 0,
            total_submit_count BIGINT(20) UNSIGNED DEFAULT 0,
            failed_submit_count BIGINT(20) UNSIGNED DEFAULT 0,
            passed_submit_count BIGINT(20) UNSIGNED DEFAULT 0,
            summary VARCHAR(450) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}ucm_questions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            survey_id BIGINT(20) UNSIGNED NOT NULL,
            question TEXT NOT NULL,
            type VARCHAR(50) NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (survey_id) REFERENCES {$wpdb->prefix}ucm_surveys(id) ON DELETE CASCADE
        ) $charset_collate;",

        "CREATE TABLE {$wpdb->prefix}ucm_choices (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id BIGINT(20) UNSIGNED NOT NULL,
            choice TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            PRIMARY KEY (id),
            FOREIGN KEY (question_id) REFERENCES {$wpdb->prefix}ucm_questions(id) ON DELETE CASCADE
        ) $charset_collate;"
    ];

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $table) {
        dbDelta($table);
    }
}

register_activation_hook(__FILE__, 'ucm_create_results_table');

function ucm_create_results_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ucm_results';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        survey_id mediumint(9) NOT NULL,
        test_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        answers text NOT NULL,
        correct_answers mediumint(9) NOT NULL,
        score mediumint(9) NOT NULL,
        correct_count mediumint(9) NOT NULL,
        wrong_count mediumint(9) NOT NULL,
        passed mediumint(9) NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Function to create the ucm_question_answers_results table
function create_ucm_question_answers_results_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ucm_question_answers_results';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        survey_id bigint(20) NOT NULL,
        test_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        question_id bigint(20) NOT NULL,
        answer text NOT NULL,
        is_correct bigint(20) NOT NULL,
        submitted_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY survey_id (survey_id),
        KEY test_id (test_id),
        KEY question_id (question_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook the function to plugin activation
register_activation_hook(__FILE__, 'create_ucm_question_answers_results_table');