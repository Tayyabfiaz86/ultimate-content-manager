<?php

class UCM_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'ucm_create_admin_menu'));
        add_action('admin_post_ucm_save_survey', array($this, 'save_survey'));
    }

    public function ucm_create_admin_menu() {
        add_menu_page(
            'Ultimate Content Manager',
            'Content Manager',
            'manage_options',
            'ucm',
            array($this, 'admin_page'),
            'dashicons-admin-generic',
            110
        );

        add_submenu_page(
            'ucm',
            'Survey/Test Details',
            'Survey/Test Details',
            'manage_options',
            'ucm-details',
            array($this, 'details_page')
        );

        add_submenu_page(
            'ucm',
            'Test Choices Results',
            'Test Choices Results',
            'manage_options',
            'ucm-results',
            array($this, 'results_page')
        );
        add_submenu_page(
            'ucm',
            'Survey Choices Results',
            'Survey Choices Results',
            'manage_options',
            'ucm-survey-choices-results',
            array($this, 'survey_choices_result')
        );

        add_submenu_page(
            'ucm',
            'Test Question&Answer Results',
            'Test Question&Answer Results',
            'manage_options',
            'ucm-test-question-answer-results',
            array($this, 'test_question_answer_result')
        );

        add_submenu_page(
            'ucm',
            'Survey Question&Answer Results',
            'Survey Question&Answer Results',
            'manage_options',
            'ucm-survey-question-answer-results',
            array($this, 'survey_question_answer_result')
        );
        add_submenu_page(
            'ucm',
            'Survey User Answers',
            '', // Hide from menu, only accessible via link
            'manage_options',
            'ucm-survey-user-answers',
            array($this, 'survey_user_answers_page')
        );
    }

    public function survey_user_answers_page() {
        include UCM_PATH . 'admin/views/survey-user-answers.php';
    }

    public function admin_page() {
        include UCM_PATH . 'admin/views/create-survey-test.php';
    }

    public function details_page() {
        include UCM_PATH . 'admin/views/survey-test-details.php';
    }
    public function test_question_answer_result() {
        include UCM_PATH . 'admin/views/test-question-answer-results.php';
    }

    public function results_page() {
        include UCM_PATH . 'admin/views/survey-test-results.php';
    }

    public function survey_question_answer_result() {
        include UCM_PATH . 'admin/views/survey-question-answer-results.php';
    }

    public function survey_choices_result() {
        include UCM_PATH . 'admin/views/survey-choices-results.php';
    }

    public function save_survey() {
        if (!isset($_POST['ucm_nonce']) || !wp_verify_nonce($_POST['ucm_nonce'], 'ucm_save_survey')) {
            wp_die('Invalid nonce');
        }

        global $wpdb;

        $name = sanitize_text_field($_POST['name']);
        $type = sanitize_text_field($_POST['type']);
        if (isset($_POST['fail_percentage'])) {
            $failed_percentage = sanitize_text_field($_POST['fail_percentage']);
        } else {
            $failed_percentage = null; // or set a default value if needed
        }
        $survey_type = sanitize_text_field($_POST['survey_type']);
        $test_type = sanitize_text_field($_POST['test_type']);
        $survey_id = $wpdb->insert(
            "{$wpdb->prefix}ucm_surveys",
            array(
                'name' => $name,
                'type' => $type,
                'failed_percentage'=>$failed_percentage
            ),
            array('%s', '%s')
        );

        $survey_id = $wpdb->insert_id;

        if ($type == 'survey') {
            $questions = $_POST['survey_questions'];
            $choices = $_POST['survey_choices'];
            $correct_answers = [];
        } elseif ($type == 'test') {
            $questions = $_POST['test_questions'];
            $choices = $_POST['test_choices'];
            $correct_answers = $_POST['test_correct'];
        } else {
            wp_die('Invalid type');
        }

        foreach ($questions as $index => $question) {
            $question_id = $wpdb->insert(
                "{$wpdb->prefix}ucm_questions",
                array(
                    'survey_id' => $survey_id,
                    'question' => sanitize_text_field($question),
                    'type' => $type == 'survey' ? $survey_type : $test_type
                ),
                array('%d', '%s', '%s')
            );

            $question_id = $wpdb->insert_id;

            if (($type == 'test' && $test_type == 'multiple_choices') || ($type == 'survey' && $survey_type == 'multiple_choices')) {
                for ($i = 0; $i < 4; $i++) {
                    $wpdb->insert(
                        "{$wpdb->prefix}ucm_choices",
                        array(
                            'question_id' => $question_id,
                            'choice' => sanitize_text_field($choices[$index * 4 + $i]),
                            'is_correct' => isset($correct_answers[$index]) && $correct_answers[$index] == $i + 1
                        ),
                        array('%d', '%s', '%d')
                    );
                }
            }
        }

        $referer = wp_get_referer();
        $redirect_url = add_query_arg(array('message' => 'success', 'type' => $type), $referer);

        // Redirect back to the referring page with a success message
        wp_redirect($redirect_url);
        exit;
    }
}

new UCM_Admin();