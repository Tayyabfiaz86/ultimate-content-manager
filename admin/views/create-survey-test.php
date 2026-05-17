<div class="wrap">
    <h1>Create New Survey/Test</h1>
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
                <input type="text" name="name" id="name" required />
            </div>

            <div class="top-field">
                <label for="type">Choose Type:</label>
                <select name="type" id="type">
                    <option value="survey">Survey</option>
                    <option value="test">Test</option>
                </select>
            </div>

            <div class="top-field" id="survey-type-fields" style="display:none;">
                <label for="survey-type">Survey Type:</label>
                <select name="survey_type" id="survey-type">
                    <option value="question_answers">Question and Answers</option>
                    <option value="multiple_choices">Multiple Choices</option>
                </select>
            </div>

            <div class="top-field" id="test-type-fields" style="display:none;">
                <label for="test-type">Test Type:</label>
                <select name="test_type" id="test-type">
                    <option value="question_answers">Question and Answers</option>
                    <option value="multiple_choices">Multiple Choices</option>
                </select>
            </div>
        </div>

        <div id="survey-fields" style="display:none;">
            <h3>Survey Questions</h3>
            <div id="ucm-form-error-survey" class="ucm-form-error" style="display:none;"></div>
            <div id="survey-questions-container" class="scrollable-container">
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
            </div>
            <button type="button" id="add-survey-question" class="button">Add Another Question</button>
        </div>
        
        <div id="test-fields" style="display:none;">
            <h3>Test Questions & Answers</h3>
            <div id="ucm-form-error-test" class="ucm-form-error" style="display:none;"></div>
            <div id="test-questions-container" class="scrollable-container">
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
            </div>
            <button type="button" id="add-test-question" class="button">Add Another Question</button>
        </div>

        <input type="submit" value="Create" class="button button-primary">
    </form>
</div>