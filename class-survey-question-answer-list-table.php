<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Survey_Question_Answer_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => __('Survey Result', 'ucm'),
            'plural'   => __('Survey Results', 'ucm'),
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'survey_id'      => __('Survey ID', 'ucm'),
            'survey_name'    => __('Survey Name', 'ucm'),
            'total_questions'=> __('Total Questions', 'ucm'),
            'attempted'      => __('Attempted By', 'ucm'),
            'actions'        => __('Actions', 'ucm')
        );
        return $columns;
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
        $sortable = array();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = $this->get_results($per_page, $current_page);
    }

    public function get_results($per_page = 5, $page_number = 1) {
        global $wpdb;
        $offset = ($page_number - 1) * $per_page;

        $where = 'WHERE 1=1 AND a.survey_id > 0';
        if (!empty($_GET['filter_survey_id'])) {
            $where .= $wpdb->prepare(' AND a.survey_id = %d', $_GET['filter_survey_id']);
        }

        $query = "
            SELECT 
                a.survey_id,
                s.name as survey_name,
                COUNT(DISTINCT a.question_id) as total_questions,
                COUNT(DISTINCT a.email) as attempted
            FROM {$wpdb->prefix}ucm_question_answers_results a
            LEFT JOIN {$wpdb->prefix}ucm_surveys s ON a.survey_id = s.id
            $where
            GROUP BY a.survey_id
            ORDER BY a.survey_id DESC
            LIMIT %d, %d
        ";

        return $wpdb->get_results($wpdb->prepare($query, $offset, $per_page));
    }

    public function record_count() {
        global $wpdb;

        $where = 'WHERE 1=1 AND survey_id > 0';
        if (!empty($_GET['filter_survey_id'])) {
            $where .= $wpdb->prepare(' AND survey_id = %d', $_GET['filter_survey_id']);
        }

        $query = "SELECT COUNT(*) FROM (SELECT survey_id FROM {$wpdb->prefix}ucm_question_answers_results $where GROUP BY survey_id) as t";
        return $wpdb->get_var($query);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'survey_id':
                return esc_html($item->survey_id);
            case 'survey_name':
                return esc_html($item->survey_name);
            case 'total_questions':
                return esc_html($item->total_questions);
            case 'attempted':
                return esc_html($item->attempted);
            case 'actions':
                return '
                    <a href="' . admin_url('admin.php?page=ucm-survey-user-answers&survey_id=' . esc_attr($item->survey_id)) . '" class="button">View</a>
                    <button type="button" class="button ucm-add-summary" data-survey-id="' . esc_attr($item->survey_id) . '">Add Summary</button>
                ';
            default:
                return '';
        }
    }
}