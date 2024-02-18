<?php
/*
Plugin Name: Movie Fetcher
Description: Ett WordPress-plugin som hämtar data från Magento 2 via GraphQL och visar upp den i frontend.
Version: 1.0
Author: Din författare
*/

require_once('api-client.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class Movie_Fetcher {
    private $api_client;

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'register_movie_post_type')); // Registrera posttypen
        // add_action('init', array($this, 'init')); // Registrera init-metoden som hook för init-evenemanget
    }

    public function activate() {
        // Aktiveringsåtgärder, om några
        this->init();
    }

    public function deactivate() {
        // Avaktiveringsåtgärder, om några
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
            'parent_item_colon'  => __('Parent Movies:'),
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
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt')
        );

        register_post_type('movie', $args);
    }

    public function init() {
        error_log('Init function is running.', 0, ABSPATH . 'wp-content/debug.log');
    

        //Config API Client
        $this->api_client = new API_Client('https://www2.stockholmfilmfestival.se/graphql');

        //Make GraphQL request and fetch data 
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
                    }
                }
            }
        }
        GRAPHQL;
        $response = $this->api_client->make_graphql_request($graphql_query);
        // Hantera API-svar och lagra data i WordPress-databasen
        if ($response) {
            $movies_data = json_decode($response, true);

            $movies = $movies_data['data']['products']['items']; 
            

            if (isset($movies)) {
                
                foreach ($movies as $movie) {
                    // Lagra varje film i WordPress-databasen med hjälp av Data_Handler
                    $title = $movie['name'];
                    $sku = $movie['sku'];
                    $path = $movie['url_key'];
                    $image_url = $movie['image']['url'];

                    $new_post_id = wp_insert_post(array(
                        'post_title'   => $title,
                        // 'post_content' => $post_content,
                        'post_type'    => 'movie',
                        'post_status'  => 'publish',
                    ));


                    if (!is_wp_error($new_post_id)) {
                        // Lagra ytterligare information som metadata, t.ex. SKU och bild-URL
                        add_post_meta($new_post_id, 'sku', $movie['sku']);
                        add_post_meta($new_post_id, 'image_url', $movie['image']['url']);

                        // Lägg till en bild som 'featured image' om det finns en URL för det
                        if (!empty($image_url)) {
                            // var_dump($image_url);

                            $image_id = media_sideload_image($image_url, $new_post_id);
                
                            if (!is_wp_error($imageUrl)) {
                                set_post_thumbnail($new_post_id, $image_id);
                            }
                        }
                    } else {
                        // Hantera fel om posten inte kunde skapas
                        error_log('Error creating post: ' . $new_post_id->get_error_message());
                    }
                }
            }
        }
    }
}

$movie_fetcher = new Movie_Fetcher();
?>
