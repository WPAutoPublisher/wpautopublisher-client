<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//ENDPOINTS

// GET AUTHERS
function register_wpap_restapi_site_data_hook() {
    register_rest_route(
        'wpap-client/v1', '/getSiteData',
        array(
            'methods' => 'POST',
            'callback' => 'wpap_restapi_get_site_data',
            // 'permission_callback' => '__return_true',
        )
    );
}

add_action( 'rest_api_init', 'register_wpap_restapi_site_data_hook' );


// ARTICLE POST ROUTE
function register_wpap_restapi_post_article_hook() {
    register_rest_route(
        'wpap-client/v1', '/postArticle',
        array(
            'methods' => 'POST',
            'callback' => 'wpap_restapi_post_article',
            // 'permission_callback' => '__return_true',
        )
    );
}

add_action( 'rest_api_init', 'register_wpap_restapi_post_article_hook' );


// CALLBACKS

function wpap_restapi_get_site_data( WP_REST_Request $request ) {

    // Check if the 'Content-Type' header is set to 'application/json'
    $content_type = $request->get_header( 'content-type' );
    if ( $content_type !== 'application/json' ) {
        return new WP_Error( 'invalid_content_type', 'Content-Type must be application/json', array('status' => 400) );
    }

    // VERIFY API KEY
    $params = $request->get_json_params();
    $request_api_key_authString = $request->get_header(WPAP_CLIENT_API_KEY_HEADER);  
    $api_key_option = get_option('wpap_api_key_auth_string');

    if( $request_api_key_authString != $api_key_option ){
        return rest_ensure_response( array( 'status' => 403, 'message' => 'Invalid API Key Auth' ) );
    }

    // Get all users
    $users = get_users();
    // Get all categories
    $categories = get_categories(array(
        'hide_empty' => false,
    ));
    // Get all tags
    $tags = get_tags(array(
        'hide_empty' => false,
    ));

    // If users are found
    if( !empty($users) ) {
        // Format users' data
        $users_data = array();
        foreach($users as $user) {
            // Check if user can publish posts
            if (user_can($user, 'publish_posts')) {
                $name = $user->first_name ? $user->first_name : ($user->display_name ? $user->display_name : $user->user_login);
                $users_data[] = array(
                    'id' => $user->ID,
                    'name' => $name,
                );
            }
        }

        // Format categories' data
        $categories_data = array();
        foreach ($categories as $category) {
            $categories_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
            );
        }

        // Format tags' data
        $tags_data = array();
        foreach ($tags as $tag) {
            $tags_data[] = array(
                'id' => $tag->term_id,
                'name' => $tag->name,
            );
        }

        // Return site data
        return rest_ensure_response( array( 
            'status' => 'success', 
            'users' => $users_data,
            'categories' => $categories_data,
            'tags' => $tags_data 
        ));

    } else {
        return rest_ensure_response( array( 'status' => 403, 'message' => 'No data found' ) );
    }
}




function wpap_restapi_post_article( WP_REST_Request $request ) {

    // Check if the 'Content-Type' header is set to 'application/json'
    $content_type = $request->get_header( 'content-type' );
    if ( $content_type !== 'application/json' ) {
        return new WP_Error( 'invalid_content_type', 'Content-Type must be application/json', array('status' => 400) );
    }

    // VERIFY API KEY
    $params = $request->get_json_params();

    $request_api_key_authString = $request->get_header(WPAP_CLIENT_API_KEY_HEADER);  
    $api_key_option = get_option('wpap_api_key_auth_string');

    if( $request_api_key_authString != $api_key_option ){
        return rest_ensure_response( array( 'status' => 403, 'message' => 'Invalid API Key Auth' ) );
    }

    $data = json_decode($request->get_body(), true);
    
    // Validation
    if (!isset($data['title']) || empty($data['title'])) {
        return new WP_Error('title_required', 'Title is required');
    }

    if (!isset($data['author']) || empty($data['author'])) {
        return new WP_Error('author_required', 'Author is required');
    }

    if (!isset($data['content']) || empty($data['content'])) {
        return new WP_Error('content_required', 'Content is required');
    }

    $title = $data['title'];
    $author = $data['author'];
    $slug = isset($data['slug']) ? $data['slug'] : ''; // Slug is optional
    $content = $data['content'];
    $excerpt = isset($data['excerpt']) ? $data['excerpt'] : ''; // Excerpt is optional
    $draft = isset($data['draft']) ? $data['draft'] : false; // Draft status is optional
    $createdTags = isset($data['createdTags']) ? $data['createdTags'] : array(); // Tags are optional
    $image_base64 = isset($data['image_base64']) ? $data['image_base64'] : ''; // Image is optional
    // $tags = isset($data['tags']) ? json_decode(stripslashes($data['tags']), true) : array();
    // $categories = isset($data['categories']) ? json_decode(stripslashes($data['categories']), true) : array();

    $tags = isset($data['tags']) ? wpap_sanitize_taxonomy_data_client($data['tags']) : array();
    $categories = isset($data['categories']) ? wpap_sanitize_taxonomy_data_client($data['categories']) : array();
    
    

// Process categories
if (!empty($categories)) {
    $category_ids = array();
    foreach($categories as $category) {
        $term = term_exists((int)$category['id'], 'category');
        if ($term !== 0 && $term !== null) {
            $category_ids[] = $term['term_id'];
        }
    }
    $categories = $category_ids;
}

// Process existing tags
if (!empty($tags)) {
    $tag_names = array();
    foreach($tags as $tag) {
        $term = get_term((int)$tag['id'], 'post_tag');
        if ($term && !is_wp_error($term)) {
            $tag_names[] = $term->name;
        }
    }
    $tags = $tag_names;
}

// Process newly created tags
if (!empty($createdTags)) {
    foreach($createdTags as $new_tag) {
        $term = term_exists($new_tag, 'post_tag');
        if ($term !== 0 && $term !== null) {
            $tags[] = $new_tag;
        } else {
            $inserted_tag = wp_insert_term($new_tag, 'post_tag');
            if (!is_wp_error($inserted_tag)) {
                $tags[] = $new_tag;
            }
        }
    }
}

// Insert the post
$post_id = wp_insert_post(array(
    'post_title' => $title,
    'post_content' => $content,
    'post_excerpt' => $excerpt,
    'post_name' => $slug,
    'post_author' => $author,
    'post_status' => $draft,
    'post_category' => $categories,  // Add the categories to the post
    'tags_input' =>$tags,  // Add the tags to the post
));
    // $params = $request->get_json_params();

    // error_log('Received parameters: ' . print_r($params, true));
    // Handle the featured image
    if (!empty($image_base64)) {
        // Split the base64 string in data and image data
        $image_parts = explode(";base64,", $image_base64);
        $base64_string = $image_parts[1];
    
        // Save the image file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . uniqid() . '.jpg';
        file_put_contents($file_path, base64_decode($base64_string));
    
        // Attach the image to the post
        $filetype = wp_check_filetype(basename($file_path), null);
        $wp_upload_dir = wp_upload_dir();
        $attachment = array(
            'guid' => $wp_upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($post_id, $attach_id);
    }
    

    return new WP_REST_Response(array(
        'post_id' => $post_id,
        'post_url' => get_permalink($post_id),
        'post_title' => get_post_field('post_title', $post_id)
    ), 200);
    
}
