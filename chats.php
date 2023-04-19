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

$bxSmile = array(":)", ";)", ":D", "8)", ":facepalm:", ":{}", ":(", ":|", ":oops:", ":cry:", ":evil:", ":o", " :/", ":idea:", ":?:", ":!:", ":like:", "[B]", "[/B]");
$replSmile = array("😀", "😉", "😁", "😎", "🤦‍♂️", "😘", "🥺", "😔", "😳", "😢", "😠", "😱", "😏", "💡", "❓", "❗️", "👍", "");

if ($_POST["data"]["CONNECTOR"] == "SMART_SENDER") {
    $getMessage = $_POST["data"]["MESSAGES"][0]["message"];
    $userId = $_POST["data"]["MESSAGES"][0]["chat"]["id"];
} else {
    echo "FAIL CONNECTOR";
    exit;
}

if (array_key_exists("files", $getMessage) === true) {
    if (file_exists("tempFiles") === false) {
        mkdir("tempFiles");
    }
    $extention = explode(".", $getMessage["files"][0]["name"]);
    $extention = $extention[(count($extention) - 1)];
    $fileName = "tempFiles/".mt_rand(1000000, 9999999).time().".".$extention;
    file_put_contents($fileName, file_get_contents($getMessage["files"][0]["link"]));
    if ($getMessage["files"][0]["type"] == "video") {
        $send["type"] = "video";
    } else if ($getMessage["files"][0]["type"] == "image") {
        $send["type"] = "picture";
    } else if ($getMessage["files"][0]["type"] == "audio") {
        $send["type"] = "audio";
    } else {
        $send["type"] = "file";
    }
    $send["watermark"] = 1;
    $send["media"] = $url."/".$fileName;
    $headers[] = "Authorization: Bearer ".$ssToken;
    $sendUrl = "https://api.smartsender.com/v1/contacts/".$userId."/send";
    $sendMessage = json_decode(send_request($sendUrl, "POST", $send, $headers), true);
    if (array_key_exists("error", $sendMessage) === true) {
        $send["type"] = "text";
        unset($send["media"]);
        $send["content"] = $getMessage["files"][0]["name"].PHP_EOL.$getMessage["files"][0]["link"];
        $sendMessage = json_decode(send_request($sendUrl, "POST", $send, $headers), true);
    }
    unlink($fileName);
} else {
    $send["type"] = "text";
    $send["watermark"] = 1;
    if (stripos($getMessage["text"], "[br]") !== false) {
        $send["content"] = explode("[br]", $getMessage["text"])[1];
    } else {
        $send["content"] = $getMessage["text"];
    }
    $send["content"] = str_ireplace($bxSmile, $replSmile, $send["content"]);
    $headers[] = "Authorization: Bearer ".$ssToken;
    $sendUrl = "https://api.smartsender.com/v1/contacts/".$userId."/send";
    $sendMessage = json_decode(send_request($sendUrl, "POST", $send, $headers), true);
    if (array_key_exists("id", $sendMessage) === true) {
        unset($send);
        $send["CONNECTOR"] = "SMART_SENDER";
        $send["LINE"] = $_POST["data"]["LINE"];
        $send["MESSAGES"][0]["im"] = $_POST["data"]["MESSAGES"][0]["im"];
        $send["MESSAGES"][0]["message"]["id"][] = $sendMessage["id"];
        $send["MESSAGES"][0]["chat"]["id"] = $userId;
        $sendUrl = $_POST["auth"]["client_endpoint"]."imconnector.send.status.delivery?auth=".$_POST["auth"]["access_token"];
        $sendDelivery = json_decode(send_request($sendUrl, "POST", $send), true);
        
    }
}



