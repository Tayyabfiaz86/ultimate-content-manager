<?php

class UCM_Shortcodes {

    public function __construct() {
        add_shortcode('ucm_surveys', array($this, 'display_surveys'));
        add_shortcode('ucm_tests', array($this, 'display_tests'));
        add_shortcode('ucm_survey', array($this, 'display_single_survey'));
        add_shortcode('ucm_test', array($this, 'display_single_test'));
        add_action('init', array($this, 'handle_form_submission'));
    }

    public function display_surveys($atts) {
        global $wpdb;

        $surveys = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE type = 'survey'");

        if (empty($surveys)) {
            return '<p>No surveys found.</p>';
        }

        $output = '<div class="ucm-surveys">';
        foreach ($surveys as $survey) {
            $output .= '<div class="ucm-survey">';
            $output .= '<h3>' . esc_html($survey->name) . '</h3>';
            $output .= $this->display_questions($survey->id, 'survey');
            $output .= '<p>Shortcode: [ucm_survey id="' . esc_html($survey->id) . '"]</p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function display_tests($atts) {
        global $wpdb;

        $tests = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE type = 'test'");

        if (empty($tests)) {
            return '<p>No tests found.</p>';
        }

        $output = '<div class="ucm-tests">';
        foreach ($tests as $test) {
            $output .= '<div class="ucm-test">';
            $output .= '<h3>' . esc_html($test->name) . '</h3>';
            $output .= $this->display_questions($test->id, 'test');
            $output .= '<p>Shortcode: [ucm_test id="' . esc_html($test->id) . '"]</p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function display_single_survey($atts) {
        global $wpdb;

        $atts = shortcode_atts(array('id' => 0), $atts, 'ucm_survey');
        $survey_id = intval($atts['id']);

        $survey = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d AND type = 'survey'", $survey_id));

        if (!$survey) {
            return '<p>Survey not found.</p>';
        }

        $output = '<div class="ucm-survey">';
        $output .= '<h3>' . esc_html($survey->name) . '</h3>';
        $output .= '<form method="post" action="">';
        $output .= '<p><label for="ucm_name">Name:</label> <input type="text" name="ucm_name" required></p>';
        $output .= '<p><label for="ucm_email">Email:</label> <input type="email" name="ucm_email" required></p>';
        $output .= $this->display_questions($survey->id, 'survey');
        $output .= '<input type="hidden" name="ucm_survey_id" value="' . esc_attr($survey->id) . '">';
        $output .= '<input type="hidden" name="ucm_type" value="survey">';
        $output .= '<p><input type="submit" name="ucm_submit" value="Submit"></p>';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    public function display_single_test($atts) {
        global $wpdb;

        $atts = shortcode_atts(array('id' => 0), $atts, 'ucm_test');
        $test_id = intval($atts['id']);

        $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d AND type = 'test'", $test_id));

        if (!$test) {
            return '<p>Test not found.</p>';
        }

        $output = '<div class="ucm-test">';
        $output .= '<h3>' . esc_html($test->name) . '</h3>';
        $output .= '<form method="post" action="">';
        $output .= '<p><label for="ucm_name">Name:</label> <input type="text" name="ucm_name" required></p>';
        $output .= '<p><label for="ucm_email">Email:</label> <input type="email" name="ucm_email" required></p>';
        $output .= $this->display_questions($test->id, 'test');
        $output .= '<input type="hidden" name="ucm_test_id" value="' . esc_attr($test->id) . '">';
        $output .= '<input type="hidden" name="ucm_type" value="test">';
        $output .= '<p><input type="submit" name="ucm_submit" value="Submit"></p>';
        $output .= '</form>';
        $output .= '</div>';

        return $output;
    }

    private function display_questions($survey_id, $type) {
        global $wpdb;

        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d", $survey_id));

        if (empty($questions)) {
            return '<p>No questions found.</p>';
        }

        $output = '<div class="ucm-questions">';
        foreach ($questions as $question) {
            $output .= '<div class="ucm-question">';
            $output .= '<p>' . esc_html($question->question) . '</p>';
            if ($question->type == 'multiple_choices') {
                $output .= $this->display_choices($question->id);
            } else if ($question->type == 'question_answers') {
                $output .= '<p><input type="text" name="ucm_answers[' . esc_attr($question->id) . ']" required></p>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    private function display_choices($question_id) {
        global $wpdb;

        $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_choices WHERE question_id = %d", $question_id));

        if (empty($choices)) {
            return '<p>No choices found.</p>';
        }

        $output = '<ul class="ucm-choices">';
        foreach ($choices as $choice) {
            $output .= '<li><label><input type="radio" name="ucm_answers[' . esc_attr($question_id) . ']" value="' . esc_attr($choice->id) . '" required> ' . esc_html($choice->choice) . '</label></li>';
        }
        $output .= '</ul>';

        return $output;
    }

    public function handle_form_submission() {
        if (isset($_POST['ucm_submit'])) {
            global $wpdb;
    
            $name = sanitize_text_field($_POST['ucm_name']);
            $email = sanitize_email($_POST['ucm_email']);
            $answers = $_POST['ucm_answers'];
            $survey_id = isset($_POST['ucm_survey_id']) ? intval($_POST['ucm_survey_id']) : 0;
            $test_id = isset($_POST['ucm_test_id']) ? intval($_POST['ucm_test_id']) : 0;
    
            // Fetch the questions and their types
            $question_ids = array_keys($answers);
            $placeholders = implode(',', array_fill(0, count($question_ids), '%d'));
    
            $questions = $wpdb->get_results($wpdb->prepare(
                "SELECT id, type FROM {$wpdb->prefix}ucm_questions WHERE id IN ($placeholders)",
                ...$question_ids
            ));
    
            // Separate answers by type
            $multiple_choices_answers = [];
            $question_answers = [];
    
            foreach ($questions as $question) {
                $question_id = $question->id;
                $type = $question->type;
                $answer = $answers[$question_id];
    
                if ($type === 'multiple_choices') {
                    $multiple_choices_answers[$question_id] = $answer;
                } else if ($type === 'question_answers') {
                    $question_answers[$question_id] = $answer;
                }
            }
    
            // Process multiple_choices answers
            if (!empty($multiple_choices_answers)) {

                

                if ($survey_id>0) {
                    // Fetch the survey details
                    $survey = $wpdb->get_row($wpdb->prepare("SELECT id, total_submit_count FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));
        
                    if ($survey) {
                        // Increment the total_submit_count
                        $total_submit_count = $survey->total_submit_count + 1;
        
                        // Update the wp_ucm_surveys table
                        $wpdb->update(
                            "{$wpdb->prefix}ucm_surveys",
                            array(
                                'total_submit_count' => $total_submit_count
                            ),
                            array('id' => $survey->id),
                            array('%d'),
                            array('%d')
                        );
        
                        // Save results in the database
                        $wpdb->insert(
                            "{$wpdb->prefix}ucm_results",
                            array(
                                'survey_id' => $survey_id,
                                'name' => $name,
                                'email' => $email,
                                'answers' => maybe_serialize($answers),
                                'submitted_at' => current_time('mysql')
                            ),
                            array(
                                '%d',
                                '%s',
                                '%s',
                                '%s',
                                '%s'
                            )
                        );
                    }
                }

                // Fetch the survey details
                $test = $wpdb->get_row($wpdb->prepare("SELECT id, total_submit_count, failed_percentage,passed_submit_count,failed_submit_count FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $test_id));

                if ($test) {
                    // Increment the total_submit_count
                    $total_submit_count = $test->total_submit_count + 1;

                    // Update the wp_ucm_surveys table
                    

                    // Serialize answers
                    $serialized_answers = maybe_serialize($multiple_choices_answers);

                    // Calculate correct_count, wrong_count, score, and passed
                    $correct_count = 0;
                    $wrong_count = 0;
                    $total_questions = count($multiple_choices_answers);
                    $correct_answers_map = [];

                    // Fetch correct answers from the database for multiple_choices
                    $correct_answers = $wpdb->get_results($wpdb->prepare(
                        "SELECT question_id, id AS choice_id FROM {$wpdb->prefix}ucm_choices WHERE is_correct = 1 AND question_id IN ($placeholders)",
                        ...array_keys($multiple_choices_answers)
                    ));

                    foreach ($correct_answers as $correct_answer) {
                        $correct_answers_map[$correct_answer->question_id] = $correct_answer->choice_id;
                        if (isset($multiple_choices_answers[$correct_answer->question_id]) && $multiple_choices_answers[$correct_answer->question_id] == $correct_answer->choice_id) {
                            $correct_count++;
                        } else {
                            $wrong_count++;
                        }
                    }

                    // Calculate score as a percentage
                    $score = ($total_questions > 0) ? ($correct_count / $total_questions) * 100 : 0;

                    // Determine if the user passed or failed
                    $fail_percentage = $test->failed_percentage; // Example fail percentage, replace with actual value if needed
                    $passed = ($score >= $fail_percentage) ? 1 : 0;

                    if ($passed) {
                        $passed_submit_count = $test->passed_submit_count + 1;
                        $failed_submit_count = $test->failed_submit_count;
                    } else {
                        $passed_submit_count = $test->passed_submit_count;
                        $failed_submit_count = $test->failed_submit_count + 1;
                    }

                    

                    $wpdb->update(
                        "{$wpdb->prefix}ucm_surveys",
                        array(
                            'total_submit_count' => $total_submit_count,
                            'passed_submit_count' => $passed_submit_count,
                            'failed_submit_count' => $failed_submit_count
                        ),
                        array('id' => $test->id),
                        array('%d', '%d', '%d'),
                        array('%d')
                    ); 

                    // Save results in the database
                    $wpdb->insert(
                        "{$wpdb->prefix}ucm_results",
                        array(
                            'test_id' => $test_id,
                            'name' => $name,
                            'email' => $email,
                            'answers' => $serialized_answers,
                            'correct_count' => $correct_count,
                            'wrong_count' => $wrong_count,
                            'score' => $score,
                            'passed' => $passed,
                            'submitted_at' => current_time('mysql')
                        ),
                        array(
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%f',
                            '%d',
                            '%s'
                        )
                    );
    
                    
                }
            }
    
            // Process question_answers
            if (!empty($question_answers)) {
                foreach ($question_answers as $question_id => $answer) {
                    $wpdb->insert(
                        "{$wpdb->prefix}ucm_question_answers_results",
                        array(
                            'survey_id' => $survey_id,
                            'test_id' => $test_id,
                            'name' => $name,
                            'email' => $email,
                            'question_id' => $question_id,
                            'answer' => maybe_serialize($answer),
                            'submitted_at' => current_time('mysql')
                        ),
                        array(
                            '%d',
                            '%d',
                            '%s',
                            '%s',
                            '%d',
                            '%s',
                            '%s'
                        )
                    );
                }
            }
    
            // Redirect or display a message after form submission
            wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
            exit;
        }
    }
}