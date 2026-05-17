<div class="wrap">
    <h1>Surveys & Tests</h1>
    <style>
        .wrap h1 {
            font-size: 1.5rem;
            margin-bottom: 16px;
            color: #23282d;
            letter-spacing: 1px;
        }
        /* Page-specific top-section layout for filters: single-line and responsive */
        .wrap .top-section {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
            width: 100%;
        }
        @media (max-width: 880px) {
            .wrap .top-section {
                grid-template-columns: 1fr;
            }
            .wrap .top-section > div { width: 100%; }
        }
        form[method="get"] {
            display: flex;
            gap: 8px;
            align-items: center;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 18px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            width: 100%;
            box-sizing: border-box;
        }
        form[method="get"] select,
        form[method="get"] input[type="text"] {
            padding: 4px 8px 4px 8px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            font-size: 14px;
            min-width: 140px;
            height: 34px;
            background: #fff;
            appearance: auto; /* Fix for arrow */
        }
        form[method="get"] .button {
            background: #2271b1;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            height: 34px;
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            text-decoration: none;
            margin: 0;
        }
        form[method="get"] .button:hover,
        form[method="get"] .button:focus,
        form[method="get"] .button:active {
            background: #2271b1;
            color: #fff;
        }
        .wp-list-table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .wp-list-table th, .wp-list-table td {
            padding: 10px 12px;
            font-size: 15px;
        }
        .wp-list-table th {
            background: #f1f1f1;
            color: #222;
            font-weight: bold;
        }
        .wp-list-table td.actions.column-actions {
            white-space: nowrap;
            vertical-align: middle;
        }
        .wp-list-table td.actions.column-actions .button {
            padding: 6px 12px;
            min-height: 34px;
            line-height: 1.4;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .wp-list-table td.actions.column-actions .button-primary {
            border-color: #0073aa;
            background: #0073aa;
            color: #fff;
        }
        .wp-list-table tr:nth-child(even) td {
            background: #fafbfc;
        }
        .tablenav.top, .tablenav.bottom {
            background: transparent;
            border: none;
            box-shadow: none;
        }
    </style>
    <?php

    global $wpdb;
    $question_types = $wpdb->get_col("SELECT DISTINCT type FROM {$wpdb->prefix}ucm_questions ORDER BY type ASC");

    if (!class_exists('UCM_Survey_Test_List_Table')) {
        require_once plugin_dir_path(__FILE__) . '../../class-ucm-survey-test-list-table.php';
    }

    $list_table = new UCM_Survey_Test_List_Table();
    $list_table->prepare_items();
    $list_table->process_bulk_action();
    ?>

    <!-- Filter Form Start -->
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <div class="top-section" style="width:100%;padding:10px;margin-bottom:14px;">
            <div class="top-field">
                <label for="filter_name">Name:</label>
                <input type="text" name="filter_name" id="filter_name" value="<?php echo esc_attr($_GET['filter_name'] ?? ''); ?>" />
            </div>

            <div class="top-field">
                <label for="filter_type">Type:</label>
                <select name="filter_type" id="filter_type">
                    <option value="">All Types</option>
                    <option value="survey" <?php selected($_GET['filter_type'] ?? '', 'survey'); ?>>Survey</option>
                    <option value="test" <?php selected($_GET['filter_type'] ?? '', 'test'); ?>>Test</option>
                </select>
            </div>

            <div class="top-field">
                <label for="filter_question_type">Question Type:</label>
                <select name="filter_question_type" id="filter_question_type">
                    <option value="">All Question Types</option>
                    <?php foreach($question_types as $qt): ?>
                        <option value="<?php echo esc_attr($qt); ?>" <?php selected($_GET['filter_question_type'] ?? '', $qt); ?>><?php echo esc_html($qt); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;align-self:end;">
                <button type="submit" class="button">Filter</button>
                <button type="button" class="button" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=' . esc_attr($_REQUEST['page']))); ?>'">Reset</button>
            </div>
        </div>
    </form>
    <!-- Filter Form End -->

    <form method="post">
        <?php
        $list_table->search_box('search', 'search_id');
        $list_table->display();
        ?>
    </form>
</div>