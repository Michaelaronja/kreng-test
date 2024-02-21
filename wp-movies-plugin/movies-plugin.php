<?php
/*
Plugin Name: Stockholm Filmfestival Plugin
Description: Fetch Movies from Stockholm Filmfestival in category Junior 2024
Version: 1.0
Author: Michaela Bång
*/

require_once('api-client.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class Movie_Fetcher {
    private $api_client;

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));

        add_action('init', array($this, 'register_movie_post_type'));
    }

    public function activate() {    
        $this->create_movies_posts();
    }


    private function create_movies_posts() {
        
        $this->api_client = new API_Client('https://www2.stockholmfilmfestival.se/graphql');

        $graphql_query = <<<GRAPHQL
        {
            products(
                filter: {
                    category_id: { eq: "26" }
                }
            ) {
                items {
                    name
                    sku
                    url_key
                    image {
                        url
                        label
                    }
                }
            }
        }
        GRAPHQL;

        $response = $this->api_client->make_graphql_request($graphql_query);

        if ($response) {
            $movies_data = json_decode($response, true);
    
            $movies = $movies_data['data']['products']['items']; 
    
            if (isset($movies)) {
                foreach ($movies as $movie) {
            
                    $title = $movie['name'];
            
                    // Check if a post with the same title and post type movie already exists in DB
                    global $wpdb;
                    $post_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s",
                        $title,
                        'movie'
                    ));
        
                    if ($post_exists) {
                        $post_status = get_post_status($post_exists);
                        
                        // If the posts are in the trash, but not deleted from DB, publish them again 
                        if ($post_status === 'trash') {
                            wp_untrash_post($post_exists);

                            wp_update_post(array(
                                'ID' => $post_exists,
                                'post_status' => 'publish',
                            ));
                        }
                    } else {
                        // Create new posts if they are not found in DB
                        $sku = $movie['sku'];
                        $url_key = $movie['url_key'];
                        $image_url = $movie['image']['url'];
                        $image_label = $movie['image']['label'];
                        $url = 'https://www.stockholmfilmfestival.se/' . $url_key;
            
                        $new_post_id = wp_insert_post(array(
                            'post_title'   => $title,
                            'post_type'    => 'movie',
                            'post_status'  => 'publish',
                        ));
            
                        if (!is_wp_error($new_post_id)) {
                            update_post_meta($new_post_id, 'sku', $sku);
                            update_post_meta($new_post_id, 'url', $url);
                            update_post_meta($new_post_id, 'image_url', $image_url);
            
                            if (!empty($image_url)) {
                                $attachment = media_sideload_image($image_url, $new_post_id, $image_label, 'id');
                                if (!is_wp_error($attachment)) {
                                    set_post_thumbnail($new_post_id, $attachment);
                                }
                            }
                        } else {
                            error_log('Error creating post: ' . $new_post_id->get_error_message());
                        }
                    }
                }
            }
        }
    }


    public function register_movie_post_type() {
        $labels = array(
            'name'               => __('Movies'),
            'singular_name'      => __('Movie'),
            'menu_name'          => __('Movies'),
            'name_admin_bar'     => __('Movie'),
            'add_new'            => __('Add New'),
            'add_new_item'       => __('Add New Movie'),
            'new_item'           => __('New Movie'),
            'edit_item'          => __('Edit Movie'),
            'view_item'          => __('View Movie'),
            'all_items'          => __('All Movies'),
            'search_items'       => __('Search Movies'),
            'not_found'          => __('No movies found.'),
            'not_found_in_trash' => __('No movies found in Trash.')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'movie'),
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'          => true, 
            'rest_controller_class' => 'WP_REST_Posts_Controller'
        );

        register_post_type('movie', $args);

        $meta_fields = array(
            array(
                'name' => 'sku',
                'description' => 'Product sku'
            ),
            array(
                'name' => 'url',
                'description' => 'Product url'
            ),
            // For easy access in frontend | avoiding making another request fetching featured image
            array(
                'name' => 'image_url',
                'description' => 'Image url'
            )
        );

        foreach ($meta_fields as $meta_field) {
            register_post_meta('movie', $meta_field['name'], array(
                'show_in_rest' => true,
                'single' => true,
                'type' => 'string',
                'description' => $meta_field['description'],
                'auth_callback' => function() {
                    return current_user_can('edit_posts');
                }
            ));
        }
    }
    
}

$movie_fetcher = new Movie_Fetcher();
?>