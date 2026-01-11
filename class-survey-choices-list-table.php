<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UCM_Survey_Test_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'survey',
            'plural'   => 'surveys',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'survey_id'      => 'Survey ID',
            'survey_title'   => 'Survey Title',
            'responses'      => 'Number of Responses',
            'actions'       => __('Actions', 'ucm') // Add a new column for actions
        );
        return $columns;
    }

    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="survey[]" value="%s" />', $item['survey_id']);
    }

    protected function column_actions($item) {
        $view_button = sprintf(
            '<button type="button" class="button view-survey-details" data-survey-id="%s">View</button>',
            esc_attr($item['survey_id'])
        );
    
        $add_summary_button = sprintf(
            '<button type="button" class="button add-summary" data-survey-id="%s">Add Summary</button>',
            esc_attr($item['survey_id'])
        );
    
        return $view_button . ' ' . $add_summary_button;
    }

    protected function column_view($item) {
        $view_url = add_query_arg(array(
            'page'      => 'ucm-survey-details',
            'survey_id' => $item['survey_id']
        ), admin_url('admin.php'));

        return sprintf('<button type="button" class="button view-survey-details" data-survey-id="%s">View</button>', esc_attr($item['survey_id']));
    }

    protected function column_add_summary($item) {
        return sprintf('<button type="button" class="button add-summary" data-survey-id="%s">Add Summary</button>', esc_attr($item['survey_id']));
    }

    public function prepare_items() {
        $per_page = $this->get_items_per_page('surveys_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = $this->record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

        $this->items = $this->get_surveys($per_page, $current_page);
    }

    public function get_surveys($per_page = 10, $page_number = 1) {
        global $wpdb;

        $offset = ($page_number - 1) * $per_page;

        $sql = "SELECT id as survey_id, name as survey_title, total_submit_count as responses 
                FROM {$wpdb->prefix}ucm_surveys 
                WHERE type = 'survey'
                LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset), ARRAY_A);

        return $results;
    }

    public function record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ucm_surveys WHERE type = 'survey'";

        return $wpdb->get_var($sql);
    }

    protected function column_default($item, $column_name) {
        switch ($column_name) {
            case 'survey_id':
            case 'survey_title':
            case 'responses':
                return $item[$column_name];
            default:
                return print_r($item, true);
        }
    }

    protected function get_sortable_columns() {
        return array(
            'survey_id'    => array('survey_id', false),
            'survey_title' => array('survey_title', false),
            'responses'    => array('responses', false)
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => 'Delete'
        );
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            global $wpdb;
            $ids = isset($_REQUEST['survey']) ? $_REQUEST['survey'] : array();
            if (is_array($ids)) {
                $ids = implode(',', array_map('intval', $ids));
            }
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}ucm_surveys WHERE id IN($ids)");
            }
        }
    }

    public function search_box($text, $input_id) {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = $input_id . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        if (!empty($_REQUEST['post_mime_type'])) {
            echo '<input type="hidden" name="post_mime_type" value="' . esc_attr($_REQUEST['post_mime_type']) . '" />';
        }
        if (!empty($_REQUEST['detached'])) {
            echo '<input type="hidden" name="detached" value="' . esc_attr($_REQUEST['detached']) . '" />';
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php echo esc_attr($_REQUEST['s'] ?? ''); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
        <?php
    }
}