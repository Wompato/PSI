<?php

namespace PSI;

class Rewrites {
    private static $instance = null;

    private function __construct() {
        add_action('init', array($this, 'custom_rewrite_rules'));
        add_filter('query_vars', array($this, 'custom_query_vars'));
        add_action('wp_loaded', array($this, 'flush_rewrite_rules'));
        add_action('template_redirect', array($this, 'restrict_access'));
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function custom_rewrite_rules() {
        // Rewrite rule for user-profile/user_nicename
        add_rewrite_rule('^staff/profile/([^/]+)/?$', 'index.php?pagename=user-profile&user_nicename=$matches[1]', 'top');
    
        // Rewrite rules for other sections
        $sections = array(
            'professional-history',
            'honors-and-awards'
        );
    
        foreach ($sections as $section) {
            add_rewrite_rule("staff/profile/{$section}/([^/]+)/?$", 'index.php?pagename=' . $section . '&user_nicename=$matches[1]', 'top');
        }
    }

    /**
     * Add custom query variables.
     *
     * @param array $query_vars The array of existing query variables.
     * @return array The modified array of query variables.
     */
    public function custom_query_vars($query_vars) {
        $query_vars[] = 'user_nicename';
        return $query_vars;
    }

    public function flush_rewrite_rules() {
        // Ensure that the rewrite rules are flushed only once.
        if (get_option('psi_rewrite_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('psi_rewrite_rules_flushed', '1');
        }
    }

    public function restrict_access() {
        $restricted_pages = ['professional-history', 'honors-and-awards'];  // Add other pages as needed
        if (in_array(get_query_var('pagename'), $restricted_pages) && !get_query_var('user_nicename')) {
            wp_redirect(home_url());  // Redirect to the homepage or to a 404 page
            exit;
        }
    }
    
}
