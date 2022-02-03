<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

include('connect.php');

//--------------

if ($input["method"] == NULL) {
    $result["state"] = false;
    $result["message"]["method"] = "method is missing";
    echo json_encode($result);
    exit;
}

if ($input["data"] == NULL) {
    $result = json_decode(send_request($auth["client_endpoint"].$input["method"]."?auth=".$auth["access_token"]), true);
} else {
    $result = json_decode(send_request($auth["client_endpoint"].$input["method"]."?auth=".$auth["access_token"], "POST", $input["data"]), true);
}

echo json_encode($result);