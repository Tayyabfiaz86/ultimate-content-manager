<div class="wrap">
    <h1>Survey Choices Results</h1>

    <?php
    if (!class_exists('UCM_Survey_Test_List_Table')) {
        require_once plugin_dir_path(__FILE__) . '../../class-survey-choices-list-table.php';
    }

    $list_table = new UCM_Survey_Test_List_Table();
    $list_table->prepare_items();
    $list_table->process_bulk_action();
    ?>

    <form method="post">
        <?php
        $list_table->search_box('search', 'search_id');
        $list_table->display();
        ?>
    </form>
</div>

<!-- Modal for Survey Details -->
<div id="survey-details-modal" style="display:none;">
    <div class="survey-details-content">
        <span class="close">&times;</span>
        <div id="survey-details"></div>
    </div>
</div>

<div id="summary-survey-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Survey Summary</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="summary-survey-form">
                <input type="hidden" id="modal-survey-id" name="survey_id">
                <textarea id="modal-summary" name="summary" rows="4" cols="50" placeholder="Enter your summary here..."></textarea>
                <div class="modal-footer">
                    <button type="submit" class="save-btn">Save Summary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.view-survey-details').on('click', function() {
        var surveyId = $(this).data('survey-id');
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_survey_details',
                survey_id: surveyId
            },
            success: function(response) {
                $('#survey-details').html(response);
                $('#survey-details-modal').show();
            }
        });
    });

    $('.close').on('click', function() {
        $('#survey-details-modal').hide();
    });
});


</script>

<style>
#survey-details-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgb(0,0,0);
    background-color: rgba(0,0,0,0.4);
}

.survey-details-content {
    background-color: #fefefe;
    margin: auto;
    margin-top: 7%;
    padding: 20px;
    border: 1px solid #888;
    width: 70%;
    max-height: 380px;
    max-width: 800px;
    border-radius: 10px;
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    animation: survey_choice_results_fadeIn 0.3s ease-in-out;
    position: relative;
}

.close {
    position: sticky;
    top: 0;
    right: 0;
    z-index: 1001; /* Ensure the close button is on top */
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    margin: 10px;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #000;
    text-decoration: none;
}
</style>

