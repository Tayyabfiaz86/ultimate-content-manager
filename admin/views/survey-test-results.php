<div class="wrap">
    <h1>Choices Test Results</h1>

    <?php
    if (!class_exists('UCM_Results_List_Table')) {
        require_once plugin_dir_path(__FILE__) . '../../class-ucm-results-list-table.php';
    }

    $list_table = new UCM_Results_List_Table();
    $list_table->prepare_items();
    ?>

    <form method="post">
        <?php
        // $list_table->search_box('search', 'search_id');
        $list_table->display();
        ?>
    </form>
</div>