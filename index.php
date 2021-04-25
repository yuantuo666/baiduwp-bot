<?php
require("config.php");

$data = json_decode(file_get_contents('php://input'), true); //fetch input json


if ($data == false) { //check the json
    exit("input_error");
}

function sendmessage($method, $text, $chat_id, $attach = "")
{
    $text = urlencode($text);
    $url = "https://api.telegram.org/bot" . token . "/$method?text=$text&chat_id=$chat_id" . $attach;
    $header = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36"];
    return get($url, $header); //send message
}

function setCurl(&$ch, array $header)
{
    $a = curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $b = curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $c = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $d = curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    return ($a && $b && $c && $d);
}
function post(string $url, $data, array $header)
{
    $ch = curl_init($url);
    setCurl($ch, $header);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
function get(string $url, array $header)
{
    $ch = curl_init($url);
    setCurl($ch, $header);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$chat_id = $data['message']['chat']['id'];
$message_text = $data['message']['text'];

// $message_text = $_GET["url"];
// Format:" https://pan.baidu.com/s/1otNXu2-z1cp1s_f8Gwp17w  提取码:aaaa" or "https://pan.baidu.com/s/1otNXu2-z1cp1s_f8Gwp17w@aaaa"
if (preg_match("/pan.baidu.com/", $message_text)) {
    if (preg_match("/surl=([A-Za-z0-9-_]+)/", $message_text, $matches))
        $surl = $matches[0];
    elseif (preg_match("/1[A-Za-z0-9-_]+/", $message_text, $matches))
        $surl = $matches[0];
    else
        exit("can't fecth surl");

    if (preg_match("/提取码.? *(\w{4})/", $message_text, $matches))
        $pwd = substr($matches[0], 10);
    elseif (preg_match("/@(\w{4})/", $message_text, $matches))
        $pwd =  substr($matches[0], 1);
    else
        $pwd = "";


    $header = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36"];
    $data = "surl=$surl&pwd=$pwd";
    $filelist = post("https://imwcr.cn/api/bdwp-core/api.php?m=getlist", $data, $header);
    echo ($filelist);
    $filelist = json_decode($filelist, true);
    if ($filelist["error"] == 0) {
        //success
        if ($filelist["filenum"] == 1) {
            //now only support get one file
            $rootdata = $filelist["rootdata"];
            $fileinfo = $filelist["filedata"][0];
            if ($fileinfo["isdir"] == 0) {
                //make sure it is a file
                $fs_id = $fileinfo["fs_id"];

                $timestamp = $rootdata["timestamp"];
                $sign = $rootdata["sign"];
                $randsk = $rootdata["randsk"];
                $share_id = $rootdata["shareid"];
                $uk = $rootdata["uk"];
                $surl = "1" . $rootdata["surl_1"];
                $data="fs_id=$fs_id&timestamp=$timestamp";
                //unfinished




            }
        } else $message = "Too much file in the link! Now only support get one file at once.";
    } else $message = "Unkown error happened~";

    // $text = get("https://imwcr.cn/api/2019-nCoV/index.php?method=robottext", $header);
    // $imgsrc = get("https://imwcr.cn/api/2019-nCoV/index.php?method=imgsrc", $header);

    // $text="sadasd";
    // $text = urlencode($text);
    // sendmessage("sendphoto", "sss", $chat_id, "&caption=$text");

    exit;
}
