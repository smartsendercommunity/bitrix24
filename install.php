<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: text/html; charset=utf-8');

http_response_code(200);

include('config.php');

if ($_POST["event"] == "ONAPPINSTALL") {
    $auth = $_POST["auth"];
    $auth["scope"] = explode(",", $auth["scope"]);
    file_put_contents("auth.json", json_encode($auth));
    $send["ID"] = "SMART_SENDER";
    $send["NAME"] = "Smart Sender";
    $send["ICON"]["DATA_IMAGE"] = "data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" viewBox=\"0 0 93 93\" xml:space=\"preserve\"><rect fill=\"rgb(255 255 255)\" width=\"93\" height=\"93\"/><path style=\"fill:rgb(19 128 246)\" d=\"M84.8,38.3C84.8,17.2,67.6,0,46.5,0C25.4,0,8.2,17.2,8.2,38.3c0,21.1,17.1,38.3,38.3,38.3v15.8l26.9-26.9 C80.4,58.7,84.8,49,84.8,38.3z M63.7,55.9L46.5,73.1V62.9c-13.6,0-24.6-11-24.6-24.6c0-13.6,11-24.6,24.6-24.6 c13.6,0,24.6,11,24.6,24.6c0,6.8-2.7,12.9-7.2,17.4L63.7,55.9z\"/></svg>";
    $send["ICON"]["COLOR"] = "#1380F6";
    $send["PLACEMENT_HANDLER"] = $url."/install.php?connector=smartsender";
    $sendUrl = $auth["client_endpoint"]."imconnector.register?auth=".$auth["access_token"];
    send_request($sendUrl, "POST", $send);
    unset($send);
    $sendUrl = $auth["client_endpoint"]."imopenlines.config.list.get?auth=".$auth["access_token"];
    $getLines = json_decode(send_request($sendUrl, "POST"), true);
    if (is_array($getLines["result"]) === true) {
        foreach ($getLines["result"] as $oneLines) {
            if ($oneLines["LINE_NAME"] == "Smart Sender Chats") {
                $auth["olId"] = $oneLines["ID"];
                file_put_contents("auth.json", json_encode($auth));
                $send["CONNECTOR"] = "SMART_SENDER";
                $send["LINE"] = $oneLines["ID"];
                $send["ACTIVE"] = 1;
                $sendUrl = $auth["client_endpoint"]."imconnector.activate?auth=".$auth["access_token"];
                send_request($sendUrl, "POST", $send);
                unset($send);
                $connectLine = true;
                break;
            }
        }
    }
    if ($connectLine != true) {
        $send["PARAMS"]["ACTIVE"] = "Y";
        $send["PARAMS"]["LINE_NAME"] = "Smart Sender Chats";
        $send["PARAMS"]["WELCOME_MESSAGE"] = "N";
        $send["PARAMS"]["VOTE_MESSAGE"] = "N";
        $sendUrl = $auth["client_endpoint"]."imopenlines.config.add?auth=".$auth["access_token"];
        $addOL = json_decode(send_request($sendUrl, "POST", $send), true);
        unset($send);
        $auth["olId"] = $addOL["result"];
        file_put_contents("auth.json", json_encode($auth));
        $send["CONNECTOR"] = "SMART_SENDER";
        $send["LINE"] = $addOL["result"];
        $send["ACTIVE"] = 1;
        $sendUrl = $auth["client_endpoint"]."imconnector.activate?auth=".$auth["access_token"];
        send_request($sendUrl, "POST", $send);
        unset($send);
    }
    $send["event"] = "OnImConnectorMessageAdd";
    $send["handler"] = $url."/chats.php";
    $sendUrl = $auth["client_endpoint"]."event.bind?auth=".$auth["access_token"];
    send_request($sendUrl, "POST", $send);
    unset($send);
    $send["event"] = "onCrmLeadAdd";
    $send["handler"] = $url."/binding.php";
    send_request($sendUrl, "POST", $send);
    unset($send);
    $send["event"] = "onCrmContactAdd";
    $send["handler"] = $url."/binding.php";
    $sendUrl = $auth["client_endpoint"]."event.bind?auth=".$auth["access_token"];
    send_request($sendUrl, "POST", $send);
    unset($send);
    echo "install ok";
} else {
    $auth = json_decode(file_get_contents("auth.json"), true);
    echo 'Ручная настройка коннектора в данный момент не поддерживается. Пожалуйста, переустановите локальное приложение для автоматической настройки.';
}

 



