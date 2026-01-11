<?php

class UCM_Frontend {

    public function __construct() {
        add_shortcode('ucm_survey_test', array($this, 'ucm_render_survey_test'));
    }

    public function ucm_render_survey_test($atts) {
        ob_start();
        include UCM_PATH . 'frontend/views/survey-test-display.php';
        return ob_get_clean();
    }
}

new UCM_Frontend();
