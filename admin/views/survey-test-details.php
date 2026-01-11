<div class="wrap">
    <h1>Survey/Test Details</h1>
    <style>
        .wrap h1 {
            font-size: 1.5rem;
            margin-bottom: 16px;
            color: #23282d;
            letter-spacing: 1px;
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
            width: fit-content;
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
            padding: 6px 16px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            height: 34px;
        }
        form[method="get"] .button:hover {
            background: #135e96;
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
    <form method="get" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <select name="filter_type">
            <option value="">All Types</option>
            <option value="survey" <?php selected($_GET['filter_type'] ?? '', 'survey'); ?>>Survey</option>
            <option value="test" <?php selected($_GET['filter_type'] ?? '', 'test'); ?>>Test</option>
        </select>
        <select name="filter_question_type">
            <option value="">All Question Types</option>
            <?php foreach($question_types as $qt): ?>
                <option value="<?php echo esc_attr($qt); ?>" <?php selected($_GET['filter_question_type'] ?? '', $qt); ?>>
                    <?php echo esc_html($qt); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button">Filter</button>
    </form>
    <!-- Filter Form End -->

    <form method="post">
        <?php
        $list_table->search_box('search', 'search_id');
        $list_table->display();
        ?>
    </form>
</div>