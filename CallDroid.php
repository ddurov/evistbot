<?php

require_once('vendor/autoload.php');
require_once('simplevk-master/autoload.php');

function createJson($one, $two) {

	$array = array("$one" => "$two");

	$json = json_encode($array);
	
	echo $json;

}

function logg($text) {

	file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt', $text."\n", FILE_APPEND);

}

use DigitalStar\vk_api\VK_api as vk_api;
use Krugozor\Database\Mysql\Mysql as Mysql;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

$data = json_decode(file_get_contents('php://input'));
$user_id = $data->object->user_id;
$response = $data->response;
$conv_name = $data->object->conv_name;
$codename = $data->object->codename;
$message_id = $data->object->conv_id;
$text = $data->object->text;

$code_user = $db->query("SELECT codename FROM android_data WHERE user_id = '$user_id' AND activated = '1'")->fetch_assoc()['codename'];

$v = '5.103';
$token = $db->query("SELECT token FROM android_data WHERE user_id = '$user_id' AND activated = '1'")->fetch_assoc()['token'];
$vk = vk_api::create($token, $v);

if ($response == "activating_user_bot" && !$code_user) {
	
	return createJson('answer', 'ok');
	
}
if ($response == "activating_user_bot" && $code_user) {
	
	return createJson('answer', 'already registred');
	
}
if ($response !== "activating_user_bot" && !$code_user) {
	
	return createJson('answer', 'no data');
	
}
if ($response == 'delete') {
	
	$req = $vk->request('messages.searchConversations', ['q' => $conv_name, 'count' => '1']);
	
	$peerId = $req['items'][0]['peer']['id'];
	
	$history = $vk->request('messages.getHistory', ['peer_id' => $peerId, 'count' => '200']);
	
	if ($vk->isAdmin($user_id, $peerId) == 'admin' || $vk->isAdmin($user_id, $peerId) == 'owner') {
		
		foreach($history['items'] as $k) {
			
			if ($k['reply_message']) {
				
				$id_message = $k['reply_message']['id'];
				
				$req = $vk->request('messages.delete', ["delete_for_all" => 1, "message_ids" => $id_message]);
				
				if (isset($req['error_msg'])) {
					
					$error_msg = $req['error_msg'];
					
					$json = createJson("errorvk", $error_msg);
					
					return $json;
					
				}
				
				$json = createJson('answer', 'ok');
				
				if (!$data->object->silent_mode) {
					
					$vk->sendMessage($peerId, 'Успешно.');
					
					return $json;
					
				} else {
					
					return $json;
					
				}
				
			} elseif ($k['fwd_messages']) {
				
				foreach ($k['fwd_messages'] as $l) {
					
					$id_message = $l['id'];
					$conv_id = $l['conversation_message_id'];
					
					$req = $vk->request('messages.delete', ["delete_for_all" => 1, "message_ids" => $id_message]);
					
				}
				if ($vk->request('messages.getByConversationMessageId', ['peer_id' => $peerId, 'conversation_message_ids' => $conv_id])['count'] !== '0') {
					
					if (!isset($req['error_msg'])) {
						
						$json = createJson('answer', 'ok');
						
						if (!$data->object->silent_mode) {
							
							$vk->sendMessage($peerId, 'Успешно.');
							
							return $json;
							
						} else {
							
							return $json;
							
						}
						
					} else {
						
						$error_msg = $req['error_msg'];
						
						$json = createJson("errorvk", $error_msg);
						
						return $json;
						
					}
					
				} else {
					
					$json = createJson('error', 'message_in_chat_not_found');
					
					return $json;
					
				}
				
			} else {
				
				$id_message = $k['id'];
				
				$req = $vk->request('messages.delete', ["delete_for_all" => 1, "message_ids" => $id_message]);
				
				if (isset($req['error_msg'])) {
					
					$error_msg = $req['error_msg'];
					
					$json = createJson("errorvk", $error_msg);
					
					return $json;
					
				}
				
				$json = createJson('answer', 'ok');
				
				return $json;
				
				if (!$data->object->silent_mode) {
					
					$vk->sendMessage($peerId, 'Успешно.');
					
				}
				
			}
			
		}
		
	} else {
		
		$json = createJson('error', 'android_not_admin_or_owner');
		
		return $json;
		
	}
	
}
if ($response == 'activate_peer') {
	
	$req = $vk->request('messages.searchConversations', ['q' => $conv_name, 'count' => '1']);
	
	if (isset($req['error_msg'])) {
		
		$error_msg = $req['error_msg'];
		
		createJson("errorvk", $error_msg);
		
	}
	
	$peerId = $req['items'][0]['peer']['id'];
	$title = $req['items'][0]['chat_settings']['title'];
	
	if ($conv_name == $title && $codename == $code_user) {
		
		createJson('answer', 'ok');
		
		$vk->sendMessage($peerId, 'Подключено!');
		
		$check_peer = $db->query("SELECT * FROM android_peers WHERE c_name = '$conv_name'")->getNumRows();
		
		if (!$check_peer) {
			
			$db->query("INSERT INTO android_peers SET user_id = '$user_id', c_name = '$conv_name'");
			
		} else {
			
			$db->query("UPDATE android_peers SET user_id = '$user_id' WHERE c_name = '$conv_name'");
			
		}
		
	} else {
		
		echo 'Возникла ошибка. Проверьте настройки Андроида.';
		
	}
	
}
if ($response == 'return_user') {
	
	$req = $vk->request('messages.searchConversations', ['q' => $conv_name, 'count' => '1']);
	
	$peerId = $req['items'][0]['peer']['id'];
	
	$rid = $data->object->ruid;
	
	if ($vk->request('friends.areFriends', ['user_ids' => $rid])[0]["friend_status"] == '3') {
		
		if ($vk->isAdmin($user_id, $peerId) == 'admin' || $vk->isAdmin($user_id, $peerId) == 'owner') {
			
			$chatId = $peerId - 2e9;
			
			$req = $vk->request("messages.addChatUser", ['user_id' => $rid, 'chat_id' => $chatId, "visible_messages_count" => '1000']);
			
			if (isset($req['error_msg'])) {
				
				$error_msg = $req['error_msg'];
				
				createJson("errorvk", $error_msg);
				
			}
			
			$jsonb = createJson('answer', 'ok');
			
			return $jsonb;
			
		} else {
			
			$json = createJson('error', 'android_not_admin_or_owner');
			
			return $json;
			
		}
		
	} else {
		
		$json = createJson('error', 'rid_not_friend');
		
		return $json;
		
	}
	
}