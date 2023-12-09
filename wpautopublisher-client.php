<?php
/**
 * Plugin Name: AutoPublisher-Client
 * Description: Supercharge your WordPress site with AI! AutoPublisher-Client seamlessly integrates cutting-edge AI tools like DALL-E, Stable Diffusion, and ChatGPT4. Transform your platform into a content creation powerhouse, generating captivating text and images effortlessly. Experience the future of content creation, today.
 * Version: 1.0.0
 * Requires at least: 4.8
 * Tested up to: 6.4.2
 * Stable tag: 4.8
 * Requires PHP: 7.0
 * Author: WPAutoPublisher Team
 * Author URI: https://WPAutoPublisher.com
 * License: GPL2
 * Text Domain: wpautopublisher-client
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// DEFIEN CONSTANTS
define( 'WPAUTOPUBLISHER_CLIENT_VERSION', '1.0.0' );
define( 'WPAUTOPUBLISHER_CLIENT_API_URL_VERIFY', 'http://localhost/wpautopublisher/wp-json/wpap-ai-engine/v1/verify' );
define( 'WPAUTOPUBLISHER_CLIENT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAUTOPUBLISHER_CLIENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

const WPAP_ASSETS_PATH = WPAUTOPUBLISHER_CLIENT_PLUGIN_URL.'assets/';
const WPAP_INCLUDE_PATH = WPAUTOPUBLISHER_CLIENT_PLUGIN_DIR.'includes/';
const WPAP_CLIENT_API_KEY_HEADER = 'X-Authorization';


// REQUIRE PLUGIN CORE FILES REQUIRE
require_once WPAP_INCLUDE_PATH . '/wpap-client-settings.php';
require_once WPAP_INCLUDE_PATH . '/wpap-client-helpers.php';
require_once WPAP_INCLUDE_PATH . '/wpap-client-api.php';
require_once WPAP_INCLUDE_PATH . '/wpap-client-restapi.php';



// REGISTER SCRIPTS

function wpap_client_enqueue_scripts() {
    wp_enqueue_script( 'wpap-main', WPAP_ASSETS_PATH . 'scripts/wpap-main.js', array('jquery'), '1.0', true );
    wp_enqueue_script( 'wpap_client_ajax', WPAP_ASSETS_PATH . 'scripts/wpap-client-ajax.js', array('jquery','wpap-main'), '1.0', true );
  
        // LOCALZIE
        wp_localize_script(
            'wpap_client_ajax',
            'wpap_ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
        );

}

add_action( 'admin_enqueue_scripts', 'wpap_client_enqueue_scripts' );


function wpap_client_enqueue_styles() {
    wp_enqueue_style('wpap_admin_styles', WPAP_ASSETS_PATH . 'styles/wpap-client-styles.css', array(), '1.0');
}

add_action( 'admin_enqueue_scripts', 'wpap_client_enqueue_styles' );


function wpap_client_activation_tasks() {
    wpap_set_api_key_verification_url();
    wpap_api_key_verification_status();
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'wpap_client_activation_tasks' );

function wpap_client_deactivation_tasks() {
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'wpap_client_deactivation_tasks' );

function wpap_client_uninstall_tasks() {
    // Delete the options created 
    wpap_delete_all_options();
    flush_rewrite_rules();
}

register_uninstall_hook(__FILE__, 'wpap_client_uninstall_tasks');
