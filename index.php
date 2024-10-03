<?php

// Set the appropriate headers for JSON response
header("Content-Type: application/json");
class Pyrus {
  private $credentials;
  private $apiUrl;
  private $token;
  //private $logger;

  public function __construct($credentials, $apiUrl = "https://api.pyrus.com/v4") {
    $this->credentials = $credentials;
    $this->apiUrl = $apiUrl;
    $this->token = $this->getToken();
  }

  public function setWorkflowApprovalChoice($id, $approvalChoice, $fieldsToUpdate = []) {
    $url = "/tasks/$id/comments";
    $body = ["approval_choice" => $approvalChoice];
    if (!empty($fieldsToUpdate)) {
      foreach ($fieldsToUpdate as $key => $value) {
        $body["field_updates"][] = ["id" => $key, "value" => $value];
      }
    }
    return $this->executeAuthPostCurl($url, $body);
  }

  public function getForm($id) {
    $url = "/forms/$id";
    $method = 'GET';
    return $this->executeAuthGetCurl($url, $method);
  }

  public function getFormByName($name) {
    $forms = $this->getForms();
    foreach ($forms["forms"] as $form) {
      if ($form['name'] == $name) {
          return $form;
      }
    }
    return false;
  }

  private function isNullOrEmpty($var) {
    return !isset($var) || empty($var);
  }

  public function getFormFields($formId) {
      $form = $this->getForm($formId);
      return $this->isNullOrEmpty($form) ? false : $form['fields'];
  }

  public function getFieldByName($formId, $name) {
    $fields = $this->getFormFields($formId);
    if ($fields === false) {
        return false;
    }
    foreach ($fields as $field) {
        if ($field['name'] == $name) {
            return $field;
        }
    }
    return false;
  }

  private function getAuthorizationHeader() {
    return ("Authorization: Bearer " . $this->token);
  }

  private function getToken() {
    $response = $this->executeCurl("/auth", $this->credentials);
    if (empty($response) || empty($response["access_token"])) {
      throw new \Exception("Could not get the authentication token, please verify credentials.");
    }
    return $response["access_token"];
  }

  private function executeAuthGetCurl($endPoint, $params = null) {
    $headers[] = $this->getAuthorizationHeader();
    $method = "GET";
    $params=[];
    return $this->executeCurl($endPoint, $params, $headers, $method);
  }

  private function executeAuthPostCurl($endPoint, $params = null) {
    $headers[] = $this->getAuthorizationHeader();
    $method = "POST";
    return $this->executeCurl($endPoint, $params, $headers, $method);
  }

  private function executeCurl($endPoint, $params = null, $headers = [], $method = 'POST') {
    $curl = curl_init();
    $opts = $this->getBaseCurlOptions($endPoint, $params, $headers, $method);
    curl_setopt_array($curl, $opts);
    $response = curl_exec($curl);
    if ($response === false || curl_errno($curl)) {
      $errorMsg = "Could not execute the curl api request: " . $endPoint ." - ". $method . " parans: " . json_encode($params) . " headers : " . json_encode($headers);
      curl_close($curl);
      throw new \Exception($errorMsg);
    }
    curl_close($curl);
    return json_decode($response, true);
  }

  private function getBaseCurlOptions($endPoint, $params, $headers, $method = 'POST') {
    $opts = [
      CURLOPT_URL => $this->apiUrl . $endPoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FAILONERROR => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => $method,

    ];
    if (!empty($params)) {
      $opts[CURLOPT_POSTFIELDS] = json_encode($params);
    }
    if (!empty($headers)) {
      $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
    }
    return $opts;
  }
} 

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw JSON data from the request body
    $raw_data = file_get_contents('php://input');
    
    // Convert the JSON data to a PHP associative array
    $received_data = json_decode($raw_data, true);

    // Create an instance of the Pyrus class

    $pyrusCredentials =    [
      'login' => "bot@e5b81bc6-7e03-43bc-87a1-4c82a26732fc",
      'security_key' => "Sf4EKtPhMRpNXnyWq7XBj3oUpb9oV0pMk-IJKUL-f--ytJlVH1pMU8bd7IXtrAZNZNaWey279XwP6A6mzlpKpNMPSeA4HCto"
    ];
    $pyrus = new Pyrus($pyrusCredentials);
    try {
      // Call the getFieldByName method
      $fieldStatus = $pyrus->getFieldByName(1452357, "TestCFR");

      // Call the setPyrusWorkflowApprovalsChoice method
      setPyrusWorkflowApprovalsChoice([$received_data["task"]], "approved", [
          $fieldStatus['id'] => "CFR Test",
      ], $pyrus);
      // Return the received data as the response    
      http_response_code(200);
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
