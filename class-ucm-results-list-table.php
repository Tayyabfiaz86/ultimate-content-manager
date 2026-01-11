<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UCM_Results_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'result',
            'plural'   => 'results',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'name'          => 'Name',
            'email'         => 'Email',
            'correct_count' => 'Correct Answers',
            'wrong_count'   => 'Wrong Answers',
            'score'         => 'Score',
            'view'          => 'View'
        );
    
        return $columns;
    }
    
    protected function column_view($item) {
        return sprintf('<button class="button view-button" data-result-id="%d">View</button>', $item->id);
    }
    

    protected function get_sortable_columns() {
        $sortable_columns = array(
            'name'          => array('name', false),
            'email'         => array('email', false),
            'correct_count' => array('correct_count', false),
            'wrong_count'   => array('wrong_count', false),
            'score'         => array('score', false)
        );

        return $sortable_columns;
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
            case 'email':
            case 'correct_count':
            case 'wrong_count':
                return esc_html($item->$column_name);
            case 'score':
                return esc_html($item->$column_name) . '%';
            default:
                return print_r($item, true);
        }
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="result[]" value="%s" />',
            $item->id
        );
    }

    protected function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );

        return $actions;
    }

    public function process_bulk_action() {

        if ('delete' === $this->current_action()) {
            // Verify nonce
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
                return;
            }
    
            // Get the IDs of the items to delete
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
    
            if (is_array($ids)) {
                $ids = array_map('intval', $ids);
            } else {
                $ids = intval($ids);
            }
    
            // Delete the items
            if (!empty($ids)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'ucm_results';
                $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $ids));
            }
        }
    }

    public function prepare_items() {
        global $wpdb;
    
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $test_id_filter = isset($_REQUEST['test_id_filter']) ? intval($_REQUEST['test_id_filter']) : 0;
        $include_passed = isset($_REQUEST['include_passed']) ? intval($_REQUEST['include_passed']) : 0;
        $include_failed = isset($_REQUEST['include_failed']) ? intval($_REQUEST['include_failed']) : 0;
    
        // Base query to count total items
        $query = "SELECT COUNT(id) FROM {$wpdb->prefix}ucm_results WHERE test_id > 0";
        if ($test_id_filter > 0) {
            $query .= $wpdb->prepare(" AND test_id = %d", $test_id_filter);
        }
        if ($include_passed && !$include_failed) {
            $query .= " AND passed = 1";
        } elseif (!$include_passed && $include_failed) {
            $query .= " AND passed = 0";
        }
        $total_items = $wpdb->get_var($query);
    
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));
    
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
    
        $this->process_bulk_action();
    
        // Base query to fetch results
        $query = "SELECT * FROM {$wpdb->prefix}ucm_results WHERE test_id > 0";
        if ($test_id_filter > 0) {
            $query .= $wpdb->prepare(" AND test_id = %d", $test_id_filter);
        }
        if ($include_passed && !$include_failed) {
            $query .= " AND passed = 1";
        } elseif (!$include_passed && $include_failed) {
            $query .= " AND passed = 0";
        }
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, ($current_page - 1) * $per_page);
    
        $this->items = $wpdb->get_results($query);
    }
    
    // Add a function to render the dropdown filter
    public function render_test_filter() {
        global $wpdb;
        $test_id_filter = isset($_REQUEST['test_id_filter']) ? intval($_REQUEST['test_id_filter']) : 0;
        $include_passed = isset($_REQUEST['include_passed']) ? intval($_REQUEST['include_passed']) : 0;
        $include_failed = isset($_REQUEST['include_failed']) ? intval($_REQUEST['include_failed']) : 0;
    
        // Fetch all tests
        $tests = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}ucm_surveys WHERE type= 'test'");
    
        echo '<div class="filter-container">';
        echo '<div class="filter-item">';
        echo '<label for="test_id_filter">' . __('Select Test:', 'textdomain') . '</label>';
        echo '<select name="test_id_filter" id="test_id_filter">';
        echo '<option value="0">' . __('All Tests', 'textdomain') . '</option>';
        foreach ($tests as $test) {
            $selected = ($test_id_filter == $test->id) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($test->id) . '" ' . $selected . '>' . esc_html($test->name) . '</option>';
        }
        echo '</select>';
        echo '</div>';
    
        // Add checkboxes for passed and failed filters
        echo '<div class="filter-item">';
        echo '<label><input type="checkbox" name="include_passed" value="1" ' . checked(1, $include_passed, false) . '> ' . __('Include Passed Students', 'textdomain') . '</label>';
        echo '</div>';
        echo '<div class="filter-item">';
        echo '<label><input type="checkbox" name="include_failed" value="1" ' . checked(1, $include_failed, false) . '> ' . __('Include Failed Students', 'textdomain') . '</label>';
        echo '</div>';
    
        echo '<div class="filter-item">';
        // submit_button(__('Filter'), 'primary', 'filter_action', false);
        echo '</div>';
        echo '</div>';
    }
    

    public function render_test_details($test_id_filter) {
    global $wpdb;

    if ($test_id_filter > 0) {
        // Fetch test details
        $test = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ucm_surveys WHERE id = %d", $test_id_filter));

        if ($test) {
            // Fetch student details with correct column names
            $students = $wpdb->get_results($wpdb->prepare("SELECT name, email, score, correct_count, wrong_count, passed FROM {$wpdb->prefix}ucm_results WHERE test_id = %d", $test_id_filter));

            $total_students = count($students);
            $passed_students = count(array_filter($students, function($student) { return $student->passed == 1; }));
            $failed_students = $total_students - $passed_students;

            echo '<div class="ucm-test-details">';
            echo '<h3>' . esc_html($test->name) . ' Details</h3>';
            echo '<p>Total Students Enrolled: ' . esc_html($total_students) . '</p>';
            echo '<p>Passed: ' . esc_html($passed_students) . '</p>';
            echo '<p>Failed: ' . esc_html($failed_students) . '</p>';

            // Buttons to print passed and failed students
            echo '<div class="ucm-print-buttons">';
            echo '<button class="ucm-print-button" onclick="ucmPrintStudents(\'passed\')">Print Passed Students</button>';
            echo '<button class="ucm-print-button" onclick="ucmPrintStudents(\'failed\')">Print Failed Students</button>';
            echo '</div>';

            // Create lists for passed and failed students
            $passed_list = array_filter($students, function($student) { return $student->passed == 1; });
            $failed_list = array_filter($students, function($student) { return $student->passed == 0; });

            // Hidden containers for passed and failed students
            echo '<div id="ucm-passed-students" style="display:none;">';
            echo '<h4>Passed Students - ' . esc_html($test->name) . '</h4>';
            echo '<table class="ucm-print-table">';
            echo '<tr><th>Test Name</th><th>Name</th><th>Email</th><th>Correct Answers</th><th>Wrong Answers</th><th>Score</th></tr>';
            foreach ($passed_list as $student) {
                echo '<tr>';
                echo '<td>' . esc_html($test->name) . '</td>';
                echo '<td>' . esc_html($student->name) . '</td>';
                echo '<td>' . esc_html($student->email) . '</td>';
                echo '<td>' . esc_html($student->correct_count) . '</td>';
                echo '<td>' . esc_html($student->wrong_count) . '</td>';
                echo '<td>' . esc_html($student->score) .'%'. '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

            echo '<div id="ucm-failed-students" style="display:none;">';
            echo '<h4>Failed Students - ' . esc_html($test->name) . '</h4>';
            echo '<table class="ucm-print-table">';
            echo '<tr><th>Name</th><th>Email</th><th>Correct Answers</th><th>Wrong Answers</th><th>Score</th></tr>';
            foreach ($failed_list as $student) {
                echo '<tr>';
                echo '<td>' . esc_html($student->name) . '</td>';
                echo '<td>' . esc_html($student->email) . '</td>';
                echo '<td>' . esc_html($student->correct_count) . '</td>';
                echo '<td>' . esc_html($student->wrong_count) . '</td>';
                echo '<td>' . esc_html($student->score) .'%'. '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            echo '</div>';

            }
        }
    }

    // Call the render_test_filter and render_test_details functions in the appropriate place
    public function display() {
        echo '<form method="get">';
        $this->render_test_filter();
    
        // // Manually add the search box here
        // echo '<div class="ucm-filter-item">';
        // echo '<label for="search_id">' . __('Search:', 'textdomain') . '</label>';
        // echo '<input type="search" id="search_id" name="s" value="' . esc_attr(isset($_REQUEST['s']) ? $_REQUEST['s'] : '') . '" />';
        // echo '</div>';
    
        submit_button(__('Filter'), 'primary', 'filter_action', false);
        echo '</form>';
    
        $test_id_filter = isset($_REQUEST['test_id_filter']) ? intval($_REQUEST['test_id_filter']) : 0;
        $this->render_test_details($test_id_filter);
    
        // Add modal structure
        echo '<div id="ucm-modal" class="ucm-modal">';
        echo '<div class="ucm-modal-content">';
        echo '<span class="ucm-close">&times;</span>';
        echo '<div id="ucm-modal-body"></div>';
        echo '</div>';
        echo '</div>';

        
    
        parent::display();
    }

    
    

    protected function usort_reorder($a, $b) {
        $orderby = !empty($_REQUEST['orderby']) ? wp_unslash($_REQUEST['orderby']) : 'id';
        $order = !empty($_REQUEST['order']) ? wp_unslash($_REQUEST['order']) : 'asc';

        $result = strcmp($a->$orderby, $b->$orderby);

        return ('asc' === $order) ? $result : -$result;
    }

    protected function column_name($item) {
        $actions = array(
            'view' => sprintf('<a href="?page=%s&action=%s&result=%s">View</a>', $_REQUEST['page'], 'view', $item->id)
        );

        return sprintf('%1$s %2$s', $item->name, $this->row_actions($actions));
    }
}

echo '<script>
function ucmPrintStudents(type) {
    var listId = type === "passed" ? "ucm-passed-students" : "ucm-failed-students";
    var printContents = document.getElementById(listId).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>';