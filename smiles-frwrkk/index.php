<?

require_once('../simplevk-master/autoload.php');
require_once('../vendor/autoload.php');
require_once("handler_smiles_emo.php");

use DigitalStar\vk_api\VK_api as vk_api;
use DigitalStar\vk_api\VkApiException;
use DigitalStar\vk_api\Execute;
use Krugozor\Database\Mysql\Mysql as Mysql;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

const VK_KEY = "ТОКЕН ГРУППЫ";
const VERSION = "5.122";

$vk = vk_api::create(VK_KEY, VERSION)->setConfirm("СТРОКА ПОДТВЕРЖДЕНИЯ ИЗ CALLBACK API");
$data = json_decode(file_get_contents('php://input')); 

$vk->sendOK();

$peer_id    = $data->object->message->peer_id;
$from_id    = $data->object->message->from_id;
$message    = $data->object->message->text;
$chat_id    = $peer_id - 2e9;
function logg($text) {

    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt', "[".date('d.m.y H:i:s')."] Текст: " .$text."\n", FILE_APPEND);

}

function getSmilesCodes($smiles) {
    
    for ($pos = 0; $pos < mb_strlen($smiles); $pos++) {
        
        $smile = mb_substr($smiles, $pos, 1, 'UTF-8');
        $obrd = Smiles($smile);
        $array = array($pos+1 => $obrd);
        
    }
    return key($array);
    
}

$smiles = preg_replace('/[а-яА-Яa-zA-Z0-9\s\.,?!ё\/\/\-]/u', '', $message);
$smiles = getSmilesCodes($smiles);

if ($data->type == 'message_new') {

    $check = $vk->request('messages.getConversationMembers', ['peer_id' => $peer_id]);
    
	if (!$check['error_msg']) {
    
        $sms_min = $db->query("SELECT smsmin FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['smsmin'];
        
        if (mb_strlen($message) >= 840  && $smiles < 250) {
    
	    	$vk->request('messages.send', ['peer_id' => $peer_id, 'message' => "Обнаружен текст от @id$from_id (пользователя), превышающий допустимую отметку.".PHP_EOL."В целях безопасности я удалю пользователя.", 'disable_mentions' => '1']);
	    	$vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
	    } elseif ($sms_min > '19') {
	        
	        $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => "Высокий поток сообщений от @id$from_id (пользователя)!".PHP_EOL."В целях безопасности я удалю пользователя.", 'disable_mentions' => '1']);
	    	$vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
	        
        } elseif ($smiles > 250 || mb_strlen($message) >= 840) {
            
            $obr_msg = substr_replace($message, '', 400);
            
            $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'Найдено подозрение на рейд атаку, исключаю пользователя..']);
	    	$vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
	    	
	    	if ($from_id > 1) {
	    	    
	    	    $vk->request('messages.send', ['peer_id' => 'айди беседы для логов', 'message' => "<WARNING> @id$from_id (Пользователь) отправлял подозрительные сообщения ($smiles > 250). Текст сообщения: $obr_msg.", 'disable_mentions' => '1']);    
	    	    
	    	} else {
	    	    
	    	    $gid = mb_substr($from_id, 1);
	    	    
	    	    $vk->request('messages.send', ['peer_id' => 'айди беседы для логов', 'message' => "<WARNING> @club$gid (Группа) отправляла подозрительные сообщения ($smiles > 250). Текст сообщения: $obr_msg.", 'disable_mentions' => '1']); 
	    	    
	    	}
            
        } elseif (preg_match("~(?:[\p{M}]{1})([\p{M}])+?~uis", $onlyemoji)) {
            
            $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => "Обнаружен опасный символ (zalgo text) от @id$from_id (пользователя).", 'disable_mentions' => '1']);
	    	$vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
            
        }
        
	}
    
}