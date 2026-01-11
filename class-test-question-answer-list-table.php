<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Test_Question_Answer_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => __('Result', 'ucm'),
            'plural'   => __('Results', 'ucm'),
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'test_id'        => __('Test ID', 'ucm'),
            'name'           => __('User Name', 'ucm'),
            'email'          => __('User Email', 'ucm'),
            'correct_count'  => __('Correct', 'ucm'),
            'incorrect_count'=> __('Incorrect', 'ucm'),
            'unchecked_count'=> __('Unchecked', 'ucm'),
            'total_questions'=> __('Total Questions', 'ucm'),
            'attempts'       => __('Attempts', 'ucm'),
            'actions'        => __('Actions', 'ucm')
        );
        return $columns;
    }

    public function get_sortable_columns() {
        return array(
            'submitted_at' => array('submitted_at', true),
            'test_id'      => array('test_id', false)
        );
    }

    public function single_row($item) {
        echo '<tr data-test-id="' . esc_attr($item->test_id) . '" data-email="' . esc_attr($item->email) . '">';
        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = "class='column-$column_name'";
            $style = '';
            if (in_array($column_name, $hidden)) {
                $style = ' style="display:none;"';
            }
            $attributes = "$class$style";

            if (method_exists($this, 'column_' . $column_name)) {
                echo "<td $attributes>" . call_user_func(array($this, 'column_' . $column_name), $item) . "</td>";
            } else {
                echo "<td $attributes>" . $this->column_default($item, $column_name) . "</td>";
            }
        }
        echo '</tr>';
    }

    public function prepare_items() {
        $per_page     = 5;
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $this->get_results($per_page, $current_page);
    }

    public function get_results($per_page = 5, $page_number = 1) {
        global $wpdb;
        $offset = ($page_number - 1) * $per_page;

        $where = 'WHERE 1=1 AND a.test_id > 0'; // <-- Add this condition
        if (!empty($_GET['filter_test_id'])) {
            $where .= $wpdb->prepare(' AND a.test_id = %d', $_GET['filter_test_id']);
        }
        if (!empty($_GET['filter_name'])) {
            $where .= $wpdb->prepare(' AND a.name LIKE %s', '%' . $wpdb->esc_like($_GET['filter_name']) . '%');
        }
        if (!empty($_GET['filter_email'])) {
            $where .= $wpdb->prepare(' AND a.email LIKE %s', '%' . $wpdb->esc_like($_GET['filter_email']) . '%');
        }

        $query = "
            SELECT 
                a.test_id,
                a.name,
                a.email,
                COUNT(*) as total_questions,
                SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                SUM(CASE WHEN a.is_correct = 2 THEN 1 ELSE 0 END) as incorrect_count,
                SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) as unchecked_count,
                COUNT(DISTINCT a.question_id) as attempts
            FROM {$wpdb->prefix}ucm_question_answers_results a
            $where
            GROUP BY a.test_id, a.email
            ORDER BY a.submitted_at DESC
            LIMIT %d, %d
        ";

        return $wpdb->get_results($wpdb->prepare($query, $offset, $per_page));
    }

    public function record_count() {
        global $wpdb;

        $where = 'WHERE 1=1 AND test_id > 0';
        if (!empty($_GET['filter_test_id'])) {
            $where .= $wpdb->prepare(' AND test_id = %d', $_GET['filter_test_id']);
        }
        if (!empty($_GET['filter_name'])) {
            $where .= $wpdb->prepare(' AND name LIKE %s', '%' . $wpdb->esc_like($_GET['filter_name']) . '%');
        }
        if (!empty($_GET['filter_email'])) {
            $where .= $wpdb->prepare(' AND email LIKE %s', '%' . $wpdb->esc_like($_GET['filter_email']) . '%');
        }

        // Count unique (test_id, email) pairs
        $query = "SELECT COUNT(*) FROM (SELECT test_id, email FROM {$wpdb->prefix}ucm_question_answers_results $where GROUP BY test_id, email) as t";
        return $wpdb->get_var($query);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'test_id':
                return esc_html($item->test_id);
            case 'name':
                return esc_html($item->name);
            case 'email':
                return esc_html($item->email);
            case 'correct_count':
                return esc_html($item->correct_count);
            case 'incorrect_count':
                return esc_html($item->incorrect_count);
            case 'unchecked_count':
                return esc_html($item->unchecked_count);
            case 'total_questions':
                return esc_html($item->total_questions);
            case 'attempts':
                return esc_html($item->attempts);
            case 'actions':
                return '<button type="button" class="button ucm-view-details" data-test-id="' . esc_attr($item->test_id) . '" data-email="' . esc_attr($item->email) . '">View</button>';
            default:
                return '';
        }
    }

    public function column_answer($item) {
        return '<button type="button" class="button ucm-show-answer" data-answer="' . esc_attr($item->answer) . '" data-id="' . esc_attr($item->id) . '">View Answer</button>';
    }
}
