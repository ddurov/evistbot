<?php

require_once('vendor/autoload.php');
require_once('simplevk-master/autoload.php');

use Krugozor\Database\Mysql\Mysql as Mysql;
use DigitalStar\vk_api\VK_api as vk_api;
use DigitalStar\vk_api\VkApiException;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

$version = "5.92";
$vk_token = $_POST['token'];
$codename = $_POST['codename'];
$id = file_get_contents("https://api.vk.com/method/users.get?access_token=" . $vk_token . "&v=5.92");
$json = json_decode($id);
$id = $json->response[0]->id;

if (isset($vk_token)) {

    $vk = vk_api::create($vk_token, $version);
    
    $ug = $vk->request('users.get', ['user_ids' => '1']);
    
    if (isset($ug['error'])) {
    
        $error_message = $ug['error']['error_msg'];
        
        echo '<h1>Произошла ошиб очка!</h1>';
        echo '<p>' . 'Код ошибки: ' . $ug['error']['error_code'] . ', сообщение ошибки: ' . $error_message . '</p>';
        
        return;
    
    }
    
    if (mb_strlen($codename) > '8' && mb_strlen($codename) <= '20') {
        
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/bot/CallDroid.php";
    
        $codemsg = '+андроид ' . htmlspecialchars($codename) . ' ' . $actual_link;
    	$vk->sendMessage(-191095367, $codemsg);
    	echo 'ok';
        
        $check = $db->query("SELECT token FROM android_data WHERE user_id = '$id'")->getNumRows();
        
        if (!$check) {
    
    	    $db->query("INSERT INTO android_data (user_id, codename, token) VALUES ('$id', '$codename', '$vk_token')");
    	    
        } else {
            
            $db->query("UPDATE android_data SET codename = '$codename', token = '$vk_token' WHERE user_id = '$id'");
            
        }
    	return;
    	
    
    } else {
    
    	echo 'Требуется > 8 и <= (меньше или равно) 20 символов для кодового имени';
    
    }
    
}

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<form action="" method="post">
<table>
<tr>
	<td>Токен юзера</td>
	<td><input type="text" name="token" value="" placeholder="Токен" style="width: 400px"></td>
</tr>
<tr>
	<td>Кодовое имя</td>
	<td><input type="text" name="codename" value="" placeholder="Придумайте кодовое имя" style="width: 400px"></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" value="Добавить"></td>
</tr>
</table>
</form>
</body></html>