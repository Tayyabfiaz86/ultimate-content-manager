<div class="wrap">
    <?php
    // Edit mode handler
    global $wpdb;
    $edit_data = null;
    $edit_questions = array();
    $heading = 'Create New Survey/Test';
    $button_text = 'Create';
    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $edit_type = isset($_GET['edit_type']) ? sanitize_text_field($_GET['edit_type']) : '';

    if ($edit_id && in_array($edit_type, array('survey', 'test'), true)) {
        $edit_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d AND type = %s",
            $edit_id, $edit_type
        ));
        if ($edit_data) {
            $edit_questions = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d",
                $edit_id
            ));
            $heading = 'Edit ' . ucfirst($edit_type);
            $button_text = 'Update';
        }
    }
    ?>
    <h1><?php echo esc_html($heading); ?></h1>
    <?php if (isset($_GET['message']) && $_GET['message'] == 'success' && isset($_GET['type'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo ucfirst($_GET['type']); ?> created successfully!</p>
        </div>
    <?php endif; ?>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="custom-survey-form">
        <input type="hidden" name="action" value="ucm_save_survey">
        <?php wp_nonce_field('ucm_save_survey', 'ucm_nonce'); ?>

        <?php if (isset($_GET['message']) && $_GET['message'] === 'error' && isset($_GET['error_reason'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(urldecode($_GET['error_reason'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="top-section">
            <div class="top-field">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" value="<?php echo $edit_data ? esc_attr($edit_data->name) : ''; ?>" required />
            </div>

            <div class="top-field">
                <label for="type">Choose Type:</label>
                <?php if ($edit_data): ?>
                    <div style="padding:9px 12px; background:#f1f1f1; border:1px solid #ccd0d4; border-radius:4px; color:#333;"><?php echo ucfirst(esc_html($edit_data->type)); ?></div>
                    <input type="hidden" name="type" value="<?php echo esc_attr($edit_data->type); ?>" />
                    <input type="hidden" name="edit_id" value="<?php echo intval($edit_data->id); ?>" />
                    <select id="type" style="display:none;">
                        <option value="<?php echo esc_attr($edit_data->type); ?>" selected><?php echo ucfirst(esc_html($edit_data->type)); ?></option>
                    </select>
                <?php else: ?>
                    <select name="type" id="type">
                        <option value="survey">Survey</option>
                        <option value="test">Test</option>
                    </select>
                <?php endif; ?>
            </div>

            <div class="top-field" id="survey-type-fields" style="display:none;">
                <label for="survey-type">Survey Type:</label>
                <select name="survey_type" id="survey-type">
                    <option value="question_answers" <?php echo $edit_data && $edit_data->type === 'survey' && isset($edit_questions[0]) && $edit_questions[0]->type === 'question_answers' ? 'selected' : ''; ?>>Question and Answers</option>
                    <option value="multiple_choices" <?php echo $edit_data && $edit_data->type === 'survey' && isset($edit_questions[0]) && $edit_questions[0]->type === 'multiple_choices' ? 'selected' : ''; ?>>Multiple Choices</option>
                </select>
            </div>

            <div class="top-field" id="test-type-fields" style="display:none;">
                <label for="test-type">Test Type:</label>
                <select name="test_type" id="test-type">
                    <option value="question_answers" <?php echo $edit_data && $edit_data->type === 'test' && isset($edit_questions[0]) && $edit_questions[0]->type === 'question_answers' ? 'selected' : ''; ?>>Question and Answers</option>
                    <option value="multiple_choices" <?php echo $edit_data && $edit_data->type === 'test' && isset($edit_questions[0]) && $edit_questions[0]->type === 'multiple_choices' ? 'selected' : ''; ?>>Multiple Choices</option>
                </select>
            </div>
        </div>

        <div id="survey-fields" style="display:none;">
            <h3>Survey Questions</h3>
            <div id="ucm-form-error-survey" class="ucm-form-error" style="display:none;"></div>
            <div id="survey-questions-container" class="scrollable-container">
                <?php 
                if ($edit_data && $edit_data->type === 'survey' && !empty($edit_questions)) {
                    global $wpdb;
                    foreach ($edit_questions as $q_idx => $q): 
                        $choices = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}ucm_choices WHERE question_id = %d",
                            $q->id
                        ));
                        ?>
                        <div class="survey-question">
                            <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
                            <div>
                                <label>Question:</label>
                                <input type="text" name="survey_questions[]" value="<?php echo esc_attr($q->question); ?>" />
                            </div>
                            <div class="survey-choices-container" style="display:<?php echo $q->type === 'multiple_choices' ? 'block' : 'none'; ?>;">
                                <?php for ($i = 1; $i <= 4; $i++): 
                                    $choice_value = isset($choices[$i-1]) ? $choices[$i-1]->choice : '';
                                    ?>
                                    <div class="choice-container">
                                        <label>Choice <?php echo $i; ?>:</label>
                                        <input type="text" name="survey_choices[]" value="<?php echo esc_attr($choice_value); ?>" />
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach;
                } else {
                ?>
                <div class="survey-question">
                    <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
                    <div>
                        <label>Question:</label>
                        <input type="text" name="survey_questions[]" />
                    </div>
                    <div class="survey-choices-container" style="display:none;">
                        <div class="choice-container">
                            <label>Choice 1:</label>
                            <input type="text" name="survey_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 2:</label>
                            <input type="text" name="survey_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 3:</label>
                            <input type="text" name="survey_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 4:</label>
                            <input type="text" name="survey_choices[]" />
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <button type="button" id="add-survey-question" class="button">Add Another Question</button>
        </div>
        
        <div id="test-fields" style="display:none;">
            <h3>Test Questions & Answers</h3>
            <div id="ucm-form-error-test" class="ucm-form-error" style="display:none;"></div>
            <div id="test-questions-container" class="scrollable-container">
                <?php 
                if ($edit_data && $edit_data->type === 'test' && !empty($edit_questions)) {
                    global $wpdb;
                    foreach ($edit_questions as $q_idx => $q): 
                        $choices = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}ucm_choices WHERE question_id = %d",
                            $q->id
                        ));
                        ?>
                        <div class="test-question">
                            <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
                            <div>
                                <label>Question:</label>
                                <input type="text" name="test_questions[]" value="<?php echo esc_attr($q->question); ?>" />
                            </div>
                            <div class="test-choices-container" style="display:<?php echo $q->type === 'multiple_choices' ? 'block' : 'none'; ?>;">
                                <?php for ($i = 1; $i <= 4; $i++): 
                                    $choice = isset($choices[$i-1]) ? $choices[$i-1] : null;
                                    $choice_value = $choice ? $choice->choice : '';
                                    ?>
                                    <div class="choice-container">
                                        <label>Choice <?php echo $i; ?>:</label>
                                        <input type="text" name="test_choices[]" value="<?php echo esc_attr($choice_value); ?>" />
                                    </div>
                                <?php endfor; ?>
                                <div class="choice-container">
                                    <label>Correct Answer:</label>
                                    <select name="test_correct[]">
                                        <option value="" selected disabled>Choose Correct Answer</option>
                                        <?php foreach ($choices as $c_idx => $c): ?>
                                            <option value="<?php echo $c_idx + 1; ?>" <?php echo $c->is_correct ? 'selected' : ''; ?>>Choice <?php echo $c_idx + 1; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                } else {
                ?>
                <div class="test-question">
                    <button type="button" class="ucm-remove-question" aria-label="Remove question">×</button>
                    <div>
                        <label>Question:</label>
                        <input type="text" name="test_questions[]" />
                    </div>
                    <div class="test-choices-container" style="display:none;">
                        <div class="choice-container">
                            <label>Choice 1:</label>
                            <input type="text" name="test_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 2:</label>
                            <input type="text" name="test_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 3:</label>
                            <input type="text" name="test_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Choice 4:</label>
                            <input type="text" name="test_choices[]" />
                        </div>
                        <div class="choice-container">
                            <label>Correct Answer:</label>
                            <select name="test_correct[]">
                                <option value="" selected disabled>Choose Correct Answer</option>
                                <option value="1">Choice 1</option>
                                <option value="2">Choice 2</option>
                                <option value="3">Choice 3</option>
                                <option value="4">Choice 4</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <button type="button" id="add-test-question" class="button">Add Another Question</button>
        </div>

        <input type="submit" value="<?php echo esc_attr($button_text); ?>" class="button button-primary">
    </form>
</div>