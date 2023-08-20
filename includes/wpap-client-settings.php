<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ADD PLUGIN SETTHIGNS PAGE MENU
add_action( 'admin_menu', 'wpap_client_plugin_menu' );

function wpap_client_plugin_menu() {
    add_menu_page(
        'WPAutoPublisher-Client Settings',
        'WPAutoPublisher',
        'manage_options',
        'wpautopublisher',
        'wpap_client_settings_page',
        'dashicons-megaphone',
        2
    );
}

// REGISTER SETTINGS PAGE

function wpap_client_settings_page() {
    ?>
    <div class="wpap-wrap">
        <div class="wpap-settings-page-h1"><h1><?php _e( 'WPAutoPublisher-Client Settings', 'wpautopublisher-client' ); ?></h1></div>
        <form action="options.php" method="POST">
            <div class="wpap-inner-wrap">
                <?php  wpap_settings_page_meta(); ?>
                <div id="wpap-client-settings-wrap">
                    
                    <?php
                    settings_fields( 'wpap_client_settings' );
                    do_settings_sections( 'wpautopublisher' );
                    submit_button();
                    
                    ?>

                </div>
            </div>
        </form>
    </div>
    <?php
}

add_action( 'admin_init', 'wpap_client_register_settings' );

function wpap_client_register_settings() {
    add_settings_section( 'wpap_client_settings_section', __( 'WPAP:AI Endpoint Connector', 'wpautopublisher-client' ), 'wpap_client_settings_callback', 'wpautopublisher' );

    add_settings_field( 'wpap_client_api_key', __( 'Client API Key', 'wpautopublisher-client' ), 'wpap_client_api_key_callback', 'wpautopublisher', 'wpap_client_settings_section' );

    register_setting( 'wpap_client_settings', 'wpap_client_api_key' );
}

function wpap_client_settings_callback() {

    $text_heading = __( 'Please enter your API key below:', 'wpautopublisher-client' );
    $text_api_status = __( 'API Connection State:', 'wpautopublisher-client' );
    $api_key_warning = __( 'Your api key is secret! Do not share your API key with others or expose it in any way, including 3rd party entities. If you think your api key has been compromised in any way or has been made public, please reach out to our support immediately.', 'wpautopublisher-client' );
    $api_status = wpap_return_api_status_text();
    $divClassAppend = strtolower($api_status);
    echo <<<HTML
                <div id="wpap-client-api-status-wrap">
                    <div class="wpap-client-api-status-text-wrap">
                        {$text_api_status} 
                        <div class="wpap-key-state-holder">
                            <div class="wpap-status-forjs wpap-status-{$divClassAppend}"></div><div id="wpap-key-status-text">{$api_status}</div>
                        </div> 
                    </div>
                    <p class="api-key-warning-p">{$api_key_warning}</p>
                    <p class="api-key-textheading-p">{$text_heading}</p>   
                </div>
                
            HTML;
}

function wpap_client_api_key_callback() {
    $api_key = get_option( 'wpap_client_api_key' );
    $apy_key_btn = wpap_show_connect_api_button();
    $api_verify_nonce = wp_create_nonce( 'wpap_client_verify_api_key_request' );
    echo '<div class="wpap-client-action-wrap"><span id="wpap-client-key-edit">'.__('edit','wpautopublisher-client').'</span><input type="text" id="wpap_client_api_key" name="wpap_client_api_key" value="' . esc_attr( $api_key ) . '" readonly />'.$apy_key_btn.'<input type="hidden" name="wpap_client_verify_api_key_request" id="wpap_client_verify_api_key_request" value="'.$api_verify_nonce.'" /></div>';
    
}

function wpap_settings_page_meta(){

    echo '<div id="wpap-status-change">';
    if(!get_option('wpap_api_key_status')) {
        // Option does not exist yet
        echo wpap_client_not_active_api_key_message();

    } elseif(get_option('wpap_api_key_status') === 'verified') {
        // Option exists and API key is verified
        echo wpap_client_active_api_key_message();
    } else {
        // Option exists but API key is not verified
        echo wpap_client_not_active_api_key_message();
    }
    echo '</div>';

}

// IF ACTIVE PAGE META
function wpap_client_active_api_key_message() {

    $html = '<div id="wpap-active-api-key-message">
                <p>' . __('Oh yeah! Your API key is active and ready to blast off! 🚀 You have successfully connected your site with WPAutoPublisher. Exciting times ahead!', 'wpautopublisher') . '</p>
                <p>' . __('With great power comes great responsibility, and we know you are ready for it. Your journey to automating your publishing and turning your website into a content powerhouse begins now!', 'wpautopublisher') . '</p>
                <p>' . sprintf(__('Remember, our <a href="%s" target="_blank">documentation</a> is always here to guide you, and if you find any challenges along the way, our support team is ready to assist!', 'wpautopublisher'), 'https://wpautopublisher.com/how-it-works/') . '</p>
                <p>' . __('Let\'s reach for the stars together. Happy publishing! 🌟', 'wpautopublisher') . '</p>
            </div>';

    return $html;
}
// IF NOT ACTIVE PAGE META
function wpap_client_not_active_api_key_message(){

    $html = '<div id="wpap-not-active-api-key-message">
               <p>' . __('Hey there, superhero! Ready to take your website to the stratosphere? You\'re just an API key away from unlocking all features the AI engine has to offer 😎', 'wpautopublisher-client') . '</p>
                <p>' . sprintf(__('Need help finding your key? Check out our detailed <a href="%s" target="_blank">documentation</a>.', 'wpautopublisher-client'), 'https://wpautopublisher.com/how-it-works/') . '</p>
                <p>' . sprintf(__('Curious about all the superpowers you can unlock with WPAutoPublisher? Explore the full list of <a href="%s" target="_blank">features</a>.', 'wpautopublisher-client'), 'https://wpautopublisher.com/ai-engines/') . '</p>
                <p>' . __('Let\'s make the web a better place, one post at a time. 🚀', 'wpautopublisher-client') . '</p>
    
            </div>';

    return $html;
}
