<?php

// Set the appropriate headers for JSON response
header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw JSON data from the request body
    $raw_data = file_get_contents('php://input');
    
    // Convert the JSON data to a PHP associative array
    $received_data = json_decode($raw_data, true);

    // Return the received data as the response
    echo json_encode($received_data);
} else {
    // Return an error message for unsupported request methods
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Method not allowed."));
}

?>