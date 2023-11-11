<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// REQUESTS
//VERIFY API KEY
function wpap_client_verify_api_key_request($api_key, $linked_to_url) {
    $saas_api_url = WPAUTOPUBLISHER_CLIENT_API_URL_VERIFY;

    $api_key = strval($api_key);
    $api_key = trim($api_key);

    $request_data = array(
        'linked_to_url' => $linked_to_url,
    );

    $response = wp_remote_post($saas_api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
             WPAP_CLIENT_API_KEY_HEADER =>  $api_key,
        ),
        'body' => json_encode($request_data),
        'timeout' => 20,
    ));
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return array(
            'error' => true,
            'message' => 'Error in wpap_verify_api_key_request(): ' . $error_message
        );
    } else if (wp_remote_retrieve_response_code($response) == 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true); 

        // Check if authString exists in the response body before accessing it
        if (isset($body['authString'])) {
            $authString = $body['authString']; 
            wpap_api_key_auth_string($authString);
        }
        
        return $body;
        
    } else {
        return array(
            'error' => true,
            'message' => 'Unexpected response code: ' . wp_remote_retrieve_response_code($response)
        );
    }
}


// AJAX CALLBACKS
function wpap_client_initialize_connection() {

    // check user login
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'User not logged in'));
        exit;
    }

    // Check nonce 
    $nonce = isset($_POST['wpapClientVerifyAPIKeyNonce']) ? sanitize_text_field($_POST['wpapClientVerifyAPIKeyNonce']) : '';
    if (!wp_verify_nonce($nonce, 'wpap_client_verify_api_key_request')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        exit;
    }

    // Get API key from request
    $api_key = isset($_POST['wpap_client_api_key']) ? sanitize_text_field($_POST['wpap_client_api_key']) : '';

    // If the API key is not provided, return an error
    if(empty($api_key)) {
        // wp_send_json_error(array('message' => 'API key not provided'));
        $message = wpap_client_not_active_api_key_message();
        wp_send_json_error(array('message' => isset($result['message']) ? $result['message'] : 'An error occurred: empty or invalid key.', 'apiKeyStatusMsg' => $message));

        // exit;
    }

    // Call the verification function
    $result = wpap_client_verify_api_key_request($api_key, get_site_url());

    if (!isset($result['error']) && isset($result['status']) && $result['status'] === 'success') {
         delete_option('wpap_api_key_status');
         update_option('wpap_api_key_status', 'verified');
         update_option('wpap_client_api_key', $api_key);
         $message = wpap_client_active_api_key_message();
         wp_send_json_success(array('message' =>  isset($result['message']) ? $result['message'] : 'An error occurred', 'apiKeyStatusMsg' => $message));

    }  else if (isset($result['type']) && $result['type'] === 'duplicate_url') {
        // Handle the specific case where the URL is already connected
        // Maybe show a different notification or message
        $message = wpap_client_active_api_key_message();
        wp_send_json_success(array('message' =>  isset($result['message']) ? $result['message'] : 'An error occurred', 'apiKeyStatusMsg' => $message));
    } else {
        delete_option('wpap_api_key_status');
        update_option('wpap_api_key_status', 'unverified');
        update_option('wpap_client_api_key', $api_key);
        
        $message = wpap_client_not_active_api_key_message();
        wp_send_json_error(array('message' => isset($result['message']) ? $result['message'] : 'An error occurred', 'apiKeyStatusMsg' => $message));
    }
}
add_action('wp_ajax_wpap_client_initialize_connection', 'wpap_client_initialize_connection');

