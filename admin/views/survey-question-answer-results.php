<div class="wrap">
    <h1>Survey Question&Answer Results</h1>
    <?php
    if (!class_exists('Survey_Question_Answer_List_Table')) {
        require_once plugin_dir_path(__FILE__) . '../../class-survey-question-answer-list-table.php';
    }
    $list_table = new Survey_Question_Answer_List_Table();
    $list_table->prepare_items();

    // Only show surveys which have results
    global $wpdb;
    $survey_ids = $wpdb->get_col("SELECT DISTINCT survey_id FROM {$wpdb->prefix}ucm_question_answers_results WHERE survey_id > 0");
    if ($survey_ids) {
        $in = implode(',', array_map('intval', $survey_ids));
        $surveys = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ucm_surveys WHERE id IN ($in) ORDER BY name ASC");
    } else {
        $surveys = [];
    }
    ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;">
        <input type="hidden" name="action" value="ucm_download_survey_report">
        <select name="survey_id" required style="min-width:220px;">
            <option value="">Select Survey</option>
            <?php foreach($surveys as $survey): ?>
                <option value="<?php echo esc_attr($survey->id); ?>"><?php echo esc_html($survey->name); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button button-primary">Download Report</button>
    </form>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <input type="text" name="filter_survey_id" placeholder="Survey ID" value="<?php echo isset($_GET['filter_survey_id']) ? esc_attr($_GET['filter_survey_id']) : ''; ?>">
        <input type="text" name="filter_name" placeholder="User Name" value="<?php echo isset($_GET['filter_name']) ? esc_attr($_GET['filter_name']) : ''; ?>">
        <input type="text" name="filter_email" placeholder="User Email" value="<?php echo isset($_GET['filter_email']) ? esc_attr($_GET['filter_email']) : ''; ?>">
        <input type="submit" class="button" value="Filter">
    </form>
    <?php $list_table->display(); ?>
</div>

<div id="ucm-summary-modal" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; max-width:400px; margin:10% auto; padding:20px; position:relative;">
        <span id="ucm-close-summary-modal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
        <h3>Add/Edit Survey Summary</h3>
        <textarea id="ucm-summary-text" style="width:100%;height:100px;"></textarea>
        <input type="hidden" id="ucm-summary-survey-id" value="">
        <button id="ucm-save-summary" class="button button-primary" style="margin-top:10px;">Save</button>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Open modal
    document.querySelectorAll('.ucm-add-summary').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var surveyId = this.getAttribute('data-survey-id');
            document.getElementById('ucm-summary-survey-id').value = surveyId;
            // Fetch existing summary
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    document.getElementById('ucm-summary-text').value = xhr.responseText;
                }
            };
            xhr.send('action=ucm_get_survey_summary&survey_id=' + encodeURIComponent(surveyId));
            document.getElementById('ucm-summary-modal').style.display = 'block';
        });
    });

    // Close modal
    document.getElementById('ucm-close-summary-modal').onclick = function() {
        document.getElementById('ucm-summary-modal').style.display = 'none';
    };

    // Save summary
    document.getElementById('ucm-save-summary').onclick = function() {
        var surveyId = document.getElementById('ucm-summary-survey-id').value;
        var summary = document.getElementById('ucm-summary-text').value;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl, true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Summary saved!');
                document.getElementById('ucm-summary-modal').style.display = 'none';
            }
        };
        xhr.send('action=ucm_save_survey_summary&survey_id=' + encodeURIComponent(surveyId) + '&summary=' + encodeURIComponent(summary));
    };
});
</script>