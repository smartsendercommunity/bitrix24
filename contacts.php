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

$temp = $input;
unset($temp["id"]); unset($temp["fields"]); unset($temp["userId"]); unset($temp["action"]); unset($temp["filter"]);
if ($input["fields"] != NULL && is_array($input["fields"]) === true) {
    $input["fields"] = array_merge($input["fields"], $temp);
} else {
    $input["fields"] = $temp;
}

if ($input["userId"] == NULL) {
    $result["state"] = false;
    $result["message"]["userId"] = "userId is missing";
    echo json_encode($result);
    exit;
}

if ($input["action"] == "delete") { // Удаление контакта
    if ($input["id"] == NULL) {
        $result["state"] = false;
        $result["message"]["id"] = "id is required to delete a contact";
        echo json_encode($result);
        exit;
    }
    echo send_request($auth["client_endpoint"]."crm.contact.delete?auth=".$auth["access_token"]."&id=".$input["id"]);
} else if ($input["action"] == "get") { // Чтение контакта
    if (file_exists("users.json") === true) {
        $usersData = json_decode(file_get_contents("users.json"), true);
        if ($usersData[$input["userId"]] != NULL) {
            $contactId = $usersData[$input["userId"]];
        } 
    }
    if ($input["id"] == NULL && $contactId == NULL) {
        $result["state"] = false;
        $result["message"]["id"] = "id is required to get a contact";
        echo json_encode($result);
        exit;
    }
    $result = send_request($auth["client_endpoint"]."crm.contact.get?auth=".$auth["access_token"]."&id=".$input["id"]);
    $getFields = json_decode(send_request($auth["client_endpoint"]."crm.contact.fields?auth=".$auth["access_token"]."&id=".$input["id"]), true);
    if (is_array($getFields["result"]) === true) {
        foreach ($getFields["result"] as $oneField) {
            if ($oneField["title"] != NULL && $oneField["listLabel"] != NULL) {
                $search[] = json_encode($oneField["title"]).":";
                $replace[] = json_encode($oneField["listLabel"]).":";
            }
        }
    }
    echo str_replace($search, $replace, $result);
    exit;
} else if ($input["action"] == "search") { // Поиск контакта
    $result["state"] = false;
    $result["message"]["action"] = "search not production";
    echo json_encode($result);
    exit;
} else { // Создание или обновление контакта
    $getFields = json_decode(send_request($auth["client_endpoint"]."crm.contact.fields?auth=".$auth["access_token"]."&id=".$input["id"]), true);
    if (is_array($getFields["result"]) === true) {
        foreach ($getFields["result"] as $oneField) {
            if ($oneField["title"] != NULL && $oneField["listLabel"] != NULL) {
                $replace[] = json_encode($oneField["title"]).":";
                $search[] = json_encode($oneField["listLabel"]).":";
            }
        }
    }
    $send["fields"] = json_decode(str_replace($search, $replace, json_encode($input["fields"])), true);
    $send["fields"]["ORIGINATOR_ID"] = "SMART_SENDER";
    $send["fields"]["ORIGIN_ID"] = $input["userId"];
    if ($input["fields"]["email"] != NULL) {
        if (is_array($input["fields"]["email"]) === true) {
            foreach ($input["fields"]["email"] as $email) {
                $send["fields"]["EMAIL"][]["VALUE"] = $email;
            }
        } else {
            $send["fields"]["EMAIL"][]["VALUE"] = $input["fields"]["email"];
        }
    }
    if ($input["fields"]["phone"] != NULL) {
        if (is_array($input["fields"]["phone"]) === true) {
            foreach ($input["fields"]["phone"] as $phone) {
                $send["fields"]["PHONE"][]["VALUE"] = $phone;
            }
        } else {
            $send["fields"]["PHONE"][]["VALUE"] = $input["fields"]["phone"];
        }
    }
    if ($input["fields"]["firstName"] != NULL) {
        $send["fields"]["NAME"] = $input["fields"]["firstName"];
    }
    if ($input["fields"]["lastName"] != NULL) {
        $send["fields"]["LAST_NAME"] = $input["fields"]["lastName"];
    }
    if ($input["fields"]["utm_campaign"] != NULL) {
        $send["fields"]["UTM_CAMPAIGN"] = $input["fields"]["utm_campaign"];
    }
    if ($input["fields"]["utm_content"] != NULL) {
        $send["fields"]["UTM_CONTENT"] = $input["fields"]["utm_content"];
    }
    if ($input["fields"]["utm_medium"] != NULL) {
        $send["fields"]["UTM_MEDIUM"] = $input["fields"]["utm_medium"];
    }
    if ($input["fields"]["utm_source"] != NULL) {
        $send["fields"]["UTM_SOURCE"] = $input["fields"]["utm_source"];
    }
    if ($input["fields"]["utm_term"] != NULL) {
        $send["fields"]["UTM_TERM"] = $input["fields"]["utm_term"];
    }
    if (file_exists("users.json") === true) {
        $usersData = json_decode(file_get_contents("users.json"), true);
        if ($usersData[$input["userId"]] != NULL) {
            $contactId = $usersData[$input["userId"]];
        } 
    }
    if ($contactId == NULL && $input["id"] != NULL) {
        $check = json_decode(send_request($auth["client_endpoint"]."crm.contact.get?auth=".$auth["access_token"]."&id=".$input["id"]), true);
        if ($check["result"] != NULL && $check["result"]["ORIGINATOR_ID"] != "SMART_SENDER") {
            $contactId = $input["id"];
            if (file_exists("users.json") === true) {
                $usersData = json_decode(file_get_contents("users.json"), true);
            }
            $usersData[$input["userId"]] = $input["id"];
            file_put_contents("users.json", json_encode($usersData));
        }
    }
    if ($contactId == NULL) { // Создание контакта
        $sendUrl = $auth["client_endpoint"]."crm.contact.add?auth=".$auth["access_token"];
        echo send_request($sendUrl, "POST", $send);
    } else { // обновление контакта
        $send["id"] = $contactId;
        $sendUrl = $auth["client_endpoint"]."crm.contact.update?auth=".$auth["access_token"];
        echo send_request($sendUrl, "POST", $send);
    }
}

