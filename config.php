<?php

// Данные интеграции с bitrix24
$clientId = "";
$clientSecret = "";
$ssToken = "";

// Сервысные данные
$dir = dirname($_SERVER["PHP_SELF"]);
$url = ((!empty($_SERVER["HTTPS"])) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $dir;
$url = explode("?", $url);
$url = $url[0];

//
include('functions.php');