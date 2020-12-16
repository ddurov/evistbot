<?php

require_once('simplevk-master/autoload.php');
require_once('vendor/autoload.php');

use DigitalStar\vk_api\VK_api as vk_api;
use DigitalStar\vk_api\VkApiException;
use DigitalStar\vk_api\Execute;
use Krugozor\Database\Mysql\Mysql as Mysql;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

const VK_KEY = "ТОКЕН ГРУППЫ";
const VERSION = "5.103";

const BT_DENY = "❌";

$vk = vk_api::create(VK_KEY, VERSION);
$vk = new Execute($vk);
$data = json_decode(file_get_contents('php://input')); 

$vk->sendOK();

function logg($text) {

    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt', $text."\n", FILE_APPEND);

}

function postResponse ($url, $data) {

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (!isset($response)) {
        
        return null;
        
    }
    return $response;
    
}

$db->query("UPDATE users SET smsmin = '0'");

$check_ban = $db->query("SELECT vk_id, peer_id, timeban FROM bans");
$check_mute = $db->query("SELECT vk_id, peer_id, timemute FROM mutes");

while ($mute_result = $check_mute->fetch_assoc()) {
    
    $defi = $mute_result['timemute'];
    
    if ($defi !== '0') {
        
        $db->query("UPDATE mutes SET timemute = '$defi'-1");
        
    } elseif ($defi == '0') {
        
        $peerid = $mute_result['peer_id'];
        $vkid = $mute_result['vk_id'];
        
        $banfl = $vk->request("users.get", ['user_ids' => $vkid, 'name_case' => 'gen']);
		$name = $banfl[0]['first_name'];
		$pname = $banfl[0]['last_name'];

        $vk->request('messages.send', ['peer_id' => $peerid, "message" => "Срок мута @id$vkid ($name $pname) истек. Теперь данный пользователь может дальше общаться."]);
        
        $db->query("DELETE FROM mutes WHERE timemute = '0' AND vk_id = '$vkid' AND peer_id = '$peerid'");
        
    }
    
}

while ($row = $check_ban->fetch_assoc()) {

    $def = $row['timeban'];
    
    if ($def !== '0') {
        
        $db->query("UPDATE bans SET timeban = '$def'-1");
        
    } elseif ($def == '0') {
        
        $peerid = $row['peer_id'];
        $vkid = $row['vk_id'];
        
        $banfl = $vk->request("users.get", ['user_ids' => $vkid, 'name_case' => 'gen']);
		$name = $banfl[0]['first_name'];
		$pname = $banfl[0]['last_name'];
		
		$chat_android = $db->query("SELECT android FROM peers WHERE peer_id = '$peerid'")->fetch_assoc()['android'];
        
        if ($chat_android == '0') {
        
            $vk->request('messages.send', ['peer_id' => $peerid, "message" => "Срок бана @id$vkid ($name $pname) истек. Теперь не только модераторы с 4+ рангом могут добавить его обратно."]);
            
        } else {
            
            $vk->request('messages.send', ['peer_id' => $peerid, "message" => "Срок бана @id$vkid ($name $pname) истек. Андроид сейчас попытается вернуть пользователя."]);
            
            $db->query("DELETE FROM bans WHERE timeban = '0' AND vk_id = '$vkid' AND peer_id = '$peerid'");
        
            $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$chat_android'")->fetch_assoc()['address'];
            
            $title = $vk->request('messages.getConversationsById', ['peer_ids' => $peerid])['items'][0]['chat_settings']['title'];
            
            $dat = array("response" => "return_user", "object" => array("user_id" => $chat_android, "ruid" => $vkid, "peerId" => $peerid, 'conv_name' => "$title"));
            
            $json = json_encode($dat, JSON_UNESCAPED_UNICODE);
            
            $out = postResponse($getAddress, $json);
            
            $out = json_decode($out, true);
            
            if ($out['errorvk']) {
                
                if ($out['errorvk'] == 'Access denied: user already in') {
                    
                    $vk->sendMessage($peerid, BT_DENY . " Андроид вернул следующий отчет: Невозможно добавить пользователя так как он уже есть или он вышел с беседы.");
                    
                }
                 
            }
            if ($out['error']) {
                
                if ($out['error'] == 'rid_not_friend') {
                    
                    $vk->sendMessage($peerid, BT_DENY . " Андроид вернул следующий отчет: Невозможно добавить пользователя так как он не является моим другом.");
                    
                }
                
            }
            if ($out['answer'] == 'ok') {
                
                return;
                
            }
            
        }
        
    }
    
}