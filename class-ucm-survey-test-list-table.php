<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UCM_Survey_Test_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'survey_test',
            'plural'   => 'survey_tests',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'cb'        => '<input type="checkbox" />',
            'name'      => 'Name',
            'type'      => 'Type',
            'question_type'      => 'Question Type',
            'shortcode' => 'Shortcode',
            'actions'   => 'Actions'
        );
    }

    protected function get_sortable_columns() {
        return array(
            'name' => array('name', false),
            'type' => array('type', false)
        );
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'name':
                return esc_html($item->name);
            case 'type':
                return esc_html($item->type);
            case 'question_type':
                    // Fetch the question type from the database
                    global $wpdb;
                    $question_type = $wpdb->get_var($wpdb->prepare(
                        "SELECT type FROM {$wpdb->prefix}ucm_questions WHERE survey_id = %d LIMIT 1",
                        $item->id
                    ));
                    return $question_type ? $question_type : 'N/A';
            case 'shortcode':
                return '[ucm_' . esc_html($item->type) . ' id="' . esc_html($item->id) . '"]';
            case 'actions':
                $preview_url = esc_url(add_query_arg(array('ucm_preview' => 1, 'type' => $item->type, 'id' => $item->id), home_url('/')));
                $edit_url = esc_url(admin_url('admin.php?page=ucm&edit_id=' . intval($item->id) . '&edit_type=' . esc_attr($item->type)));
                return '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
                    . '<a class="button" target="_blank" href="' . $preview_url . '">View</a>'
                    . '<a class="button button-primary" href="' . $edit_url . '">Edit</a>'
                    . '</div>';
            default:
                return print_r($item, true);
        }
    }

    protected function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="survey_test[]" value="%s" />',
            $item->id
        );
    }

    public function prepare_items() {
        global $wpdb;

        $per_page = 10;
        $current_page = $this->get_pagenum();

        $where = [];
        $params = [];

        // Filter by survey/test type
        if (!empty($_GET['filter_type'])) {
            $where[] = "type = %s";
            $params[] = $_GET['filter_type'];
        }

        // Filter by name (partial match)
        if (!empty($_GET['filter_name'])) {
            $where[] = "name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($_GET['filter_name']) . '%';
        }

        // Filter by question type (from questions table)
        if (!empty($_GET['filter_question_type'])) {
            $where[] = "id IN (SELECT survey_id FROM {$wpdb->prefix}ucm_questions WHERE type = %s)";
            $params[] = $_GET['filter_question_type'];
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$wpdb->prefix}ucm_surveys $where_sql",
            ...$params
        ));

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $items_sql = "SELECT * FROM {$wpdb->prefix}ucm_surveys $where_sql LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results($wpdb->prepare($items_sql, ...$params));
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => 'Delete'
        );
    }

    protected function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            global $wpdb;
            $ids = isset($_REQUEST['survey_test']) ? $_REQUEST['survey_test'] : array();
            if (is_array($ids)) {
                $ids = implode(',', array_map('intval', $ids));
            }
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}ucm_surveys WHERE id IN($ids)");
            }
        }
    }
}