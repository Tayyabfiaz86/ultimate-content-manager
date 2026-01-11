<?php
global $wpdb;
$survey_id = intval($_GET['survey_id']);
if (!$survey_id) {
    echo '<div class="notice notice-error"><p>Survey ID not found.</p></div>';
    return;
}

// Survey name
$survey_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $survey_id));
?>
<div class="wrap">
    <h1>Survey Answers: <?php echo esc_html($survey_name); ?> (ID: <?php echo $survey_id; ?>)</h1>
    <style>
        body.wp-admin {
            background: #f6f8fa !important;
        }
        .ucm-question-block {
            margin-bottom: 38px;
            padding: 22px 28px 16px 28px;
            background: #fafdff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,80,180,0.06);
            border: 1px solid #e3eaf3;
        }
        .ucm-question-title {
            font-weight: 700;
            font-size: 1.18em;
            margin-bottom: 18px;
            color: #2563eb;
            letter-spacing: 0.2px;
            background: #fafdff;
            position: sticky;
            top: 70px; /* 70px: thoda neeche, admin bar se gap */
            z-index: 10;
            padding-top: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e3eaf3;
            box-shadow: 0 2px 8px rgba(37,99,235,0.04);
        }
        .ucm-answer-row {
            margin-bottom: 12px;
            padding: 10px 0 8px 0;
            border-bottom: 1px solid #e3eaf3;
            transition: background 0.2s;
            border-left: 3px solid #a5d8ff;
            padding-left: 14px;
            background: #f5faff;
            border-radius: 4px;
        }
        .ucm-answer-row:last-child {
            border-bottom: none;
        }
        .ucm-answer-row:hover {
            background: #e8f1ff;
        }
        .ucm-answer-meta {
            color: #5c6b7a;
            font-size: 13px;
            margin-right: 10px;
            display: inline-block;
        }
        .ucm-answer-user {
            font-weight: 500;
            color: #2563eb;
        }
        .ucm-answer-email {
            font-style: italic;
            color: #8ca0b3;
        }
        .ucm-answer-text {
            color: #222;
            display: block;
            margin-top: 2px;
            font-size: 15px;
            line-height: 1.6;
        }
        @media (max-width: 600px) {
            .ucm-question-block { padding: 12px 6px 8px 6px; }
            .ucm-question-title { font-size: 1em; top: 56px; }
            .ucm-answer-row { font-size: 14px; padding-left: 6px; }
        }
    </style>
<?php
$questions = $wpdb->get_results($wpdb->prepare(
    "SELECT id, question FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d", $survey_id
));

if (!$questions) {
    echo '<p>No questions found for this survey.</p></div>';
    return;
}

foreach ($questions as $q) {
    echo '<div class="ucm-question-block">';
    echo '<div class="ucm-question-title">' . esc_html($q->question) . '</div>';
    $answers = $wpdb->get_results($wpdb->prepare(
        "SELECT a.answer, a.name, a.email
         FROM {$wpdb->prefix}ucm_question_answers_results a
         WHERE a.survey_id = %d AND a.question_id = %d",
        $survey_id, $q->id
    ));
    if ($answers) {
        foreach ($answers as $row) {
            echo '<div class="ucm-answer-row">';
            echo '<span class="ucm-answer-meta ucm-answer-user">' . esc_html($row->name) . '</span>';
            echo '<span class="ucm-answer-meta ucm-answer-email">&lt;' . esc_html($row->email) . '&gt;</span>';
            echo '<span class="ucm-answer-text">' . nl2br(esc_html($row->answer)) . '</span>';
            echo '</div>';
        }
    } else {
        echo '<div class="ucm-answer-row" style="color:#aaa;">No answers submitted for this question.</div>';
    }
    echo '</div>';
}
echo '</div>';