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

if ($_POST["event"] == "ONCRMLEADADD") {
    $getLead = json_decode(send_request($_POST["auth"]["client_endpoint"]."crm.lead.get?auth=".$_POST["auth"]["access_token"]."&id=".$_POST["data"]["FIELDS"]["ID"]), true);
    if ($getLead["result"]["IM"] != NULL && is_array($getLead["result"]["IM"]) === true) {
        foreach ($getLead["result"]["IM"] as $IM) {
            if (stripos($IM["VALUE"], "SMART_SENDER") !== false) {
                $upd["id"] = $_POST["data"]["FIELDS"]["ID"];
                $upd["fields"]["ORIGINATOR_ID"] = "SMART_SENDER";
                $upd["fields"]["ORIGIN_ID"] = explode("|", $IM["VALUE"])[3];
                if (file_exists("users.json") === true) {
                    $usersData = json_decode(file_get_contents("users.json"), true);
                }
                if ($usersData[$upd["fields"]["ORIGIN_ID"]] != NULL) {
                    $upd["fields"]["CONTACT_ID"] = $usersData[$upd["fields"]["ORIGIN_ID"]];
                }
                $sendUrl = $_POST["auth"]["client_endpoint"]."crm.lead.update?auth=".$_POST["auth"]["access_token"];
                $update = json_decode(send_request($sendUrl, "POST", $upd), true);
                break;
            }
        }
    }
} else if ($_POST["event"] == "ONCRMCONTACTADD") {
    $getUrl = $_POST["auth"]["client_endpoint"]."crm.contact.get?auth=".$_POST["auth"]["access_token"]."&id=".$_POST["data"]["FIELDS"]["ID"];
    $getContact = json_decode(send_request($getUrl), true);
    if ($getContact["result"]["ORIGINATOR_ID"] == "SMART_SENDER") {
        if (file_exists("users.json") === true) {
            $usersData = json_decode(file_get_contents("users.json"), true);
        }
        $usersData[$getContact["result"]["ORIGIN_ID"]] = $_POST["data"]["FIELDS"]["ID"];
        file_put_contents("users.json", json_encode($usersData));
        if ($getContact["result"]["LEAD_ID"] == NULL) {
            $getUrl = $_POST["auth"]["client_endpoint"]."crm.lead.list?auth=".$_POST["auth"]["access_token"];
            $getData["FILTER"]["ORIGINATOR_ID"] = "SMART_SENDER";
            $getData["FILTER"]["ORIGIN_ID"] = $getContact["result"]["ORIGIN_ID"];
            $getLead = json_decode(send_request($getUrl, "POST", $getData), true);
            if ($getLead["total"] >= 1) {
                $send["id"] = $getLead["result"][0]["ID"];
                $send["fields"]["CONTACT_ID"] = $_POST["data"]["FIELDS"]["ID"];
                $sendUrl = $_POST["auth"]["client_endpoint"]."crm.lead.update?auth=".$_POST["auth"]["access_token"];
                $upd = json_decode(send_request($sendUrl, "POST", $send), true);
            }
        }
    }
}

