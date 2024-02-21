<?php
class API_Client {

    private $graphql_endpoint;

    public function __construct($endpoint) {
        $this->graphql_endpoint = $endpoint;
    }

    public function make_graphql_request($query) {
        $curl_handle = curl_init();
    
        curl_setopt_array($curl_handle, array(
            CURLOPT_URL => $this->graphql_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(array('query' => $query)),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
    
        $response = curl_exec($curl_handle);

        if ($response === false) {
            $error_message = curl_error($curl_handle);
            echo "Something went wrong:" . $error_message;
        }
    
        curl_close($curl_handle);
    
        return $response;
    }
}
?>
