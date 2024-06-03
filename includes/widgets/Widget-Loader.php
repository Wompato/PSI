<?php

namespace PSI\Widgets;

class Widget_Loader {
    private static $instance = null;

    private function __construct() {
        add_action( 'widgets_init', array( $this, 'register_widgets' ) );
        
    }

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_widgets() {
        register_widget( 'PSI\Widgets\Dynamic_Category_Archive' );
        register_widget( 'PSI\Widgets\Dynamic_Category_Recent_Posts' );
    }

    
}
