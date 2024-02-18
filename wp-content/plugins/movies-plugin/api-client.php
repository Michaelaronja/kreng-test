<?php
class API_Client {
    // Instance variables to store API endpoint och auth token
    private $graphql_endpoint;

    // Constructor that creates new instances of the API Client Class with the API endpoint
    public function __construct($endpoint) {
        $this->graphql_endpoint = $endpoint;
    }

    // Method that makes the GraphQL request to API
    public function make_graphql_request($query) {
       
        // Config alternativ for HTTP request including headers, method and content
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => array(
                    'Content-Type: application/json',
                ),
                'content' => json_encode(array('query' => $query)), // Convert GraphQL request to JSON
            ),
        );

        // Create a stream context with the defined options
        $context = stream_context_create($options);


        // Make a HTTP request to the API endpoint with the defined alternatives and return the response
        return file_get_contents($this->graphql_endpoint, false, $context);
    }
}
?>
