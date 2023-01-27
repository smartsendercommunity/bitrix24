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
unset($temp["id"]); unset($temp["fields"]); unset($temp["userId"]); unset($temp["action"]); unset($temp["filter"]); unset($temp["stageCode"]);
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
} else {
    if (file_exists("users.json") === true) {
        $usersData = json_decode(file_get_contents("users.json"), true);
    }
    if ($usersData[$input["userId"]] == NULL) {
        $result["state"] = false;
        $result["message"]["userId"] = "Contact data not found. Please, create a contact";
        echo json_encode($result);
        exit;
    }
}

if ($input["action"] == "get") { // Чтение сделки/сделок
    if ($input["dealId"] == NULL) {
        $send["filter"]["CONTACT_ID"] = $usersData[$input["userId"]];
        $send["select"] = array("*", "UF_*");
        $result = send_request($auth["client_endpoint"]."crm.deal.list?auth=".$auth["access_token"], "POST", $send);
        $getFields = json_decode(send_request($auth["client_endpoint"]."crm.deal.fields?auth=".$auth["access_token"]), true);
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
    } else {
        $result = send_request($auth["client_endpoint"]."crm.deal.get?auth=".$auth["access_token"]."&id=".$input["dealId"]);
        $getFields = json_decode(send_request($auth["client_endpoint"]."crm.deal.fields?auth=".$auth["access_token"]), true);
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
    }
} else { // Создание или обновление сделки
    $getFields = json_decode(send_request($auth["client_endpoint"]."crm.deal.fields?auth=".$auth["access_token"]."&id=".$input["id"]), true);
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
    $send["fields"]["CONTACT_IDS"][] = $usersData[$input["userId"]];
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
    if ($input["dealId"] == NULL) { // Создание сделки
        $sendUrl = $auth["client_endpoint"]."crm.deal.add?auth=".$auth["access_token"];
        $result = json_decode(send_request($sendUrl, "POST", $send), true);
        $input["dealId"] = $result["result"];
        //sleep(1);
        //echo json_encode($result);
    } else { // обновление сделки
        $send["id"] = $input["dealId"];
        $sendUrl = $auth["client_endpoint"]."crm.deal.update?auth=".$auth["access_token"];
        $result = json_decode(send_request($sendUrl, "POST", $send),true);
    }
}

if (array_key_exists("stageCode", $input) === true) {
    $targetUrl = $auth["client_endpoint"]."crm.automation.trigger/?auth=".$auth["access_token"]."&target=DEAL_".$input["dealId"]."&code=".$input["stageCode"];
    $result["stage"] = json_decode(send_request($targetUrl), true);
    $result["stageUrl"] = $targetUrl;
}

echo json_encode($result);
