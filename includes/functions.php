<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;



add_action('wp_ajax_ucm_fetch_details', 'ucm_fetch_details');
add_action('wp_ajax_nopriv_ucm_fetch_details', 'ucm_fetch_details');

function ucm_fetch_details() {
    global $wpdb;

    $result_id = intval($_POST['result_id']);

    // Fetch result details
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_results WHERE id = %d", $result_id));

    if ($result) {
        // Fetch test title
        $test_title = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $result->test_id));

        // Fetch questions and answers
        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d", $result->test_id));
        $answers = unserialize($result->answers);

        $output = '<div class="ucm-question-details">';
        $output .= '<h3>' . esc_html($test_title) . '</h3>';
        $output .= '<div class="ucm-questions-container">';
        $output .= '<ul>';

        foreach ($questions as $question) {
            $user_answer_id = isset($answers[$question->id]) ? $answers[$question->id] : '';
            $user_answer = '';
            $correct_answer = '';

            if ($question->type == 'multiple_choices') {
                // Fetch choices for the question
                $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_choices WHERE question_id = %d", $question->id));
                
                // Find the correct answer and user's answer text
                foreach ($choices as $choice) {
                    if ($choice->id == $user_answer_id) {
                        $user_answer = $choice->choice;
                    }
                    if ($choice->is_correct) {
                        $correct_answer = $choice->choice;
                    }
                }
            } else {
                $correct_answer = $question->correct_answer;
                $user_answer = $user_answer_id; // For non-multiple choice, the answer is stored directly
            }

            $is_correct = $user_answer == $correct_answer;

            $output .= '<li>';
            $output .= '<p class="ucm-question-text">' . esc_html($question->question) . '</p>';
            $output .= '<p>User Answer: <span class="' . ($is_correct ? 'correct' : 'incorrect') . '">' . esc_html($user_answer) . '</span></p>';
            $output .= '<p>Correct Answer: <span class="correct">' . esc_html($correct_answer) . '</span></p>';
            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '</div>'; // Close ucm-questions-container
        $output .= '</div>'; // Close ucm-question-details

        echo $output;
    } else {
        echo '<div class="ucm-question-details"><p>No data found.</p></div>';
    }
    wp_die();
}



add_action('wp_ajax_get_survey_details', 'get_survey_details');

function get_survey_details() {
    global $wpdb;

    $survey_id = isset($_POST['survey_id']) ? intval($_POST['survey_id']) : 0;

    if ($survey_id) {
        $survey = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));
        $questions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d AND type = 'multiple_choices'", $survey_id));

        if ($survey) {
            echo '<div class="survey-choice-results-header">';
            echo '<h2>' . esc_html($survey->name) . '</h2>';
            echo '<p>Total Responses: ' . esc_html($survey->total_submit_count) . '</p>';
            echo '</div>';
            $summary = $wpdb->get_var($wpdb->prepare("SELECT summary FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));

            echo '<div class="survey-choice-results-content">';

            $question_number = 1;
            foreach ($questions as $question) {
                echo '<div class="question">';
                echo '<h3><span class="question-number">' . $question_number . '.</span> ' . esc_html($question->question) . '</h3>';

                $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_choices WHERE question_id = %d", $question->id));
                $results = $wpdb->get_results($wpdb->prepare("SELECT answers FROM {$wpdb->prefix}ucm_results WHERE survey_id = %d", $survey_id));

                $choice_counts = array();
                foreach ($choices as $choice) {
                    $choice_counts[$choice->choice] = 0;
                }

                foreach ($results as $result) {
                    $answers = maybe_unserialize($result->answers);
                    if (is_array($answers) && isset($answers[$question->id])) {
                        $selected_choice_id = $answers[$question->id];
                        $selected_choice = $wpdb->get_var($wpdb->prepare("SELECT choice FROM {$wpdb->prefix}ucm_choices WHERE id = %d", $selected_choice_id));
                        if (isset($choice_counts[$selected_choice])) {
                            $choice_counts[$selected_choice]++;
                        }
                    }
                }

                // Determine the most chosen answer
                $most_chosen_count = max($choice_counts);
                $most_chosen_choice = array_search($most_chosen_count, $choice_counts);

                echo '<ul>';
                $choice_number = 1;
                foreach ($choice_counts as $choice => $count) {
                    $class = ($choice === $most_chosen_choice) ? 'most-chosen' : '';
                    echo '<li class="' . esc_attr($class) . '">' . $choice_number . '. ' . esc_html($choice) . ' (' . esc_html($count) . ' people chose this)</li>';
                    $choice_number++;
                }
                echo '</ul>';
                echo '</div>';
                $question_number++;
            }
        } else {
            echo '<p>Survey not found.</p>';
        }
    } else {
        echo '<p>Invalid survey ID.</p>';
    }

    wp_die();
}

add_action('rest_api_init', function () {
    register_rest_route('ucm/v1', '/summary', array(
        'methods' => 'POST',
        'callback' => 'handle_summary',
        'permission_callback' => '__return_true', // Adjust this for proper permissions
    ));
});

function handle_summary(WP_REST_Request $request) {
    global $wpdb;

    $survey_id = intval($request->get_param('survey_id'));

    // Fetch the existing summary
    $table_name = $wpdb->prefix . 'ucm_surveys';
    $summary = $wpdb->get_var($wpdb->prepare("SELECT summary FROM $table_name WHERE id = %d", $survey_id));

    if ($request->get_param('summary')) {
        // Update the summary if provided
        $new_summary = sanitize_text_field($request->get_param('summary'));

        if (empty($new_summary)) {
            return new WP_Error('validation_error', 'Please fill in all required fields.', array('status' => 400));
        }

        $result = $wpdb->update(
            $table_name,
            array('summary' => $new_summary),
            array('id' => $survey_id),
            array('%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to update summary.', array('status' => 500));
        }

        return rest_ensure_response(array('message' => 'Summary added successfully.'));
    }

    return rest_ensure_response(array('summary' => $summary));
}

add_action('rest_api_init', function () {
    register_rest_route('ucm/v1', '/survey-details', array(
        'methods' => 'POST',
        'callback' => 'get_test_question_answer_details',
        'permission_callback' => '__return_true', // Adjust this for proper permissions
    ));
});

function get_test_question_answer_details(WP_REST_Request $request) {
    global $wpdb;

    $survey_id = intval($request->get_param('survey_id'));

    // Fetch questions for the given survey_id
    $questions_query = "
        SELECT 
            q.id AS question_id, 
            q.question
        FROM 
            {$wpdb->prefix}ucm_questions q
        WHERE 
            q.survey_id = %d
            AND q.type = 'question_answers'
    ";

    $questions = $wpdb->get_results($wpdb->prepare($questions_query, $survey_id));

    foreach ($questions as $question) {
        // Fetch answers for each question based on survey_id and question_id
        $answers_query = "
            SELECT 
                a.answer,
                a.is_correct
            FROM 
                {$wpdb->prefix}ucm_question_answers_results a
            WHERE 
                a.survey_id = %d
                AND a.question_id = %d
        ";

        $question->answers = $wpdb->get_results($wpdb->prepare($answers_query, $survey_id, $question->question_id));
    }

    return rest_ensure_response(array('questions' => $questions));
}

add_action('rest_api_init', function () {
    register_rest_route('ucm/v1', '/update-answer-result', array(
        'methods' => 'POST',
        'callback' => 'update_answer_result',
        'permission_callback' => '__return_true',
    ));
});

function update_answer_result(WP_REST_Request $request) {
    global $wpdb;

    $question_id = intval($request->get_param('question_id'));
    $answer = sanitize_text_field($request->get_param('answer'));
    $is_correct = intval($request->get_param('is_correct'));

    $table_name = $wpdb->prefix . 'ucm_question_answers_results';
    $result = $wpdb->update(
        $table_name,
        array('is_correct' => $is_correct),
        array('question_id' => $question_id, 'answer' => $answer),
        array('%d'),
        array('%d', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_update_error', 'Failed to update the answer result', array('status' => 500));
    }

    return rest_ensure_response(array('success' => true));
}

add_action('wp_ajax_ucm_mark_answer', function() {
    global $wpdb;
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    $wpdb->update(
        $wpdb->prefix . 'ucm_question_answers_results',
        array('is_correct' => $status),
        array('id' => $id)
    );
    wp_die();
});


add_action('wp_ajax_ucm_get_student_test_details', function() {
    global $wpdb;
    $test_id = intval($_POST['test_id']);
    $email = sanitize_email($_POST['email']);

    // Username fetch karo
    $user = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}ucm_question_answers_results WHERE test_id = %d AND email = %s LIMIT 1",
            $test_id, $email
        )
    );
    $username = $user ? esc_html($user->name) : 'User';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT q.question, a.answer, a.is_correct, a.id
             FROM {$wpdb->prefix}ucm_question_answers_results a
             LEFT JOIN {$wpdb->prefix}ucm_questions q ON a.question_id = q.id
             WHERE a.test_id = %d AND a.email = %s",
            $test_id, $email
        )
    );

    // Heading with username
    echo '<div class="ucm-modal-heading" style="font-size:18px;font-weight:bold;margin-bottom:18px;">' . $username . ' &rarr; Test Details</div>';

    echo '<div class="ucm-qa-list">';
    foreach ($results as $row) {
        echo '<div class="ucm-qa-item">';
        echo '<div class="ucm-qa-question">Q: ' . esc_html($row->question) . '</div>';
        echo '<div class="ucm-qa-answer">A: ' . nl2br(esc_html($row->answer)) . '</div>';
        echo '<div class="ucm-qa-status-action">';
        echo '<span class="status-cell">';
        if ($row->is_correct == 1) {
            echo '<span style="color:green;font-weight:bold;">Correct</span>';
        } elseif ($row->is_correct == 2) {
            echo '<span style="color:red;font-weight:bold;">Incorrect</span>';
        } else {
            echo '<span style="color:gray;">Unchecked</span>';
        }
        echo '</span>';
        echo '<button class="button mark-correct" data-id="' . esc_attr($row->id) . '">Correct</button>';
        echo '<button class="button mark-incorrect" data-id="' . esc_attr($row->id) . '">Incorrect</button>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
    wp_die();
});


add_action('wp_ajax_ucm_report_testwise', function() {
    global $wpdb;
    $test_id = intval($_POST['test_id']);

    // Total students
    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT email, name FROM {$wpdb->prefix}ucm_question_answers_results WHERE test_id = %d", $test_id
    ));

    // Pass/Fail counts (Assume pass = at least 50% correct)
    $summary = [];
    $pass = $fail = 0;
    foreach ($students as $student) {
        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN is_correct=1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN is_correct=2 THEN 1 ELSE 0 END) as incorrect,
                SUM(CASE WHEN is_correct=0 THEN 1 ELSE 0 END) as unchecked,
                COUNT(*) as total
             FROM {$wpdb->prefix}ucm_question_answers_results
             WHERE test_id = %d AND email = %s",
            $test_id, $student->email
        ));
        $is_pass = ($counts->correct >= ($counts->total/2)) ? true : false;
        if ($is_pass) $pass++; else $fail++;
        $summary[] = [
            'name' => $student->name,
            'email' => $student->email,
            'correct' => $counts->correct,
            'incorrect' => $counts->incorrect,
            'unchecked' => $counts->unchecked,
            'total' => $counts->total,
            'is_pass' => $is_pass
        ];
    }

    echo "<button id='ucm-download-testwise' class='button' style='margin-bottom:10px;'>Download CSV</button>";
    echo "<button class='button ucm-clear-report' style='float:right;margin-bottom:10px;'>Clear</button>";
    echo "<div id='ucm-testwise-table-wrap'>";
    echo "<h3>Test ID: $test_id</h3>";
    echo "<p><b>Total Students:</b> ".count($students)." | <b>Pass:</b> $pass | <b>Fail:</b> $fail</p>";
    echo "<table id='ucm-testwise-table' class='widefat'><thead><tr><th>Name</th><th>Email</th><th>Correct</th><th>Incorrect</th><th>Unchecked</th><th>Total</th><th>Status</th></tr></thead><tbody>";
    foreach ($summary as $row) {
        echo "<tr>";
        echo "<td>".esc_html($row['name'])."</td>";
        echo "<td>".esc_html($row['email'])."</td>";
        echo "<td>".esc_html($row['correct'])."</td>";
        echo "<td>".esc_html($row['incorrect'])."</td>";
        echo "<td>".esc_html($row['unchecked'])."</td>";
        echo "<td>".esc_html($row['total'])."</td>";
        echo "<td>".($row['is_pass'] ? "Pass" : "Fail")."</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    wp_die();
});

add_action('wp_ajax_ucm_report_studentwise', function() {
    global $wpdb;
    $email = sanitize_email($_POST['email']);

    $student_row = $wpdb->get_row($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ucm_question_answers_results WHERE email = %s LIMIT 1", $email));
    $student_name = $student_row ? $student_row->name : 'student';

    // All tests for this student
    $tests = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT test_id FROM {$wpdb->prefix}ucm_question_answers_results WHERE email = %s AND test_id > 0", $email
    ));

    echo "<button id='ucm-download-studentwise' class='button' style='margin-bottom:10px;'>Download CSV</button>";
    echo "<button class='button ucm-clear-report' style='float:right;margin-bottom:10px;'>Clear</button>";
    echo "<div id='ucm-studentwise-table-wrap'>";
    echo "<h3>Student: $student_name ($email)</h3>";
    echo "<table id='ucm-studentwise-table' class='widefat'><thead><tr><th>Test ID</th><th>Question</th><th>Answer</th><th>Status</th></tr></thead><tbody>";

    foreach ($tests as $test) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT q.question, a.answer, a.is_correct
            FROM {$wpdb->prefix}ucm_question_answers_results a
            LEFT JOIN {$wpdb->prefix}ucm_questions q ON a.question_id = q.id
            WHERE a.test_id = %d AND a.email = %s",
            $test->test_id, $email
        ));
        foreach ($results as $row) {
            $status = 'Unchecked';
            if ($row->is_correct == 1) $status = 'Correct';
            elseif ($row->is_correct == 2) $status = 'Incorrect';
            echo "<tr>";
            echo "<td>".esc_html($test->test_id)."</td>";
            echo "<td>".esc_html($row->question)."</td>";
            echo "<td>".esc_html($row->answer)."</td>";
            echo "<td>".$status."</td>";
            echo "</tr>";
        }
    }
    echo "</tbody></table>";
    echo "</div>";
    wp_die();
});

add_action('wp_ajax_ucm_download_test_full_detail_excel', function() {
    require_once __DIR__ . '/../vendor/autoload.php';
    global $wpdb;


    $test_id = intval($_GET['test_id']);
    if (!$test_id) exit('No test selected.');

    $spreadsheet = new Spreadsheet();

    // 1. Summary Sheet
    $summarySheet = $spreadsheet->getActiveSheet();
    $summarySheet->setTitle('Summary');

    $summarySheet->fromArray(['Student Name', 'Email', 'Correct', 'Incorrect', 'Unchecked', 'Total'], NULL, 'A1');

    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT email, name FROM {$wpdb->prefix}ucm_question_answers_results WHERE test_id = %d", $test_id
    ));
    $rowNum = 2;
    foreach ($students as $student) {
        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN is_correct=1 THEN 1 ELSE 0 END) as correct,
                SUM(CASE WHEN is_correct=2 THEN 1 ELSE 0 END) as incorrect,
                SUM(CASE WHEN is_correct=0 THEN 1 ELSE 0 END) as unchecked,
                COUNT(*) as total
             FROM {$wpdb->prefix}ucm_question_answers_results
             WHERE test_id = %d AND email = %s",
            $test_id, $student->email
        ));
        $summarySheet->fromArray([
            $student->name,
            $student->email,
            $counts->correct,
            $counts->incorrect,
            $counts->unchecked,
            $counts->total
        ], NULL, 'A' . $rowNum++);
    }

    // 2. Student Wise Sheets
    foreach ($students as $student) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(substr($student->name, 0, 28)); // Excel sheet name limit
        $sheet->fromArray(['Question', 'Answer', 'Status'], NULL, 'A1');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT q.question, a.answer, a.is_correct
             FROM {$wpdb->prefix}ucm_question_answers_results a
             LEFT JOIN {$wpdb->prefix}ucm_questions q ON a.question_id = q.id
             WHERE a.test_id = %d AND a.email = %s",
            $test_id, $student->email
        ));
        $r = 2;
        foreach ($results as $row) {
            $status = 'Unchecked';
            if ($row->is_correct == 1) $status = 'Correct';
            elseif ($row->is_correct == 2) $status = 'Incorrect';
            $sheet->fromArray([
                $row->question,
                $row->answer,
                $status
            ], NULL, 'A' . $r++);
        }
    }

    // Output Excel file
    $testName = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $test_id));
    $filename = 'test-' . $test_id . '-' . sanitize_title($testName) . '-full-detail.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
});


// Survey question and answer results work

add_action('wp_ajax_ucm_get_survey_summary', function() {
    global $wpdb;
    $survey_id = intval($_POST['survey_id']);
    $summary = $wpdb->get_var($wpdb->prepare("SELECT summary FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));
    echo esc_textarea($summary);
    wp_die();
});

add_action('wp_ajax_ucm_save_survey_summary', function() {
    global $wpdb;
    $survey_id = intval($_POST['survey_id']);
    $summary = sanitize_textarea_field($_POST['summary']);
    $wpdb->update(
        $wpdb->prefix . 'ucm_surveys',
        array('summary' => $summary),
        array('id' => $survey_id)
    );
    echo 'success';
    wp_die();
});

add_action('admin_post_ucm_download_survey_report', function() {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    

    if (!current_user_can('manage_options')) wp_die('Not allowed');
    global $wpdb;

    $survey_id = intval($_POST['survey_id']);
    if (!$survey_id) wp_die('Survey ID missing');

    // Survey info
    $survey = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));
    $survey_summary = $survey->summary; // Get summary from DB
    $total_questions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d", $survey_id));
    $attempted = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}ucm_question_answers_results WHERE survey_id = %d", $survey_id));

    $spreadsheet = new Spreadsheet();

    // Sheet 1: Summary
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Summary');
    $sheet->setCellValue('A1', 'Survey Name');
    $sheet->setCellValue('B1', $survey->name);
    $sheet->setCellValue('A2', 'Survey ID');
    $sheet->setCellValue('B2', $survey->id);
    $sheet->setCellValue('A3', 'Total Questions');
    $sheet->setCellValue('B3', $total_questions);
    $sheet->setCellValue('A4', 'Attempted By');
    $sheet->setCellValue('B4', $attempted);

    // Add Survey Summary (multi-line support)
    $sheet->setCellValue('A6', 'Survey Summary');
    $sheet->getStyle('A6')->getFont()->setBold(true);
    $sheet->setCellValue('B6', $survey_summary);

    // Bold headings in Summary
    $sheet->getStyle('A1:A6')->getFont()->setBold(true);
    $sheet->getStyle('A1:B1')->getFont()->setBold(true);


    // Sheet 2: Answers
    $answersSheet = $spreadsheet->createSheet();
    $answersSheet->setTitle('Answers');

    $questions = $wpdb->get_results($wpdb->prepare("SELECT id, question FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d", $survey_id));
    $row = 1;
    foreach ($questions as $q) {
        // Question as bold heading
        $answersSheet->setCellValue('A' . $row, $q->question);
        $answersSheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        // Table headers
        $answersSheet->setCellValue('A' . $row, 'User');
        $answersSheet->setCellValue('B' . $row, 'Email');
        $answersSheet->setCellValue('C' . $row, 'Answer');
        $answersSheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT name, email, answer FROM {$wpdb->prefix}ucm_question_answers_results WHERE survey_id = %d AND question_id = %d",
            $survey_id, $q->id
        ));
        foreach ($answers as $a) {
            $answersSheet->setCellValue('A' . $row, $a->name);
            $answersSheet->setCellValue('B' . $row, $a->email);
            $answersSheet->setCellValue('C' . $row, $a->answer);
            $row++;
        }
        $row++; // Blank line after each question
    }

    // Auto-size columns for better readability
    foreach (['A', 'B', 'C'] as $col) {
        $answersSheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output
    $survey_name_slug = sanitize_title($survey->name); // Survey name ko file-safe banaen
    $filename = 'survey-report-' . $survey_id . '-' . $survey_name_slug . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
});