<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// INITIALIZE OPTION TO HOLD KEY VERIFICATIONS STATUS

function wpap_api_key_verification_status() {
    if (!get_option('wpap_api_key_status')) {
        add_option('wpap_api_key_status', 'unverified');
    }
}

// INTILIZE URL ENDPOINT FOR API KEY VERIFY
function wpap_set_api_key_verification_url() {
    if (!get_option('api_key_verification_url')) {
        add_option('api_key_verification_url', 'https://wpautopublisher.com/wp-json/wpap-ai-engine/v1/verify');
    }
}




// INITIALIZE OPTION TO HOLD KEY authString

function wpap_api_key_auth_string($authString) {
    update_option( 'wpap_api_key_auth_string', $authString );
}

// DELETE ALL OPTIONS FOR DEACTIVATION HOOK
function wpap_delete_all_options() {
    delete_option('wpap_api_key_status');
    delete_option('api_key_verification_url');
    delete_option('wpap_api_key_auth_string');
}

// CONDITION INITALIZE CONN BTN

function wpap_show_connect_api_button() {
    //ESCAPED ON OUTPUT ON OUTPUT SIDES
    return '<button id="wpap-client-initialize-connection" name="wpap-client-initialize-connection">Invoke API Verification</button>';
}

// HELPER TO GET STATUS
function wpap_return_api_status_text() {
    $api_status_text = '';
    $api_status = get_option('wpap_api_key_status');

    if ($api_status == 'verified') {
        // Display the Connect API button
        $api_status_text = 'Online';
    }

    if ($api_status == 'unverified') {
        // Display the Connect API button
        $api_status_text = 'Offline';
    }

    return $api_status_text;

}

// SANITIZE WPAP AUTO PUBLISHER SENT DATA OF TAGS AND CATS
function wpap_sanitize_taxonomy_data_client($data) {
    return array_map(function($item) {
        return array(
            'id' => intval($item['id']),
            'name' => sanitize_text_field($item['name'])
        );
    }, $data);
}