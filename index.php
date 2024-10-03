<?php
include(__DIR__ . '/pyrus.php');
// Set the appropriate headers for JSON response
header("Content-Type: application/json");

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw JSON data from the request body
    $raw_data = file_get_contents('php://input');
    
    // Convert the JSON data to a PHP associative array
    $received_data = json_decode($raw_data, true);

    // Create an instance of the Pyrus class

    $pyrusCredentials =    [
      'login' => "ecitestadm@outlook.com",
      'security_key' => "xYUsC~6Sf7N4JfbmPBIT7-nIWRKavULwDMiPHxG81qw0R6aBxJ1mIuEnXicSIPiTWQRwgJo-2IIYDjvp0HNOzeR8W1A~cFtD"
    ];
    $pyrus = new Pyrus($pyrusCredentials);
    try {
      // Call the getFieldByName method
      $fieldStatus = $pyrus->getFieldByName(1452357, "ECI Integration (Error Message)");

      // Call the setPyrusWorkflowApprovalsChoice method
      setPyrusWorkflowApprovalsChoice([$received_data["task"]], "approved", [
          $fieldStatus['id'] => "CFR Test",
      ], $pyrus);
      // Return the received data as the response    
      
    //echo json_encode($received_data);
    } catch (Exception $e) {
      http_response_code(501); // Internal Server Error
      echo json_encode(array("error" => $e->getMessage()));
    }
} else {
    // Return an error message for unsupported request methods
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("error" => "Method not allowed."));
}


function setPyrusWorkflowApprovalsChoice($registers, $choice, $fieldsToUpdate, $pyrus) {
  $result = [];
  foreach ($registers as $register) {
    $result[] = $pyrus->setWorkflowApprovalChoice(
      $register["id"],
      $choice,
      $fieldsToUpdate
    );
  }
  return $result;
}

?>
