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

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
    http_response_code(422);
    echo json_encode($result);
    exit;
}
$headers[] = "Authorization: Bearer ".$ssToken;
$userData = json_decode(send_request("https://api.smartsender.com/v1/contacts/".$input["userId"], "GET", [], $headers), true);
if ($input["message"] === false) {
    if ($input["text"] == NULL) {
        $result["state"] = false;
        $result["message"]["text"] = "text is missing, or message is not false";
        http_response_code(422);
        echo json_encode($result);
        exit;
    }
    $send["MESSAGES"][0]["message"]["id"] = mt_rand(100000000, 999999999);
    $send["MESSAGES"][0]["message"]["date"] = time();
    $send["MESSAGES"][0]["message"]["text"] = $input["text"];
} else {
    $userMessages = json_decode(send_request("https://api.smartsender.com/v1/contacts/".$input["userId"]."/messages?page=1&limitation=20", "GET", [], $headers), true);
    if (is_array($userMessages["error"]) === true) {
        $send["MESSAGES"][0]["message"]["id"] = mt_rand(100000000, 999999999);
        $send["MESSAGES"][0]["message"]["date"] = time();
        $send["MESSAGES"][0]["message"]["text"] = "Невозможно прочитать сообщение. Проблемы в API Smart Sender, или сообщения у пользователя отсутствуют";
    } else if ($userMessages["collection"][0]["content"]["type"] == "text") {
        $send["MESSAGES"][0]["message"]["id"] = $userMessages["collection"][0]["id"];
        $send["MESSAGES"][0]["message"]["date"] = time();
        $send["MESSAGES"][0]["message"]["text"] = $userMessages["collection"][0]["content"]["resource"]["parameters"]["content"];
        if ($input["text"] != NULL) {
            $send["MESSAGES"][0]["message"]["text"] = $input["text"]."\n - - - - - \n".$send["MESSAGES"][0]["message"]["text"];
        }
    } else {
        $send["MESSAGES"][0]["message"]["id"] = mt_rand(100000000, 999999999);
        $send["MESSAGES"][0]["message"]["date"] = time();
        $send["MESSAGES"][0]["message"]["text"] = "Невозможно прочитать сообщение. Сообщение у пользователя отсутствует или не является текстом";
    }
}
$send["CONNECTOR"] = "SMART_SENDER";
$send["LINE"] = $auth["olId"];
$send["MESSAGES"][0]["user"]["id"] = $input["userId"];
$send["MESSAGES"][0]["user"]["last_name"] = $userData["lastName"];
$send["MESSAGES"][0]["user"]["name"] = $userData["firstName"];
$send["MESSAGES"][0]["user"]["picture"]["url"] = $userData["photo"];
$send["MESSAGES"][0]["user"]["email"] = $userData["email"];
$send["MESSAGES"][0]["user"]["phone"] = $userData["phone"];
$send["MESSAGES"][0]["chat"]["id"] = $input["userId"];
$send["MESSAGES"][0]["chat"]["name"] = $userData["fullName"]." from Smart Sender";
$sendUrl = $auth["client_endpoint"]."imconnector.send.messages?auth=".$auth["access_token"];
$sendMessage = json_decode(send_request($sendUrl, "POST", $send), true);

echo json_encode($sendMessage);





