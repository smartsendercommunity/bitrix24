<?php

ini_set('max_execution_time', '1700');
set_time_limit(1700);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: application/json');
header('Content-Type: application/json; charset=utf-8');

http_response_code(200);

include('config.php');

//--------------

// Проверка наличия всех обезательных полей
if ($_GET["ssId"] == NULL) {
    $result["state"] = false;
    $result["message"]["account"] = "ssId is missing";
    http_response_code(422);
    echo json_encode($result);
    exit;
}


// Подготовка данных и отправка в Smart Sender
if (is_array($_GET["addTags"]) === true) {
    foreach ($_GET["addTags"] as $addTags) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/tags?page=1&limitation=20&term=".$addTags, $ssToken), true);
        if (is_array($tagsData["collection"]) === true) {
            foreach ($tagsData["collection"] as $tagsSS) {
                if ($tagsSS["name"] == $addTags) {
                    $result["addTags"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"]."/tags/".$tagsSS["id"], $ssToken, "POST"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["delTags"]) === true) {
    foreach ($_GET["delTags"] as $delTags) {
        $tagsData = json_decode(send_bearer("https://api.smartsender.com/v1/tags?page=1&limitation=20&term=".$delTags, $ssToken), true);
        if (is_array($tagsData["collection"]) === true) {
            foreach ($tagsData["collection"] as $tagsSS) {
                if ($tagsSS["name"] == $delTags) {
                    $result["delTags"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"]."/tags/".$tagsSS["id"], $ssToken, "DELETE"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["addFunnels"]) === true) {
    foreach ($_GET["addFunnels"] as $addFunnels) {
        $funnelsData = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?page=1&limitation=20&term=".$addFunnels, $ssToken), true);
        if (is_array($funnelsData["collection"]) === true) {
            foreach ($funnelsData["collection"] as $funnelsSS) {
                if ($funnelsSS["name"] == $addFunnels) {
                    $result["addFunnels"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"]."/funnels/".$funnelsSS["serviceKey"], $ssToken, "POST"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["delFunnels"]) === true) {
    foreach ($_GET["delFunnels"] as $delFunnels) {
        $funnelsData = json_decode(send_bearer("https://api.smartsender.com/v1/funnels?page=1&limitation=20&term=".$delFunnels, $ssToken), true);
        if (is_array($funnelsData["collection"]) === true) {
            foreach ($funnelsData["collection"] as $funnelsSS) {
                if ($funnelsSS["name"] == $delFunnels) {
                    $result["delFunnels"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"]."/funnels/".$funnelsSS["serviceKey"], $ssToken, "DELETE"), true);
                    break;
                }
            }
        }
    }
}
if (is_array($_GET["triggers"]) === true) {
    foreach ($_GET["triggers"] as $triggers) {
        $result["triggers"][] = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"]."/fire?name=".$triggers, $ssToken, "POST"), true);
    }
}
if (is_array($_GET["variables"]) === true) {
    foreach ($_GET["variables"] as $varKey => $varValue) {
        $sendVar["values"][$varKey] = $varValue;
    }
    $updateUser = json_decode(send_bearer("https://api.smartsender.com/v1/contacts/".$_GET["ssId"], $ssToken, "PUT", $sendVar), true);
    $result["send"] = $sendVar;
    $result["update"] = $updateUser;
}
echo json_encode($result);