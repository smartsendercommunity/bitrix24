<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

//--------------

$input = json_decode(file_get_contents('php://input'), true);
include ('config.php');

$auth = json_decode(file_get_contents("auth.json"), true);
if ($auth["expires"] < (time() + 10)) {
    $sendUrl = "https://oauth.bitrix.info/oauth/token/?grant_type=refresh_token&client_id=".$clientId."&client_secret=".$clientSecret."&refresh_token=".$auth["refresh_token"];
    $reauth = json_decode(send_request($sendUrl), true);
    $auth["access_token"] = $reauth["access_token"];
    $auth["expires"] = $reauth["expires"];
    $auth["refresh_token"] = $reauth["refresh_token"];
    file_put_contents("auth.json", json_encode($auth));
}












