<?php
require("config.php");

$data = json_decode(file_get_contents('php://input'), true); //fetch input json


if ($data == false) { //check the json
    exit("input_error");
}

function sendMessage($message, $chat_id)
{
    $message = urlencode($message);
    $url = "https://api.telegram.org/bot" . token . "/sendMessage?text=$message&chat_id=$chat_id&parse_mode=HTML";
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
        $surl = $matches[1];
    elseif (preg_match("/1[A-Za-z0-9-_]+/", $message_text, $matches))
        $surl = $matches[0];
    else {
        sendMessage("Can't fetch surl, please check the link!", $chat_id);
        exit;
    }

    if (preg_match("/提取码：?.? *(\w{4})/", $message_text, $matches))
        $pwd = $matches[1];
    elseif (preg_match("/@(\w{4})/", $message_text, $matches))
        $pwd = $matches[1];
    else
        $pwd = "";


    $header = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36"];
    $data = "surl=$surl&pwd=$pwd";
    $filelist = post(host . "?m=getlist", $data, $header);
    echo ($filelist);
    $filelist = json_decode($filelist, true);
    if ($filelist == null) $message = "Server error";
    elseif ($filelist["error"] == 0) {
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
                $randsk = urlencode($rootdata["randsk"]);
                $share_id = $rootdata["shareid"];
                $uk = $rootdata["uk"];
                $surl = "1" . $rootdata["surl_1"];

                $SVIP_BDUSS = SVIP_BDUSS;

                $data = "fs_id=$fs_id&time=$timestamp&sign=$sign&randsk=$randsk&shareid=$share_id&uk=$uk&app_id=250528&SVIP_BDUSS=$SVIP_BDUSS&surl=$surl";

                $header = ["User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.514.1919.810 Safari/537.36"];
                $fileinfo = post(host . "?m=getlink", $data, $header);
                echo ($fileinfo);
                $fileinfo = json_decode($fileinfo, true);
                if ($fileinfo == null) $message = "Server error";
                elseif ($fileinfo["error"] == 0) {
                    //success
                    $filename = $fileinfo["filedata"]["filename"];
                    $size = $fileinfo["filedata"]["size"];
                    $uploadtime = $fileinfo["filedata"]["uploadtime"];
                    $md5 = $fileinfo["filedata"]["md5"];
                    $directlink = $fileinfo["directlink"];
                    $message = "Filename: <b>$filename</b>\nSize: <b>$size</b>\nLink: <pre>$directlink</pre>";
                } else {
                    //wrong
                    $message = "Error happened! {$fileinfo["title"]}:{$fileinfo["message"]}";
                }
            } else  $message = "There no <b>file</b> in this link.";
        } else $message = "Too much file in the link! Now only support get one file at once.";
    } else $message = "Error happened! {$filelist["title"]}:{$filelist["message"]}";

    sendMessage($message, $chat_id);

    exit;
} else {
    $message = "Please send url in this format:\n<pre>https://pan.baidu.com/s/1otNXu2-z1cp1s_f8Gwp17w  提取码:aaaa</pre> or <pre>https://pan.baidu.com/s/1otNXu2-z1cp1s_f8Gwp17w@aaaa</pre>";
    sendMessage($message, $chat_id);
}
