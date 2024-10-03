<?php

class Pyrus {
  private $credentials;
  private $apiUrl;
  private $token;
  //private $logger;

  public function __construct($credentials, $apiUrl = "https://api.pyrus.com/v4") {
    $this->credentials = $credentials;
    $this->apiUrl = $apiUrl;
    //$this->logger = $logger;
    $this->token = $this->getToken();
  }

  public function getRegisters($formId, $filters=[]) {
    $url = "/forms/$formId/register" . (empty($filters) ? "" : "?" . http_build_query($filters));
    return $this->executeAuthGetCurl($url);
  }

  public function getEntities($entityName) {
    $url = '/' . $entityName;
    return $this->executeAuthGetCurl($url);
  }

  public function updateRegister($id, $params) {
    $url = "/tasks/$id/comments";
    $body = ["field_updates" => []];
    foreach ($params as $key => $value) {
      $body["field_updates"][] = ["id" => $key, "value" => $value];
    }
    return $this->executeAuthPostCurl($url, $body);
  }

  public function createRegister($formId, $params) {
    $url = "/tasks";
    $body = ["form_id" => $formId, "fields" => []];
    foreach ($params as $key => $value) {
      $body["fields"][] = ["id" => $key, "value" => $value];
    }
    return $this->executeAuthPostCurl($url, $body);
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

  public function setWorkflowStep($id, $step, $approver) {
    $url = "/tasks/$id/comments";
    $steps = array_fill(0, ($step-1), []);
    $steps[] = [["id" => $approver]];
    $body = [
      "approvals_rerequested"=> $steps
    ];
    return $this->executeAuthPostCurl($url, $body);
  }

  public function getCatalog($id) {
    $url = "/catalogs/$id";
    $method = 'GET';
    return $this->executeAuthGetCurl($url, $method);
  }

  public function getForms() {
      $method = 'GET';
      return $this->executeAuthGetCurl("/forms/", $method);
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

  public function getMembers() {
    $url = "/members/";
    $method = 'GET';
    $members = $this->executeAuthGetCurl($url, $method);
	return $members["members"];
  }

  public function getMemberByName($name) {
    $members = $this->getMembers();
    if ($members === false) {
        return false;
    }

    foreach ($members as $member) {
      //$name to lowercase
        $name = strtolower($name);
        $firstNameIncluded = isset($member['first_name']) && !empty($member['first_name']) && stripos(strtolower($name), strtolower($member['first_name'])) !== false;
        $lastNameIncluded = isset($member['last_name']) && (empty($member['last_name']) || stripos(strtolower($name), strtolower($member['last_name'])) !== false);

        if ($firstNameIncluded && $lastNameIncluded) {
            return $member;
        }
    }
    return false;
  }

  public function getRoles() {
    $url = "/roles/";
    $method = 'GET';
    $roles = $this->executeAuthGetCurl($url, $method);
	return $roles["roles"];
  }

  public function getRolByName($name) {
    $roles = $this->getRoles();
    if ($roles === false) {
        return false;
    }
    foreach ($roles as $role) {
      $nameIncluded = isset($role['name']) && !empty($role['name']) && stripos(strtolower($name), strtolower($role['name'])) !== false;
      if ($nameIncluded) {
          return $role;
      }
    }
    return false;
  }

  public function getUserByName($name) {
    if ($member = $this->getMemberByName($name)) {
        return $member;
    }
    if ($role = $this->getRolByName($name)) {
        return $role;
    }
    return false;
  }

  public function getStepIdByName($formId,$name) {
    $form = $this->getForm($formId);

    if (empty($form) || empty($form["steps"])) {
      return "";
    }

    foreach ($form["steps"] as $id => $step) {
      if ($step === $name) {
        return $id;
      }
    }
    return "";
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
      //$this->logger->error("$errorMsg [" . curl_errno($curl) . " " . curl_error($curl) . "]");
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
