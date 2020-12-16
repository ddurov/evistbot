<?php

require_once('simplevk-master/autoload.php');
require_once('vendor/autoload.php');

use DigitalStar\vk_api\VK_api as vk_api;
use DigitalStar\vk_api\VkApiException;
use DigitalStar\vk_api\Execute;
use Krugozor\Database\Mysql\Mysql as Mysql;
$db = Mysql::create("localhost", "юзер базы", "пароль базы")->setDatabaseName("имя базы")->setCharset("utf8mb4");

const VK_KEY = "ТОКЕН ГРУППЫ";
const VERSION = "5.122";

const BT_DEN = '❌';
const BT_SUC = '✅';
const BT_WARN = '❗';

$vk = vk_api::create(VK_KEY, VERSION)->setConfirm('СТРОКА ПОДТВЕРЖДЕНИЯ ИЗ CALLBACK API');
$vk = new Execute($vk);
$data = json_decode(file_get_contents('php://input'));

if ($data->secret == 'СЕКРЕТНОЕ СЛОВО ИЗ НАСТРОЕК CALLBACK API') {

    $vk->sendOK();
    
} else {

    header("Location: https://www.youtube.com/watch?v=ELBVeRDflV0");
    
}

$peer_id     = $data->object->message->peer_id;
$from_id     = $data->object->message->from_id;
$message     = $data->object->message->text;
$invite      = $data->object->message->action->type;
$check       = $data->object->message->action->member_id;
$from_id_fwd = $data->object->message->reply_message->from_id;
$per_text    = $data->object->message->reply_message->text;
$chat_id     = $peer_id - 2000000000;
$original    = $message;
$message     = mb_strtolower($message);
$cmd         = explode(" ", $message);
$cmdorig     = explode(" ", $original);
$cmdobr      = explode(" ", $message, 2);
$cmdobrorig  = explode(" ", $original, 2);

if (isset($data->object->message->payload)) {

    $payload = json_decode($data->object->message->payload, True);

} else {

    $payload = null;

}

$payload = $payload['command'];
// ====== *************** ============

// ФУНКЦИИ

function logg($text) {

    global $chat_id;

    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt', "[".date('d.m.y H:i:s')."] [ID: $chat_id] Текст: " .$text."\n", FILE_APPEND);

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

// ФУНКЦИИ

if ($data->type == 'message_new') {
    
    if ($from_id > 0) {

        if (mb_substr($message,0,8) == '/репорт ') {
    
            $reporttext = mb_substr($original ,8);
    
            $banreport = $db->query("SELECT * FROM reportban WHERE vk_id = '$from_id'")->getNumRows();
    
            if (!$banreport) {
    
                if ($reporttext !== '') {
    
                    $maxlenghtwarn = mb_strlen($reporttext);
    
                    if ($maxlenghtwarn < 200) {
    
                        $answer = $db->query("SELECT answer FROM report WHERE ask = '$reporttext'")->fetch_assoc()['answer'];
                        $getanswer = $db->query("SELECT answer FROM report WHERE ask = '$reporttext'")->getNumRows();
    
                        if (!$getanswer && $answer !== 'none') {
    
                            $db->query("INSERT INTO `report` VALUES (NULL, '$from_id', '$peer_id', '$reporttext', 'none')");
    
                            $report_id = $db->query("SELECT id FROM report WHERE ask = '$reporttext'")->fetch_assoc()['id'];
    
                            $vk->sendMessage($peer_id, 'Заявка в репорт #' . $report_id . ' успешно создана! Ожидайте пока Коннор ответит вам!');
    
                            $report = $vk->request('users.get', ['user_ids' => $from_id, 'name_case' => 'gen']);
                            $report_name = $report[0]['first_name'];
                            $report_pname = $report[0]['last_name'];
    
                            $vk->sendMessage(2000000317, 'Новый репорт от @id' . $from_id . ' (' . $report_name . ' ' . $report_pname . ')' . ' c номером ' . $report_id . ', вот его текст: ' . $reporttext . PHP_EOL . 'Для ответа используйте: /ответ {номер репорта} {ваш ответ}');
    
                        } elseif ($getanswer && $answer !== 'none') {
    
                            $vk->sendMessage($peer_id, 'Система автоматически нашла ответ на ваш репорт -&#2;-> ' . $answer);
    
                        } elseif ($getanswer && $answer == 'none') {
    
                            $vk->sendMessage($peer_id, 'Ждите, на ваш репорт в ближайшее время ответят, не нужно флудить одним и тем же, иначе вам выдадут временный бан репорта!');
    
                        }
    
                    } else {
    
                        $vk->sendMessage($peer_id, BT_WARN . 'Ваш вопрос содержит больше 200 символов.');
    
                    }
    
                } else {
    
                    $vk->sendMessage($peer_id, 'Не обнаружен текст репорта.');
    
                }
    
            } else {
    
                $vk->sendMessage($peer_id, 'К нашему большому сожалению Вам был заблокирован Репорт в связи с тем, что вы вероятно оффтопили в репорт! Блокировка длится 1 день, после чего, вас разблокирует.');
    
            }
    
        }
    
        $sql = $db->query("SELECT * FROM gban WHERE vk_id = $from_id")->getNumRows();
    
        if ($sql) {
    
            return;
    
        } else {
    
            $checkpeerid = $db->query("SELECT peer_id FROM peers WHERE peer_id = '$peer_id'")->getNumRows();
    
            if ($checkpeerid) {
    
                try {

                    $buttons = explode(" ", $payload);

                    if ($buttons[0] == 'кик') {

                        $rang = $db->query("SELECT kick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['kick'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {

                            $id = $buttons[1];

                            if ($userrang > $db->query("SELECT rang FROM users WHERE vk_id = '$id' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {

                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $id]);

                            } elseif ($userrang <= $db->query("SELECT rang FROM users WHERE vk_id = '$id' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {

                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'Нельзя исключить человека чей ранг выше или равен вашему']);

                            }

                        } else {

                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Исключение' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }

                        }

                    }
    
                    if ($message || $data->object->message->attachments[0]) {
    
                        $check_mute = $db->query("SELECT * FROM mutes WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->getNumRows();
                        
                        if ($check_mute) {
    
                            if ($db->query("SELECT silent_mute FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mute'] == '1') {
    
                                $getAndroid = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];
    
                                if ($getAndroid !== '0') {
    
                                    $conv_id = $data->object->message->conversation_message_id;
    
                                    $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$getAndroid'")->fetch_assoc()['address'];
                                    $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$getAndroid'")->fetch_assoc()['codename'];
                                    
                                    $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
    
                                    foreach ($getId['items'] as $getTitle) {
    
                                        $title = $getTitle['chat_settings']['title'];
    
                                    }
    
                                    if ($db->query("SELECT silent_mode FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mode'] == '0') {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id, "conv_name" => "$title", "silent_mode" => false));
    
                                    } else {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id, "conv_name" => "$title", "silent_mode" => true));
    
                                    }
    
                                    $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
    
                                    postResponse($getAddress, $dat);
    
                                }
    
                            } else {
    
                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'Ой-ой-ой, кто это у нас открыл свой рот в муте? Теперь ты будешь наказан. Даже если тебя сюда кто то вернет, ты все равно будешь молчать.']);
    
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                            }
                            
                        }
    
                        $sms_all_conv_q = $db->query("SELECT sms_day, sms_all, smsmin FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
    
                        while ($sms_all_conv = $sms_all_conv_q->fetch_assoc()) {
    
                            $sms_day = $sms_all_conv['sms_day'];
                            $sms_all = $sms_all_conv['sms_all'];
                            $sms_min = $sms_all_conv['smsmin'];
    
                        }
    
                        $db->query("UPDATE users SET sms_all = '$sms_all'+1 WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
                        $db->query("UPDATE users SET sms_day = '$sms_day'+1 WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
                        $db->query("UPDATE users SET smsmin  = '$sms_min'+1 WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
    
                        $status = $db->query("SELECT statusvk FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['statusvk'];
    
                        if ($status == 'nact') {
    
                            $check = $vk->request('messages.getConversationMembers', ['peer_id' => $peer_id]);
    
                            if (!$check['error_msg']) {
    
                                foreach ($check['items'] as $key) {
    
                                    $member = $key['member_id'];
    
                                    if ($member == '-191095367') {
    
                                        if ($key['is_admin']) {
    
                                            $statusvk = $db->query("SELECT statusvk FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['statusvk'];
    
                                            if ($statusvk !== 'act') {
    
                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'Спасибо что назначили меня администратором! Я могу выдать ранги всем администраторам.']);
                                                $db->query("UPDATE peers SET statusvk = 'act' WHERE peer_id = '$peer_id'");
    
                                            }
    
                                        }
    
                                    }
    
                                    $admin = $vk->isAdmin($member, $peer_id);
    
                                    $check1 = $db->query("SELECT vk_id FROM users WHERE vk_id = '$member' AND peer_id = '$peer_id'")->getNumRows();
    
                                    if (!$check1 && $admin !== 'owner' && $admin !== 'admin' && !preg_match('/-/', $member)) {
    
                                        $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member', '0', '$peer_id')");
    
                                    } elseif (!$check1 && $admin == 'admin' && !preg_match('/-/', $member)) {
    
                                        $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member', '4', '$peer_id')");
    
                                    } elseif (!$check1 && $admin == 'owner' && !preg_match('/-/', $member)) {
    
                                        $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member', '5', '$peer_id')");
    
                                    }
                                    if ($check1 && $admin == 'admin' && $userrang == '0') {
    
                                        $db->query("UPDATE users SET rang = '4' WHERE vk_id = '$member' AND peer_id = '$peer_id'");
    
                                    } elseif ($check1 && $admin == 'owner' && $userrang == '0') {
    
                                        $db->query("UPDATE users SET rang = '5' WHERE vk_id = '$member' AND peer_id = '$peer_id'");
    
                                    }
    
                                }
    
                            }
    
                        }
    
                    }
                    // АДМИН - КОМАНДЫ (Не беседы)
                    if (mb_substr($message,0,9) == '!writein ') {
    
                        $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
                        $checkadmin = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($checkagent || $checkadmin) {
    
                            $agent_id = $db->query("SELECT id FROM agents WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                            $admin_id = $db->query("SELECT id FROM admin WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                            $obj = mb_substr($original ,9);
                            $obl = explode(" ", $obj, 2);
    
                            $idpeer = $obl[0];
                            $ansmsg = $obl[1];
    
                            $vk->sendMessage($peer_id, 'Отправлено');
    
                            if ($checkagent) {
    
                                $vk->sendMessage(2000000000 + $idpeer, 'Коннор#' . $agent_id . ' написал Вам: ' . $ansmsg);
    
                            } else {
    
                                $vk->sendMessage(2000000000 + $idpeer, 'Администратор#' . $admin_id . ' написал Вам: ' . $ansmsg);
    
                            }
    
                        }
    
                    }
                    if (mb_substr($message,0,4) == '!pm ') {
    
                        $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                        $checkadmin = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($checkagent || $checkadmin) {
    
                            $agent_id = $db->query("SELECT id FROM agents WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                            $admin_id = $db->query("SELECT id FROM admin WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                            $obj = mb_substr($original ,4);
                            $obl = explode(" ", $obj, 2);
    
                            $idpeer = $obl[0];
                            $idpeer = explode("|", mb_substr($idpeer ,3))[0];
                            $ansmsg = $obl[1];
    
                            if ($checkagent) {
    
                                $vk->sendMessage($idpeer, 'Коннор#' . $agent_id . ' написал Вам: ' . $ansmsg);
    
                            } else {
    
                                $vk->sendMessage($idpeer, 'Администратор#' . $admin_id . ' написал Вам: ' . $ansmsg);
    
                            }
    
                            $vk->sendMessage($peer_id, 'Отправлено');
    
                        }
    
                    }
                    /*if (mb_substr($message,0,12) == '/банрепорта ') {
    
                        $admsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($admsql) {
    
                            $repban_id = mb_substr($message ,12);
                            $repban_id = explode("|", mb_substr($repban_id ,3))[0];
    
                            if ($from_id_fwd) {
    
                                return;
    
                            } else {
    
                                if ($repban_id) {
    
                                    $vk->sendMessage($peer_id, '@id' . $repban_id . ' (Пользователю) было выдано ограничение писать в репорт на 1 день');
                                    $db->query("INSERT INTO reportban (id, vk_id) VALUES (NULL, '$repban_id')");
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Не указан ид!');
    
                                }
    
                            }
    
                        }
    
                    }*/
                    if ($cmd[0] == 'гбан') {
    
                        $admsqlcheck = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id_fwd'")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($admsql) {
    
                            $globan_id = $cmd[1];
                            $globan_id = explode("|", mb_substr($globan_id ,3))[0];
    
                            if ($from_id_fwd) {
    
                                return;
    
                            }
    
                            if ($globan_id) {
    
                                if ($admsqlcheck) {
    
                                    return;
    
                                } else {
    
                                    $checkban = $db->query("SELECT * FROM gban WHERE vk_id = $globan_id")->getNumRows();
    
                                    if ($checkban) {
    
                                        $vk->sendMessage($peer_id, BT_WARN . 'Пользователь уже есть в списке глобана');
    
                                    } else {
    
                                        $db->query("INSERT INTO gban (vk_id) VALUES ($globan_id)");
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' @id' . $globan_id . ' (Пользователь) был добавлен в глобальный бан бота.');
    
                                    }
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, BT_WARN . 'Не указан ид');
    
                            }
    
                        } else {
    
                            return;
    
                        }
    
                    }
                    if ($cmd[0] == 'гразбан') {
    
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = $from_id")->getNumRows();
    
                        if ($admsql) {
    
                            $glouban_id = $cmd[1];
                            $glouban_id = explode("|", mb_substr($glouban_id ,3))[0];
    
                            if ($glouban_id && !$from_id_fwd) {
    
                                $chcksql = $db->query("SELECT * FROM gban WHERE vk_id = $glouban_id")->getNumRows();
    
                                if ($chcksql) {
    
                                    $db->query("DELETE FROM gban WHERE vk_id = $glouban_id");
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' @id' . $glouban_id . ' (Пользователь) был успешно вынесен из глобального бана.');
    
                                    $vk->sendMessage($glouban_id, BT_SUC . ' Вы были успешно вынесены из глобального бана.' . PHP_EOL . 'В последующем разбан будет стоить 50 коинов, дальше 100 коинов и так далее (т.е каждый раз будет прибавлятся 50).' . PHP_EOL . 'Помните: вы попали туда не просто так.');
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_DEN . ' Пользователя и так нету в списке');
    
                                }
    
                            } elseif (!$glouban_id && !$from_id_fwd) {
    
                                $vk->sendMessage($peer_id, BT_WARN . 'Не указан ид');
    
                            }
    
                        } else {
    
                            return;
    
                        }
    
                    }
                    if (mb_substr($message,0,7) == '/ответ ') {
    
                        $obj = mb_substr($original ,7);
                        $obl = explode(" ", $obj, 2);
    
                        $idrepo = $obl[0];
                        $ansrep = $obl[1];
    
                        $peer_id_repo = $db->query("SELECT peer_id FROM report WHERE id = '$idrepo'")->fetch_assoc()['peer_id'];
                        $reportask = $db->query("SELECT ask FROM report WHERE id = '$idrepo'")->fetch_assoc()['ask'];
                        $checkanswer = $db->query("SELECT answer FROM report WHERE id = '$idrepo'")->fetch_assoc()['answer'];
                        $numagent = $db->query("SELECT id FROM agents WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
                        $admnum = $db->query("SELECT id FROM admin WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                        if ($idrepo !== '' && $ansrep !== '') {
    
                            $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                            $checkadmin = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                            if ($checkadmin || $agentsql) {
    
                                if ($checkanswer == 'none') {
    
                                    $db->query("UPDATE report SET answer = '$ansrep' WHERE id = '$idrepo'");
    
                                    $vk->sendMessage($peer_id, 'Ответ отправлен');
    
                                    if ($agentsql && $peer_id_repo < 2000000000) {
    
                                        $vk->sendMessage($peer_id_repo, '@id' . $peer_id_repo . ' (Ваш) репорт: ' . $reportask . PHP_EOL . 'Ответ Коннора#' . $numagent . ': ' . $ansrep, ['disable_mentions' => '1']);
    
                                    } elseif ($checkadmin && $peer_id_repo < 2000000000) {
    
                                        $vk->sendMessage($peer_id_repo, '@id' . $peer_id_repo . ' (Ваш) репорт: ' . $reportask . PHP_EOL . 'Ответ Администратора#' . $admnum . ': ' . $ansrep, ['disable_mentions' => '1']);
    
                                    } elseif ($agentsql && $peer_id_repo > 2000000000) {
    
                                        $vk->sendMessage($peer_id_repo, 'Из вашей беседы пришел репорт: ' . $reportask . PHP_EOL . 'Ответ Коннора#' . $numagent . ': ' . $ansrep);
    
                                    } elseif ($checkadmin && $peer_id_repo > 2000000000) {
    
                                        $vk->sendMessage($peer_id_repo, 'Из вашей беседы пришел репорт: ' . $reportask . PHP_EOL . 'Ответ Администратора#' . $admnum . ': ' . $ansrep);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Ответ уже есть -> ' . $checkanswer);
    
                                }
    
                            }
    
                        }
    
                    }
                    if ($message == '/нрепорты') {
    
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = $from_id")->getNumRows();
                        $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
    
                        if ($checkagent || $admsql) {
    
                            $id_ask_rep_none = $db->query("SELECT id, ask FROM report WHERE answer = 'none'");
    
                            while ($row = $id_ask_rep_none->fetch_assoc()) {
    
                                $id_report_none .= $row['id'] . ' -> ' . $row['ask'] . PHP_EOL;
    
                            }
    
                            $vk->sendMessage($peer_id, $id_report_none);
    
                        }
    
                    }
                    if ($message == '/арепорты') {
    
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = $from_id")->getNumRows();
                        $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
    
                        if ($checkagent || $admsql) {
    
                            $arep = $db->query("SELECT id, vk_id, ask, answer FROM report");
    
                            while ($row = $arep->fetch_assoc()) {
    
                                $arepid = $vk->request('users.get', ['user_ids' => $row['vk_id']]);
                                $arepname = $arepid[0]['first_name'];
                                $areppname = $arepid[0]['last_name'];
    
                                $areport .= $row['id'] . ' -> ' . '@id' . $row['vk_id'] . ' (' . $arepname . ' ' . $areppname . ')' . ' -> ' . $row['ask'] . ' -> ' . $row['answer'] . PHP_EOL;
    
                            }
    
                            $vk->sendMessage($peer_id, $areport, ['disable_mentions' => '1']);
    
                        }
    
                    }
                    if ($message == 'гбаны') {
    
                        $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = $from_id")->getNumRows();
    
                        if ($admsql || $checkagent) {
    
                            $gbanslist = $db->query("SELECT * FROM gban")->getNumRows();
    
                            if ($gbanslist) {
    
                                $namesss = $db->query("SELECT * FROM gban");
    
                                while ($row = $namesss->fetch_assoc()) {
    
                                    $gbanid = $vk->request('users.get', ['user_ids' => $row['vk_id']]);
                                    $gbanname = $gbanid[0]['first_name'];
                                    $gbanpname = $gbanid[0]['last_name'];
    
                                    $is_adminss .= '@id' . $row['vk_id'] . ' (' . $gbanname . ' ' . $gbanpname . ')' . PHP_EOL;
    
                                }
    
                                $vk->sendMessage($peer_id, 'Пользователи находящиеся в глобальном бане бота:' . PHP_EOL . $is_adminss, ['disable_mentions' => '1']);
    
                            } else {
    
                                $vk->sendMessage($peer_id, BT_WARN . 'Никого не найдено');
    
                            }
    
                        }
    
                    }
                    if (mb_substr($message,0,7) == '+uspam ') {
    
                        $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($agentsql || $admsql) {
    
                            $obl     = mb_substr($message, 7);
                            $obj     = explode(" ", $obl, 2);
                            $user_id = $obj[0];
                            $user_id = explode("|", mb_substr($user_id, 3))[0];
                            $reason  = explode("\n", $obl)[1];
    
                            $check_spam_user = $db->query("SELECT spammed FROM users WHERE vk_id = '$user_id'")->fetch_assoc()['spammed'];
                            $reason_user     = $db->query("SELECT spam_reason FROM users WHERE vk_id = '$user_id'")->fetch_assoc()['spam_reason'];
                            $checktable_user = $db->query("SELECT vk_id FROM users WHERE vk_id = '$user_id'")->getNumRows();
    
                            if (is_numeric($user_id)) {
    
                                if ($user_id && $reason_user == 'none' && $check_spam_user == '1') {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Пользователь уже записан как спамер.');
    
                                } elseif ($user_id && $reason_user !== 'none' && $check_spam_user == '1') {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Пользователь уже записан как спамер по причине: ' . $reason_user);
    
                                } else {
    
                                    if ($user_id && !$reason && $check_spam_club !== '1') {
    
                                        if ($checktable_user) {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @id' . $user_id . ' (Пользователь) был записан как спамер.', ['disable_mentions' => '1']);
                                            $db->query("UPDATE users SET spammed = '1' WHERE vk_id = '$user_id'");
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @club' . $user_id . ' (Пользователь) был записан как спамер.', ['disable_mentions' => '1']);
                                            $db->query("INSERT INTO users (vk_id, peer_id, spammed, spam_reason) VALUES ('$user_id', '$peer_id', '1', '$reason')");
    
                                        }
    
                                    } elseif ($user_id && $reason && $check_spam_club !== '1') {
    
                                        if ($checktable_user) {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @id' . $user_id . ' (Пользователь) был записан как спамер c причиной: ' . $reason, ['disable_mentions' => '1']);
                                            $db->query("UPDATE users SET spammed = '1', spam_reason = '$reason' WHERE vk_id = '$user_id'");
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @id' . $user_id . ' (Пользователь) был записан как спамер c причиной: ' . $reason, ['disable_mentions' => '1']);
                                            $db->query("INSERT INTO users (vk_id, peer_id, spammed, spam_reason) VALUES ('$user_id', '$peer_id', '1', '$reason')");
    
                                        }
    
                                    }
    
                                }
    
                            }
    
                        }
    
                    }
                    if (mb_substr($message,0,7) == '+gspam ') {
    
                        $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($agentsql || $admsql) {
    
                            $obl      = mb_substr($message, 7);
                            $obj      = explode(" ", $obl, 2);
                            $group_id = $obj[0];
                            $group_id = explode("|", mb_substr($group_id, 5))[0];
                            $reason   = explode("\n", $obl)[1];
    
                            $check_spam_group = $db->query("SELECT spammed FROM users WHERE vk_id = '-$group_id'")->fetch_assoc()['spammed'];
                            $reason_group     = $db->query("SELECT spam_reason FROM users WHERE vk_id = '-$group_id'")->fetch_assoc()['spam_reason'];
                            $checktable_group = $db->query("SELECT vk_id FROM users WHERE vk_id = '-$group_id'")->getNumRows();
                            $checkall_group   = $db->query("SELECT * FROM users WHERE vk_id = '-$group_id' AND spammed = '1'")->getNumRows();
    
                            if (is_numeric($group_id)) {
    
                                if ($reason_group == 'none' && $checkall_group) {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Сообщество уже записано как спамерское');
    
                                } elseif ($reason_group !== 'none' && $checkall_group) {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Сообщество уже записано как спамерское по причине: ' . $reason_group);
    
                                } elseif (!$checkall_group) {
    
                                    if ($group_id && !$reason) {
    
                                        if ($checkall_group) {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было записано как спамерское.', ['disable_mentions' => '1']);
                                            $db->query("UPDATE users SET spammed = '1' WHERE vk_id = '-$group_id'");
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было записано как спамерское.', ['disable_mentions' => '1']);
                                            $db->query("INSERT INTO users (vk_id, peer_id, spammed, spam_reason) VALUES ('-$group_id', '$peer_id', '1')");
    
                                        }
    
                                    } elseif ($group_id && $reason) {
    
                                        if ($checkall_group) {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было записано как спамерское c причиной: ' . $reason, ['disable_mentions' => '1']);
                                            $db->query("UPDATE users SET spammed = '1', spam_reason = '$reason' WHERE vk_id = '-$group_id'");
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было записано как спамерское c причиной: ' . $reason, ['disable_mentions' => '1']);
                                            $db->query("INSERT INTO users (vk_id, peer_id, spammed, spam_reason) VALUES ('-$group_id', '$peer_id', '1', '$reason')");
    
                                        }
    
                                    }
    
                                }
    
                            }
    
                        }
    
                    }
                    if (mb_substr($message,0,7) == '-uspam ') {
    
                        $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($agentsql || $admsql) {
    
                            $obl = mb_substr($message, 7);
                            $user_id = explode("|", mb_substr($obl, 3))[0];
                            $reason = $db->query("SELECT spam_reason FROM users WHERE vk_id = '$user_id'")->fetch_assoc()['spam_reason'];
                            $check_spam = $db->query("SELECT spammed FROM users WHERE vk_id = '$user_id'")->fetch_assoc()['spammed'];
    
                            if (is_numeric($user_id)) {
    
                                if ($check_spam !== '0') {
    
                                    if ($reason !== 'none') {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' @id' . $user_id . ' (Пользователь) был удален из списка спамеров. Причиной было: ' . $reason, ['disable_mentions' => '1']);
                                        $db->query("UPDATE users SET spammed = '0' WHERE vk_id = '$user_id'");
    
                                    } elseif ($reason == 'none') {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' @id' . $user_id . ' (Пользователь) был удален из списка спамеров.', ['disable_mentions' => '1']);
                                        $db->query("UPDATE users SET spammed = '0' WHERE vk_id = '$user_id'");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Пользователь уже вынесен из списка спамеров.');
    
                                }
    
                            }
    
                        }
    
                    }
                    if (mb_substr($message,0,7) == '-gspam ') {
    
                        $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
                        $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                        if ($agentsql || $admsql) {
    
                            $obl = mb_substr($message, 7);
                            $group_id = explode("|", mb_substr($obl, 5))[0];
                            $reason = $db->query("SELECT spam_reason FROM users WHERE vk_id = '-$group_id'")->fetch_assoc()['spam_reason'];
                            $check_spam = $db->query("SELECT spammed FROM users WHERE vk_id = '-$group_id'")->fetch_assoc()['spammed'];
    
                            if (is_numeric($group_id)) {
    
                                if ($check_spam == '0') {
    
                                    if ($reason !== 'none') {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было удалено из списка спамеров. Причиной было: ' . $reason);
                                        $db->query("UPDATE users SET spammed = '0' WHERE vk_id = '-$group_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' @club' . $group_id . ' (Сообщество) было удалено из списка спамеров.');
                                        $db->query("UPDATE users SET spammed = '0' WHERE vk_id = '-$group_id'");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Сообщество уже вынесено из списка спамеров.');
    
                                }
    
                            }
    
                        }
    
                    }
                    // Андроиды
                    if ($cmd[0] == '!обратно') {
    
                        $rang = $db->query("SELECT return_user FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['return_user'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if (!$from_id_fwd) {
    
                                $id = $cmd[1];
                                $id_obr = explode("|", $id)[1];
                                $id_obr = mb_substr($id_obr, 1);
                                $id_obr = explode("]", $id_obr)[0];
    
                                if ($id_obr) {
    
                                    $checkid = $vk->request('utils.resolveScreenName', ['screen_name' => $id_obr])['type'];
    
                                    if ($checkid == 'user') {
    
                                        $id = explode("|", mb_substr($id, 3))[0];
    
                                    } 
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Не могу вернуть пустоту (вы не указали кого вернуть).');
    
                                }
    
                            } else {
    
                                if ($from_id_fwd < 0) {
    
                                    $checkid = 'group';
    
                                } else {
    
                                    $checkid = 'user';
                                    $id = $from_id_fwd;
    
                                }
    
                            }
                            if ($id == $from_id) {
    
                                $vk->sendMessage($peer_id, 'Самого себя вернуть нельзя, вы же уже в чате.');
                                
                            } else {

                                if ($checkid == 'user') {

                                    if ($vk->isAdmin($id, $peer_id) == 'none') {

                                        $chat_android = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];

                                        $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$chat_android'")->fetch_assoc()['address'];
            
                                        $title = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id])['items'][0]['chat_settings']['title'];

                                        $dat = array("response" => "return_user", "object" => array("user_id" => $chat_android, "ruid" => $id, "peerId" => $peer_id, 'conv_name' => "$title"));

                                        $json = json_encode($dat, JSON_UNESCAPED_UNICODE);

                                        $out = postResponse($getAddress, $json);

                                        $out = json_decode($out, true);

                                        if ($out['errorvk']) {

                                            $vk->sendMessage($peerid, BT_DENY . " Андроид вернул следующий отчет от ВКонтакте: $out[errorvk].");

                                        }
                                        if ($out['error']) {
                
                                            if ($out['error'] == 'rid_not_friend') {
                    
                                                $vk->sendMessage($peerid, BT_DENY . " Андроид вернул следующий отчет: Невозможно добавить пользователя так как он не является мои другом.");
                    
                                            }
                
                                        }
                                        if ($out['answer'] == 'ok') {
                
                                            return;
                
                                        }

                                    } else {

                                        $vk->sendMessage($peer_id, 'Андроид вернул следующий отчет: Пользователь уже в беседе.');

                                    }

                                }

                            }

                        } else {

                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
                                
                            if ($mentions !== '1') {
                                    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!обратно' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
                                    
                            }

                        }

                    }
                    if ($cmd[0] == '!а') {

                        $rang = $db->query("SELECT execute_android_command FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['execute_android_command'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {

                            $report = $cmd[1];

                            if ($report == '-del') {

                                $rang = $db->query("SELECT delete_msg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['delete_msg'];
                            
                                $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
                            
                                if ($rang <= $userrang) {
                                
                                    $conv_id_reply   = $data->object->message->reply_message->conversation_message_id;
                                    $conv_id_forwd   = $data->object->message->fwd_messages[0]->conversation_message_id;
                                    $text_fwd_mssg   = $data->object->message->fwd_messages[0]->text;
                                
                                    if ($conv_id_reply) {
                                    
                                        $getAndroid = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];
                                    
                                        if ($getAndroid !== '0') {
                                        
                                            $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$getAndroid'")->fetch_assoc()['address'];
                                            $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$getAndroid'")->fetch_assoc()['codename'];
                                        
                                            $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
                                        
                                            foreach ($getId['items'] as $getTitle) {
                                            
                                                $title = $getTitle['chat_settings']['title'];
                                            
                                            }
                                        
                                            if ($db->query("SELECT silent_mode FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mode'] == '0') {
                                            
                                                $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_reply, "conv_name" => "$title", "silent_mode" => false));
                                            
                                            } else {
                                            
                                                $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_reply, "conv_name" => "$title", "silent_mode" => true));
                                            
                                            }
                                        
                                            $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
                                        
                                            $outorig = postResponse($getAddress, $dat);
                                        
                                            $out = json_decode($outorig, true);
                                        
                                            if ($out['answer'] == 'ok') {
                                            
                                                return;
                                            
                                            } elseif ($out['answer'] !== 'ok' && !$out['error'] && !$out['errorvk']) {
                                            
                                                $vk->sendMessage($peer_id, 'Возникла ошибка, отправляю отчет в Л/С');
                                                $vk->sendMessage($getAndroid, BT_DEN . ' Ответ вашего сервера: ' . $outorig . PHP_EOL . 'Если вам пришло данное сообщение значит на сервере произошла ошибка');
                                            
                                            } elseif ($out['error']) {
                                            
                                                if ($out['error'] == 'android_not_admin_or_owner') {
                                                
                                                    $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Андроид не администратор или не создатель.');
                                                
                                                } else {
                                                
                                                    $vk->sendMessage($peer_id, BT_DEN . ' Ответ сервера: ' . $out['error']);
                                                
                                                }
                                            
                                            } elseif ($out['errorvk']) {
                                            
                                                $vk->sendMessage($peer_id, BT_DEN . ' Ответ ВКонтакте: ' . $out['errorvk']);
                                            
                                            }
                                        
                                        } else {
                                        
                                            $vk->sendMessage($peer_id, 'Поискав в базе данных я не нашел ни одного совпадения чтобы у вас был арендован / установлен Андроид.');
                                        
                                        }
                                    
                                    } elseif ($conv_id_forwd) {
                                    
                                        $getAndroid = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];
                                    
                                        if ($getAndroid !== '0') {
                                        
                                            $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$getAndroid'")->fetch_assoc()['address'];
                                            $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$getAndroid'")->fetch_assoc()['codename'];
                                        
                                            $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
                                        
                                            foreach ($getId['items'] as $getTitle) {
                                            
                                                $title = $getTitle['chat_settings']['title'];
                                            
                                            }
                                        
                                            if ($db->query("SELECT silent_mode FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mode'] == '0') {
                                            
                                                $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_forwd, "conv_name" => "$title", "silent_mode" => false));
                                            
                                            } else {
                                            
                                                $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_forwd, "conv_name" => "$title", "silent_mode" => true));
                                            
                                            }
                                        
                                            $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
                                        
                                            $outorig = postResponse($getAddress, $dat);
                                        
                                            $out = json_decode($outorig, true);
                                        
                                            if ($out['answer'] == 'ok') {
                                            
                                                return;
                                            
                                            } elseif ($out['answer'] !== 'ok' && !$out['error'] && !$out['errorvk']) {
                                            
                                                $vk->sendMessage($peer_id, 'Возникла ошибка, отправляю отчет в Л/С');
                                                $vk->sendMessage($getAndroid, BT_DEN . ' Ответ вашего сервера: ' . $outorig . PHP_EOL . 'Если вам пришло данное сообщение значит на сервере произошла ошибка');
                                            
                                            } elseif ($out['errorvk']) {
                                            
                                                $vk->sendMessage($peer_id, BT_DEN . ' Ответ ВКонтакте: ' . $out['errorvk']);
                                            
                                            } elseif ($out['error']) {
                                            
                                                if ($out['error'] == 'message_in_chat_not_found') {
                                                
                                                    $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Пересланные сообщения в этом чате не найдены, поэтому удалить их я не могу.');
                                                
                                                } elseif ($out['error'] == 'android_not_admin_or_owner') {
                                                
                                                    $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Андроид не администратор или не создатель.');
                                                
                                                }
                                            
                                            }
                                        
                                        } else {
                                        
                                            $vk->sendMessage($peer_id, 'Поискав в базе данных я не нашел ни одного совпадения чтобы у вас был арендован / установлен Андроид.');
                                        
                                        }
                                    
                                    } elseif (!$conv_id_forwd && !$conv_id_reply) {
                                    
                                        $vk->sendMessage($peer_id, 'Не найдено сообщений для удаления.');
                                    
                                    }
                                
                                } else {
                                
                                    $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
                                
                                    if ($mentions !== '1') {
                                    
                                        $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-del' ограничена для вас, так как ваш ранг ниже требуемого.");
                                        return;
                                    
                                    }
                                
                                }

                            }

                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!а' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
                        
                    }
                    if ($message == '!связать') {

                        $rang = $db->query("SELECT set_android_trial FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_android_trial'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {

                            if ($vk->isAdmin(562061930, $peer_id) !== 'none' && $vk->isAdmin(562061930, $peer_id) == 'admin') {

                                $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
    
                                foreach ($getId['items'] as $getTitle) {
    
                                    $title = $getTitle['chat_settings']['title'];
    
                                }
    
                                $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '562061930'")->fetch_assoc()['address'];
                                $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '562061930'")->fetch_assoc()['codename'];
    
                                $arra = array("response" => "activate_peer", "object" => array("user_id" => 562061930, 'codename' => "$getCode", "conv_name" => "$title"));
    
                                $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
    
                                $out = postResponse($getAddress, $dat);
    
                                $out = json_decode($out, true);
    
                                if ($out['answer'] == 'ok') {
    
                                    $vk->sendMessage($peer_id, 'Привязано!');
                                    $db->query("UPDATE peers SET android = '562061930' WHERE peer_id = '$peer_id'");
    
                                } elseif ($out['error']) {
    
                                    $vk->sendMessage($peer_id, 'Возникла ошибка');
    
                                }

                            } elseif ($vk->isAdmin(562061930, $peer_id) !== 'none' && !$vk->isAdmin(562061930, $peer_id)) {

                                $vk->sendMessage($peer_id, 'Назначьте @id562061930 (Алексея Баймурзаева) администратором для корректной работы.');

                            } elseif ($vk->isAdmin(562061930, $peer_id) == 'none') {

                                $vk->sendMessage($peer_id, 'Пригласите @id562061930 (Алексея Баймурзаева) и назначьте его администратором для корректной работы.');

                            }

                        }

                    }
                    if ($message == '+calls') {
    
                        $rang = $db->query("SELECT set_calls FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_calls'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
    
                            foreach ($getId['items'] as $getTitle) {
    
                                $title = $getTitle['chat_settings']['title'];
    
                            }
    
                            $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$from_id'")->fetch_assoc()['address'];
                            $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$from_id'")->fetch_assoc()['codename'];
                            $check = $db->query("SELECT * FROM android_data WHERE user_id = '$from_id'")->getNumRows();
    
                            if ($check) {
    
                                $arra = array("response" => "activate_peer", "object" => array("user_id" => $from_id, 'codename' => "$getCode", "conv_name" => "$title"));
    
                                $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
    
                                $out = postResponse($getAddress, $dat);
    
                                $out = json_decode($out, true);
    
                                if ($out['answer'] == 'ok') {
    
                                    $vk->sendMessage($peer_id, 'Привязано!');
                                    $db->query("UPDATE peers SET android = '$from_id' WHERE peer_id = '$peer_id'");
    
                                } elseif ($out['error']) {
    
                                    $vk->sendMessage($peer_id, 'Возникла ошибка');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Регистрироваться в беседе без подключенной анкеты?', ['attachment' => 'video435952306_456239348']);
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+calls' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
    
                    if ($message == '-del') {
    
                        $rang = $db->query("SELECT delete_msg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['delete_msg'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $conv_id_reply   = $data->object->message->reply_message->conversation_message_id;
                            $conv_id_forwd   = $data->object->message->fwd_messages[0]->conversation_message_id;
                            $text_fwd_mssg   = $data->object->message->fwd_messages[0]->text;
    
                            if ($conv_id_reply) {
    
                                $getAndroid = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];
    
                                if ($getAndroid !== '0') {
    
                                    $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$getAndroid'")->fetch_assoc()['address'];
                                    $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$getAndroid'")->fetch_assoc()['codename'];
    
                                    $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
    
                                    foreach ($getId['items'] as $getTitle) {
    
                                        $title = $getTitle['chat_settings']['title'];
    
                                    }
    
                                    if ($db->query("SELECT silent_mode FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mode'] == '0') {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_reply, "conv_name" => "$title", "silent_mode" => false));
    
                                    } else {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_reply, "conv_name" => "$title", "silent_mode" => true));
    
                                    }
    
                                    $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
    
                                    $outorig = postResponse($getAddress, $dat);
    
                                    $out = json_decode($outorig, true);
    
                                    if ($out['answer'] == 'ok') {
    
                                        return;
    
                                    } elseif ($out['answer'] !== 'ok' && !$out['error'] && !$out['errorvk']) {
    
                                        $vk->sendMessage($peer_id, 'Возникла ошибка, отправляю отчет в Л/С');
                                        $vk->sendMessage($getAndroid, BT_DEN . ' Ответ вашего сервера: ' . $outorig . PHP_EOL . 'Если вам пришло данное сообщение значит на сервере произошла ошибка');
    
                                    } elseif ($out['error']) {
    
                                        if ($out['error'] == 'android_not_admin_or_owner') {
    
                                            $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Андроид не администратор или не создатель.');
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_DEN . ' Ответ сервера: ' . $out['error']);
    
                                        }
    
                                    } elseif ($out['errorvk']) {
    
                                        $vk->sendMessage($peer_id, BT_DEN . ' Ответ ВКонтакте: ' . $out['errorvk']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Поискав в базе данных я не нашел ни одного совпадения чтобы у вас был арендован / установлен Андроид.');
    
                                }
    
                            } elseif ($conv_id_forwd) {
    
                                $getAndroid = $db->query("SELECT android FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['android'];
    
                                if ($getAndroid !== '0') {
    
                                    $getAddress = $db->query("SELECT address FROM android_settings WHERE user_id = '$getAndroid'")->fetch_assoc()['address'];
                                    $getCode = $db->query("SELECT codename FROM android_data WHERE user_id = '$getAndroid'")->fetch_assoc()['codename'];
    
                                    $getId = $vk->request('messages.getConversationsById', ['peer_ids' => $peer_id]);
    
                                    foreach ($getId['items'] as $getTitle) {
    
                                        $title = $getTitle['chat_settings']['title'];
    
                                    }
    
                                    if ($db->query("SELECT silent_mode FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['silent_mode'] == '0') {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_forwd, "conv_name" => "$title", "silent_mode" => false));
    
                                    } else {
    
                                        $arra = array("response" => "delete", "object" => array("user_id" => $getAndroid, 'codename' => $getCode, "conv_id" => $conv_id_forwd, "conv_name" => "$title", "silent_mode" => true));
    
                                    }
    
                                    $dat = json_encode($arra, JSON_UNESCAPED_UNICODE);
    
                                    $outorig = postResponse($getAddress, $dat);
    
                                    $out = json_decode($outorig, true);
    
                                    if ($out['answer'] == 'ok') {
    
                                        return;
    
                                    } elseif ($out['answer'] !== 'ok' && !$out['error'] && !$out['errorvk']) {
    
                                        $vk->sendMessage($peer_id, 'Возникла ошибка, отправляю отчет в Л/С');
                                        $vk->sendMessage($getAndroid, BT_DEN . ' Ответ вашего сервера: ' . $outorig . PHP_EOL . 'Если вам пришло данное сообщение значит на сервере произошла ошибка');
    
                                    } elseif ($out['errorvk']) {
    
                                        $vk->sendMessage($peer_id, BT_DEN . ' Ответ ВКонтакте: ' . $out['errorvk']);
    
                                    } elseif ($out['error']) {
    
                                        if ($out['error'] == 'message_in_chat_not_found') {
    
                                            $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Пересланные сообщения в этом чате не найдены, поэтому удалить их я не могу.');
    
                                        } elseif ($out['error'] == 'android_not_admin_or_owner') {
    
                                            $vk->sendMessage($peer_id, BT_DEN . ' Андроид вернул следующий отчет: Андроид не администратор или не создатель.');
    
                                        }
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Поискав в базе данных я не нашел ни одного совпадения чтобы у вас был арендован / установлен Андроид.');
    
                                }
    
                            } elseif (!$conv_id_forwd && !$conv_id_reply) {
    
                                $vk->sendMessage($peer_id, 'Не найдено сообщений для удаления.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-del' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    //АДМИН - КОМАНДЫ (Беседы)
                    if (explode("\n", $cmd[0])[0] == 'бан') {
    
                        $rang = $db->query("SELECT ban FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ban'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if (!$from_id_fwd) {
    
                                $idban = explode("\n", $cmd[1])[0];
                                $id_obr = explode("|", $idban)[1];
                                $id_obr = mb_substr($id_obr, 1);
                                $id_obr = explode("]", $id_obr)[0];
    
                                if ($id_obr) {
    
                                    $checkid = $vk->request('utils.resolveScreenName', ['screen_name' => $id_obr])['type'];
    
                                    if ($checkid == 'user') {
    
                                        $idbann = explode("|", mb_substr($idban, 3))[0];
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать бан никому.');
    
                                }
    
                                $timeban = explode("\n", $cmd[2])[0];
                                $twotimeban = explode("\n", $cmd[3])[0];
                                $reason = explode("\n", $original)[1];
    
                            } else {
    
                                if ($from_id_fwd < 0) {
    
                                    $checkid = 'group';
    
                                } else {
    
                                    $checkid = 'user';
                                    $idbann = $from_id_fwd;
    
                                }
                                $timeban = explode("\n", $cmd[1])[0];
                                $twotimeban = explode("\n", $cmd[2])[0];
                                $reason = explode("\n", $original)[1];
    
                            }
                            if ($idbann == $from_id) {
    
                                $vk->sendMessage($peer_id, 'Самого себя банить нельзя.');
                                
                            } else {
    
                                if ($idbann) {
    
                                    if ($checkid !== 'group' || $checkid !== 'application' && $checkid == 'user') {
    
                                        if ($userrang > $db->query("SELECT rang FROM users WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {
    
                                            if ($timeban && $twotimeban && preg_match("/мин(ут(ы|а))|ч(ас(а|ов))|д(н(я|ей)|ень)|нед(ел(я|и|ь))|мес(яц(а|ев))|лет|год|года/u", $twotimeban)) {
    
                                                if (preg_match("/[0-9]/", $timeban)) {
    
                                                    if (preg_match("/мин(ут(ы|а))/u", $twotimeban)) {
    
                                                        if ($timeban <= 10000000) {
                                                            
                                                            //$vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'Минуты']);
    
                                                            $time = $timeban;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' мин.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' мин.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 10000000 минут.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/ч(ас(а|ов))/u", $twotimeban)) {
    
                                                        if ($timeban <= 1000000) {
    
                                                            $time = $timeban * 60;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' час.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' час.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1000000 часов.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/д(н(я|ей))/u", $twotimeban)) {
    
                                                        if ($timeban <= 10000) {
    
                                                            $time = $timeban * 1440;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' дн.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' дн.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 10000 дней.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/нед(ел(я|и|ь))/u", $twotimeban)) {
    
                                                        if ($timeban <= 1000) {
    
                                                            $time = $timeban * 10080;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' нед.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' нед.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1000 недель.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/мес(яц(а|ев))/u", $twotimeban)) {
    
                                                        if ($timeban <= 100) {
    
                                                            $time = $timeban * 43200;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' мес.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' мес.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 100 месяцев.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/лет|год|года/u", $twotimeban)) {
    
                                                        if ($timeban <= 100) {
    
                                                            $time = $timeban * 525600;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' год.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban . ' год.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1 век.');
    
                                                        }
    
                                                    }
    
                                                } else {
    
                                                    $vk->sendMessage($peer_id, BT_WARN . ' Неверная дата выдачи.');
    
                                                }
    
                                            } elseif ($timeban && preg_match("/мин(ут(ы|а))|ч(ас(а|ов))|д(н(я|ей)|ень)|нед(ел(я|и|ь))|мес(яц(а|ев))|лет|год|года/u", $timeban)) {
    
                                                if (preg_match("/[0-9]/", $timeban)) {
    
                                                    $timeban1 = preg_replace("/[^0-9]/", '', $timeban);
                                                    
                                                    $timeban = preg_replace("/[0-9]/", '', $timeban);
    
                                                    if (preg_match("/мин(ут(ы|а))/u", $timeban)) {
    
                                                        if ($timeban1 <= 10000000) {
    
                                                            $time = $timeban1;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' мин.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' мин.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 10000000 минут.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/ч(ас(а|ов))/u", $timeban)) {
    
                                                        if ($timeban1 <= 1000000) {
    
                                                            $time = $timeban1 * 60;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' час.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' час.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1000000 часов.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/д(н(я|ей))/u", $timeban)) {
    
                                                        if ($timeban1 <= 10000) {
    
                                                            $time = $timeban1 * 1440;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' дн.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' дн.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 10000 дней.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/нед(ел(я|и|ь))/u", $timeban)) {
    
                                                        if ($timeban1 <= 1000) {
    
                                                            $time = $timeban1 * 10080;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' нед.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' нед.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1000 недель.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/мес(яц(а|ев))/u", $timeban)) {
    
                                                        if ($timeban1 <= 100) {
    
                                                            $time = $timeban1 * 43200;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' мес.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' мес.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 100 месяцев.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/лет|год|года/u", $timeban)) {
    
                                                        if ($timeban1 <= 100) {
    
                                                            $time = $timeban1 * 525600;
    
                                                            $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                            $name = $banfl[0]['first_name'];
                                                            $pname = $banfl[0]['last_name'];
    
                                                            $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' год.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            } else {
    
                                                                if ($getbans) {
    
                                                                    $db->query("UPDATE bans SET timeban = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан на ' . $timeban1 . ' год.' . PHP_EOL . 'Причина: ' . $reason]);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать бан на срок больше чем 1 век.');
    
                                                        }
    
                                                    }
    
                                                } else {
    
                                                    $vk->sendMessage($peer_id, BT_WARN . ' Неверная дата выдачи.');
    
                                                }
                                                
                                            } elseif ($timeban == 'навсегда' || !$timeban) {
    
                                                $banfl = $vk->request("users.get", ['user_ids' => $idbann]);
                                                $name = $banfl[0]['first_name'];
                                                $pname = $banfl[0]['last_name'];
    
                                                $getbans = $db->query("SELECT vk_id, peer_id FROM bans WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->getNumRows();
    
                                                if (!$reason) {
    
                                                    if ($getbans) {
    
                                                        $db->query("UPDATE bans SET timeban = '9999999999', moder_id = '$from_id' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                    } else {
    
                                                        $db->query("INSERT INTO bans (vk_id, peer_id, timeban, moder_id) VALUES ('$idbann', '$peer_id', '9999999999', '$from_id')");
    
                                                    }
                                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан навсегда.']);
                                                    $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                } else {
    
                                                    if ($getbans) {
    
                                                        $db->query("UPDATE bans SET timeban = '9999999999', moder_id = '$from_id', reason = '$reason' WHERE vk_id = '$idbann' AND peer_id = '$peer_id'");
    
                                                    } else {
    
                                                        $db->query("INSERT INTO bans (vk_id, peer_id, timeban, reason, moder_id) VALUES ('$idbann', '$peer_id', '9999999999', '$reason', '$from_id')");
    
                                                    }
                                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idbann . ' (' . $name . ' ' . $pname . '), бан навсегда.' . PHP_EOL . 'Причина: ' . $reason]);
                                                    $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idbann]);
    
                                                }
    
                                            }
                                            
                                        } elseif ($userrang < $db->query("SELECT rang FROM users WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->fetch_assoc()['rang'] || $userrang == $db->query("SELECT rang FROM users WHERE vk_id = '$idbann' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {
    
                                            $vk->sendMessage($peer_id, 'Нельзя банить человека который выше вас по рангу или человеку чей ранг равен вашему.');
                                            
                                        }
    
                                    } elseif ($checkid !== 'group' && $checkid !== 'application' && $checkid !== 'user') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не указано кому выдавать.');
    
                                    } elseif ($checkid == 'group') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать бан группе.');
    
                                    } elseif ($checkid == 'application') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать бан приложению.');
    
                                    }
                                    
                                }
                                
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'бан' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if (explode("\n", $cmd[0])[0] == 'мут') {
    
                        $rang = $db->query("SELECT mute FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mute'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if (!$from_id_fwd) {
    
                                $idmute = explode("\n", $cmd[1])[0];
                                $id_obr = explode("|", $idmute)[1];
                                $id_obr = mb_substr($id_obr, 1);
                                $id_obr = explode("]", $id_obr)[0];
                                $checkid = $vk->request('utils.resolveScreenName', ['screen_name' => $id_obr])['type'];
                                if ($checkid == 'user') {
    
                                    $idmuten = explode("|", mb_substr($idmute, 3))[0];
    
                                }
                                $timemute = explode("\n", $cmd[2])[0];
                                $twotimemute = explode("\n", $cmd[3])[0];
                                $reason = explode("\n", $original)[1];
    
                            } else {
    
                                if ($from_id_fwd < 0) {
    
                                    $checkid = 'group';
    
                                } else {
    
                                    $checkid = 'user';
                                    $idmuten = $from_id_fwd;
    
                                }
                                $timemute = explode("\n", $cmd[1])[0];
                                $twotimemute = explode("\n", $cmd[2])[0];
                                $reason = explode("\n", $original)[1];
    
                            }
                            
                            if ($idmuten == $from_id) {
    
                                $vk->sendMessage($peer_id, 'Самого себя мутить нельзя.');
                                
                            } else {
    
                                if ($idmuten) {
    
                                    if ($checkid !== 'group' || $checkid !== 'application' && $checkid == 'user') {
    
                                        if ($userrang > $db->query("SELECT rang FROM users WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {
    
                                            if ($timemute && preg_match("/мин(ут(ы|а))|ч(ас(а|ов))|д(н(я|ей)|ень)|нед(ел(я|и|ь))|мес(яц(а|ев))|лет|год|года/u", $twotimemute)) {
    
                                                if (is_numeric($idmuten)) {
    
                                                    if (preg_match("/мин(ут(ы|а))/u", $twotimemute)) {
    
                                                        if ($timemute <= 10000000) {
    
                                                            $time = $timemute;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' мин.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' мин.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 10000000 минут.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/ч(ас(а|ов))/u", $twotimemute)) {
    
                                                        if ($timemute <= 1000000) {
    
                                                            $time = $timemute * 60;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' час.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' час.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1000000 часов.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/д(н(я|ей))/u", $twotimemute)) {
    
                                                        if ($timemute <= 10000) {
    
                                                            $time = $timemute * 1440;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' дн.']);
                                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $idmuten]);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' дн.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 10000 дней.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/нед(ел(я|и|ь))/u", $twotimemute)) {
    
                                                        if ($timemute <= 1000) {
    
                                                            $time = $timemute * 10080;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' нед.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' нед.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1000 недель.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/мес(яц(а|ев))/u", $twotimemute)) {
    
                                                        if ($timemute <= 100) {
    
                                                            $time = $timemute * 43200;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' мес.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' мес.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 100 месяцев.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/лет|год|года/u", $twotimemute)) {
    
                                                        if ($timemute <= 100) {
    
                                                            $time = $timemute * 525600;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' год.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute . ' год.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1 век.');
    
                                                        }
    
                                                    }
    
                                                } else {
    
                                                    $vk->sendMessage($peer_id, BT_WARN . ' Неверная дата выдачи.');
    
                                                }
    
                                            } elseif (preg_match("/мин(ут(ы|а))|ч(ас(а|ов))|д(н(я|ей)|ень)|нед(ел(я|и|ь))|мес(яц(а|ев))|лет|год|года/u", $timemute)) {
    
                                                if (preg_match("/[0-9]/", $timemute)) {
    
                                                    $timemute1 = preg_replace("/[^0-9]/", '', $timemute);
    
                                                    if (preg_match("/мин(ут(ы|а))/u", $timemute)) {
    
                                                        if ($timemute1 <= 10000000) {
    
                                                            $time = $timemute1;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' мин.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' мин.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 10000000 минут.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/ч(ас(а|ов))/u", $timemute)) {
    
                                                        if ($timemute1 <= 1000000) {
    
                                                            $time = $timemute1 * 60;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time' WHERE vk_id = '$idmuten', moder_id = '$from_id' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' час.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' час.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1000000 часов.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/д(н(я|ей))/u", $timemute)) {
    
                                                        if ($timemute1 <= 10000) {
    
                                                            $time = $timemute1 * 1440;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time' WHERE vk_id = '$idmuten', moder_id = '$from_id' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' дн.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' дн.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 10000 дней.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/нед(ел(я|и|ь))/u", $timemute)) {
    
                                                        if ($timemute1 <= 1000) {
    
                                                            $time = $timemute1 * 10080;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time' WHERE vk_id = '$idmuten', moder_id = '$from_id' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' нед.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' нед.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1000 недель.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/мес(яц(а|ев))/u", $timemute)) {
    
                                                        if ($timemute1 <= 100) {
    
                                                            $time = $timemute1 * 43200;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time' WHERE vk_id = '$idmuten', moder_id = '$from_id' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' мес.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' мес.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 100 месяцев.');
    
                                                        }
    
                                                    }
                                                    if (preg_match("/лет|год|года/u", $timemute)) {
    
                                                        if ($timemute1 <= 100) {
    
                                                            $time = $timemute1 * 525600;
    
                                                            $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                            $name = $mutefl[0]['first_name'];
                                                            $pname = $mutefl[0]['last_name'];
    
                                                            $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                            if (!$reason) {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time' WHERE vk_id = '$idmuten', moder_id = '$from_id' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' год.']);
    
                                                            } else {
    
                                                                if ($getmutes) {
    
                                                                    $db->query("UPDATE mutes SET timemute = '$time', reason = '$reason', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                                } else {
    
                                                                    $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '$time', '$reason', '$from_id')");
    
                                                                }
                                                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), мут на ' . $timemute1 . ' год.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                            }
    
                                                        } else {
    
                                                            $vk->sendMessage($peer_id, BT_WARN . ' Нельзя выдавать мут на срок больше чем 1 век.');
    
                                                        }
    
                                                    }
    
                                                } else {
    
                                                    $vk->sendMessage($peer_id, BT_WARN . ' Неверная дата выдачи.');
    
                                                }
                                                
                                            } elseif ($timemute == 'навсегда' || !$timemute) {
    
                                                $mutefl = $vk->request("users.get", ['user_ids' => $idmuten]);
                                                $name = $mutefl[0]['first_name'];
                                                $pname = $mutefl[0]['last_name'];
    
                                                $getmutes = $db->query("SELECT vk_id, peer_id FROM mutes WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->getNumRows();
    
                                                if (!$reason) {
    
                                                    if ($getmutes) {
    
                                                        $db->query("UPDATE mutes SET timemute = '9999999999', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                    } else {
    
                                                        $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, moder_id) VALUES ('$idmuten', '$peer_id', '9999999999', '$from_id')");
    
                                                    }
                                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), бессрочный мут.']);
    
                                                } else {
    
                                                    if ($getmutes) {
    
                                                        $db->query("UPDATE mutes SET timemute = '9999999999', moder_id = '$from_id' WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'");
    
                                                    } else {
    
                                                        $db->query("INSERT INTO mutes (vk_id, peer_id, timemute, reason, moder_id) VALUES ('$idmuten', '$peer_id', '9999999999', '$reason', '$from_id')");
    
                                                    }
                                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $idmuten . ' (' . $name . ' ' . $pname . '), бессрочный мут.' . PHP_EOL . 'Причина: ' . $reason]);
    
                                                }
    
                                            }
                                            
                                        } elseif ($userrang < $db->query("SELECT rang FROM users WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->fetch_assoc()['rang'] || $userrang == $db->query("SELECT rang FROM users WHERE vk_id = '$idmuten' AND peer_id = '$peer_id'")->fetch_assoc()['rang']) {
    
                                            $vk->sendMessage($peer_id, 'Нельзя мутить человека который выше вас по рангу или человеку чей ранг равен вашему.');
                                            
                                        }
    
                                    } elseif ($checkid == 'group') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать мут группе.');
    
                                    } elseif ($checkid == 'application') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать мут приложению.');
    
                                    }
                                    
                                }
                                
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'мут' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'баны') {
    
                        $rang = $db->query("SELECT getBans FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['getBans'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $getBans = $db->query("SELECT vk_id, timeban, reason, moder_id FROM bans WHERE peer_id = '$peer_id'");
                            $checkBans = $db->query("SELECT vk_id, timeban, reason, moder_id FROM bans WHERE peer_id = '$peer_id'")->getNumRows();
                            
                            if ($checkBans) {
    
                                $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
                                
                                while ($names_result = $names_query->fetch_assoc()) {
    
                                    $moder_name = $names_result['name_moder'];
                                    $m_adm_name = $names_result['name_m_adm'];
                                    $adm_name = $names_result['name_adm'];
                                    $s_adm_name = $names_result['name_s_adm'];
                                    $own_name = $names_result['name_own'];
    
                                }
    
                                while ($row = $getBans->fetch_assoc()) {
    
                                    $rangs = $db->query("SELECT rang FROM users WHERE vk_id = '$row[moder_id]' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
                                    
                                    switch ($rangs) {
    
                                        case '1':
    
                                        $position = $moder_name;
                                        
                                        break;
                                        case '2':
    
                                        $position = $m_adm_name;
    
                                        break;
                                        case '3':
    
                                        $position = $adm_name;
    
                                        break;
                                        case '4':
    
                                        $position = $s_adm_name;
    
                                        break;
                                        case '5':
    
                                        $position = $own_name;
    
                                        break;
    
                                    }
                                    
                                    $time = time();
                                    $time = $time+$row['timeban'];
                                    
                                    $getInfoBans = $vk->request("users.get", ['user_ids' => $row['vk_id']]);
                                    $name = $getInfoBans[0]['first_name'];
                                    $pname = $getInfoBans[0]['last_name'];
                                    
                                    $getModer = $vk->request("users.get", ['user_ids' => $row['moder_id']]);
                                    $namem = $getModer[0]['first_name'];
                                    $pnamem = $getModer[0]['last_name'];
                                    
                                    if ($row['reason'] == '') {
    
                                        $bansGet .= '@id' . $row['vk_id'] . ' (' . $name . ' ' . $pname . '), бан до ' . date("d.m.Y H:i:s", $time) . PHP_EOL . 'Выдал ' . $position . ' @id' . $row['moder_id'] . ' (' . $namem . ' ' . $pnamem . ')' . PHP_EOL;
                                        
                                    } else {
    
                                        $bansGet .= '@id' . $row['vk_id'] . ' (' . $name . ' ' . $pname . '), бан до ' . date("d.m.Y H:i:s", $time) . PHP_EOL . 'Причина: ' . $row['reason'] . PHP_EOL . 'Выдал ' . $position . ' @id' . $row['moder_id'] . ' (' . $namem . ' ' . $pnamem . ')' . PHP_EOL;
                                        
                                    }
    
                                }
                                $vk->sendMessage($peer_id, $bansGet . PHP_EOL);
                                
                            } else {
    
                                $vk->sendMessage($peer_id, 'Банов в вашей беседе не найдено.');
                                
                            }
                            
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'баны' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
                        
                    }
                    if ($cmd[0] == 'разбан') {
    
                        $rang = $db->query("SELECT unban FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unmute'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($cmd[1] && !$from_id_fwd) {
    
                                $idunban = explode("|", $cmd[1])[0];
                                $iduban = mb_substr($idunban, 3);
    
                                if (!preg_match("/club/", $idunban)) {
    
                                    $checkban = $db->query("SELECT * FROM bans WHERE vk_id = '$iduban' AND peer_id = '$peer_id'")->getNumRows();
    
                                    if ($checkban) {
    
                                        $db->query("DELETE FROM bans WHERE vk_id = '$iduban' AND peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, '@id' . $iduban . ' (Пользователь) разблокирован в данной беседе.');
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, '@id' . $iduban . ' (Пользователь) уже разблокирован', ['disable_mentions' => '1']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Не могу разблокировать сообщество.');
    
                                }
    
                            } elseif (!$cmd[1] && $from_id_fwd) {
    
                                if (!preg_match("/-/", $from_id_fwd)) {
    
                                    $checkban = $db->query("SELECT * FROM bans WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->getNumRows();
    
                                    if ($checkban) {
    
                                        $db->query("DELETE FROM bans WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, '@id' . $from_id_fwd . ' (Пользователь) разблокирован в данной беседе.');
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, '@id' . $from_id_fwd . ' (Пользователь) уже разблокирован', ['disable_mentions' => '1']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Не могу разблокировать сообщество.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Не, ну ты вообще гений мысли писать разбан когда никого не указываешь.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'разбан' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'размут') {
    
                        $rang = $db->query("SELECT unmute FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unmute'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($cmd[1] && !$from_id_fwd) {
    
                                $idunmute = explode("|", $cmd[1])[0];
                                $idumute = mb_substr($idunmute, 3);
    
                                if (!preg_match("/club/", $idunmute)) {
    
                                    $checkmute = $db->query("SELECT * FROM mutes WHERE vk_id = '$idumute' AND peer_id = '$peer_id'")->getNumRows();
    
                                    if ($checkmute) {
    
                                        $db->query("DELETE FROM mutes WHERE vk_id = '$idumute' AND peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, '@id' . $idumute . ' (Пользователь) размучен в данной беседе.');
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, '@id' . $idumute . ' (Пользователь) уже размучен', ['disable_mentions' => '1']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Не могу размутить сообщество.');
    
                                }
    
                            } elseif (!$cmd[1] && $from_id_fwd) {
    
                                if (!preg_match("/-/", $from_id_fwd)) {
    
                                    $checkmute = $db->query("SELECT * FROM mutes WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->getNumRows();
    
                                    if ($checkmute) {
    
                                        $db->query("DELETE FROM mutes WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, '@id' . $from_id_fwd . ' (Пользователь) размучен в данной беседе.');
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, '@id' . $from_id_fwd . ' (Пользователь) уже размучен', ['disable_mentions' => '1']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Не могу размутить сообщество.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Не, ну ты вообще гений мысли писать размут когда никого не указываешь.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'размут' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'муты') {
    
                        $rang = $db->query("SELECT getMutes FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['getMutes'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $getMutes = $db->query("SELECT vk_id, timemute, reason, moder_id FROM mutes WHERE peer_id = '$peer_id'");
                            $checkMutes = $db->query("SELECT vk_id, timemute, reason, moder_id FROM mutes WHERE peer_id = '$peer_id'")->getNumRows();
                            
                            if ($checkMutes) {
    
                                $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
                                
                                while ($names_result = $names_query->fetch_assoc()) {
    
                                    $moder_name = $names_result['name_moder'];
                                    $m_adm_name = $names_result['name_m_adm'];
                                    $adm_name = $names_result['name_adm'];
                                    $s_adm_name = $names_result['name_s_adm'];
                                    $own_name = $names_result['name_own'];
    
                                }
    
                                while ($row = $getMutes->fetch_assoc()) {
    
                                    $rangs = $db->query("SELECT rang FROM users WHERE vk_id = '$row[moder_id]' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
                                    
                                    switch ($rangs) {
    
                                        case '1':
    
                                        $position = $moder_name;
                                        
                                        break;
                                        case '2':
    
                                        $position = $m_adm_name;
    
                                        break;
                                        case '3':
    
                                        $position = $adm_name;
    
                                        break;
                                        case '4':
    
                                        $position = $s_adm_name;
    
                                        break;
                                        case '5':
    
                                        $position = $own_name;
    
                                        break;
    
                                    }
                                    
                                    $time = time();
                                    $time = $time+$row['timemute'];
                                    
                                    $getInfoBans = $vk->request("users.get", ['user_ids' => $row['vk_id']]);
                                    $name = $getInfoBans[0]['first_name'];
                                    $pname = $getInfoBans[0]['last_name'];
                                    
                                    $getModer = $vk->request("users.get", ['user_ids' => $row['moder_id']]);
                                    $namem = $getModer[0]['first_name'];
                                    $pnamem = $getModer[0]['last_name'];
                                    
                                    if ($row['reason'] == '') {
    
                                        $mutesGet .= '@id' . $row['vk_id'] . ' (' . $name . ' ' . $pname . '), мут до ' . date("d.m.Y H:i:s", $time) . PHP_EOL . 'Выдал ' . $position . ' @id' . $row['moder_id'] . ' (' . $namem . ' ' . $pnamem . ')' . PHP_EOL;
                                        
                                    } else {
    
                                        $mutesGet .= '@id' . $row['vk_id'] . ' (' . $name . ' ' . $pname . '), мут до ' . date("d.m.Y H:i:s", $time) . PHP_EOL . 'Причина: ' . $row['reason'] . PHP_EOL . 'Выдал ' . $position . ' @id' . $row['moder_id'] . ' (' . $namem . ' ' . $pnamem . ')' . PHP_EOL;
                                        
                                    }
    
                                }
                                $vk->sendMessage($peer_id, $mutesGet . PHP_EOL);
                                
                            } else {
    
                                $vk->sendMessage($peer_id, 'Мутов в вашей беседе не найдено.');
                                
                            }
                            
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'муты' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
                        
                    }
                    if ($message == '-рассылка') {
    
                        $rang = $db->query("SELECT mailing FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mailing'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET mailing = '0' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_DEN . ' Рассылка новостей для вашей беседы отключена!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-рассылка' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+рассылка') {
    
                        $rang = $db->query("SELECT mailing FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mailing'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET mailing = '1' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Рассылка новостей для вашей беседы включена!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+рассылка' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+silent_mode') {
    
                        $rang = $db->query("SELECT set_silent FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_silent'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET silent_mode = '1' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Уведомления об удалении смс для вашей беседы выключены!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Удаление смс без оповещения «Удалено.»' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '-silent_mode') {
    
                        $rang = $db->query("SELECT set_silent FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_silent'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET silent_mode = '0' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Уведомления об удалении смс для вашей беседы включены!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_DEN . "К сожалению, команда 'Удаление смс без оповещения «Удалено.»' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '-silent_mute') {
    
                        $rang = $db->query("SELECT set_silent FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_silent'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET silent_mute = '0' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_DEN . ' Удаления смс пользователей в муте для вашей беседы выключено!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-silent_mute' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+silent_mute') {
    
                        $rang = $db->query("SELECT set_silent FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_silent'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET silent_mute = '1' WHERE peer_id = '$peer_id'");
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Удаления смс пользователей в муте для вашей беседы включено!');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+silent_mute' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if (explode("\n", $cmd[0])[0] == '!правила') {
    
                        $rang = $db->query("SELECT rules FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rules'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $rules_text_new = explode("\n", $original)[1];
    
                            $rules_text = $cmdobr[1];
    
                            if (!$rules_text && !$rules_text_new) {
    
                                $rules = $db->query("SELECT rules FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['rules'];
    
                                if ($rules == 'none') {
    
                                    $vk->sendMessage($peer_id, BT_DEN . ' Правила не установлены.');
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Текст правил:' . PHP_EOL . $rules);
    
                                }
    
                            } elseif ($rules_text_new) {
    
                                if (mb_strlen($rules_text_new) < 1024) {
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' Текст правил успешно обновлён');
    
                                    $db->query("UPDATE peers SET rules = '$rules_text_new' WHERE peer_id = '$peer_id'");
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Ваши правила превышает 1024 символа!');
    
                                }
    
                            } elseif ($rules_text) {
    
                                if (mb_strlen($rules_text) < 1024) {
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' Текст правил успешно обновлён');
    
                                    $db->query("UPDATE peers SET rules = '$rules_text' WHERE peer_id = '$peer_id'");
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Ваши правила превышает 1024 символа!');
    
                                }
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!правила' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '-уведы') {
    
                        $rang = $db->query("SELECT mentions FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mentions'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET disable_mentions = '1' WHERE peer_id = '$peer_id'");
                            $vk->sendMessage($peer_id, '⛔ Теперь в вашей беседе бот не будет присылать уведомления о том что ранг пользователя ниже требуемого');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-уведы' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+уведы') {
    
                        $rang = $db->query("SELECT mentions FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mentions'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $db->query("UPDATE peers SET disable_mentions = '0' WHERE peer_id = '$peer_id'");
                            $vk->sendMessage($peer_id, BT_SUC . ' Теперь в вашей беседе бот будет присылать уведомления о том что ранг пользователя ниже требуемого');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+уведы' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+акик') {
    
                        $rang = $db->query("SELECT autokick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['autokick'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, '🚫 Теперь все участники, вышедшие с беседы - будут удалены с неё!');
    
                            $db->query("UPDATE peers SET autokick = '1' WHERE peer_id = '$peer_id'");
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+акик' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '-акик') {
    
                        $rang = $db->query("SELECT autokick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['autokick'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Теперь все участники, вышедшие с беседы - не будут удалены с неё!');
    
                            $db->query("UPDATE peers SET autokick = '0' WHERE peer_id = '$peer_id'");
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-акик' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '-гботы') {
    
                        $rang = $db->query("SELECT gbots FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['gbots'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, '🚫 Теперь все участники, добавившие группу - будут автоматически из нее исключены');
    
                            $db->query("UPDATE peers SET gbots = '1' WHERE peer_id = '$peer_id'");
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '-гботы' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '+гботы') {
    
                        $rang = $db->query("SELECT gbots FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['gbots'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Теперь все участники, добавившие группу - не будут автоматически из нее исключены');
    
                            $db->query("UPDATE peers SET gbots = '0' WHERE peer_id = '$peer_id'");
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '+гботы' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!повысить' || $cmd[0] == '!up') {
    
                        $rang = $db->query("SELECT rang_up FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_up'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $id = $cmd[1];
                            $id = explode("|", mb_substr($id ,3))[0];
    
                            if ($from_id_fwd) {
    
                                $check_user_rang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                if ($check_user_rang !== '4') {
    
                                    $db->query("UPDATE users SET rang = '$check_user_rang'+1 WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' Ранг @id' . $from_id_fwd . ' (пользователя) повышен!', ['disable_mentions' => '1']);
    
                                } else {
    
                                    $captcha = rand(1, 100000);
    
                                    $db->query("INSERT INTO capt_own (peer_id, vk_id, own_id, captcha) VALUES ('$peer_id', '$from_id_fwd', '$from_id', '$captcha')");
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' ВНИМАНИЕ!' . PHP_EOL .
                                        'Вы собираетесь поставить @id' . $from_id_fwd . ' (пользователя) на ранг Основателя!' . PHP_EOL .
                                        'Если вы уверены что хотите поставить @id' . $from_id_fwd . '(данного) пользователя на высокий ранг, введите !подтвердить ' . $captcha, ['disable_mentions' => '1']);
    
                                }
    
                            } elseif ($id) {
    
                                if ($id !== $from_id) {
    
                                    $check_user_rang = $db->query("SELECT rang FROM users WHERE vk_id = '$id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                    if ($check_user_rang !== '4') {
    
                                        $db->query("UPDATE users SET rang = '$check_user_rang'+1 WHERE vk_id = '$id' AND peer_id = '$peer_id'");
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' Ранг @id' . $id . ' (пользователя) повышен!', ['disable_mentions' => '1']);
    
                                    } else {
    
                                        $captcha = rand(1, 100000);
    
                                        $db->query("INSERT INTO capt_own (peer_id, vk_id, own_id, captcha) VALUES ('$peer_id', '$id', '$from_id', '$captcha')");
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' ВНИМАНИЕ' . PHP_EOL .
                                            'Вы собираетесь поставить @id' . $id . ' (пользователя) на ранг Основателя!' . PHP_EOL .
                                            'Если вы уверены что хотите поставить @id' . $id . '(данного) пользователя на высокий ранг, введите !подтвердить ' . $captcha, ['disable_mentions' => '1']);
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Повышать себя нельзя :(');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'А кого мне повышать?');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!повышение или !up' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    } elseif ($message == '!понизить' || $message == '!down') {
    
                        $rang = $db->query("SELECT rang_up FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_up'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        $check_user_rang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($from_id_fwd) {
    
                                if ($from_id !== $from_id_fwd) {
    
                                    if ($giverank !== '0') {
    
                                        $db->query("UPDATE users SET rang = '$check_user_rang'-1 WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' Ранг @id' . $from_id_fwd . ' (пользователя) понижен!', ['disable_mentions' => '1']);
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . 'Пользователь уже имеет нулевой ранг');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Не нужно.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'А кого мне понижать?');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!понизить' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'кик') {
    
                        $rang = $db->query("SELECT kick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['kick'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        $kicked = $cmd[1];
                        $clubc = explode("|", $kicked)[0];
                        $cclub = preg_match("/club/", $clubc);
                        $kickd_id = explode("|", mb_substr($kicked ,3))[0];
                        $kickd_id_ub = explode("|", mb_substr($kicked ,5))[0];
    
                        if ($from_id_fwd == $from_id || $kickd_id == $from_id) {
    
                            $vk->sendMessage($peer_id, 'Есть команда !рулетка, испытай удачу!');
                            return;
    
                        }
    
                        if ($from_id_fwd == '-191095367' || $kickd_id_ub == '191095367') {
    
                            $vk->sendMessage($peer_id, 'Неужели ты так хочешь чтобы я ушел отсюда? Если да, то попроси создателя меня кикнуть :(');
                            return;
    
                        }
    
                        if ($rang <= $userrang) {
    
                            if ($from_id_fwd) {
    
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id_fwd]);
    
                            } elseif ($kickd_id && !$cclub) {
    
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $kickd_id]);
    
                            } elseif ($cclub) {
    
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => "-$kickd_id_ub"]);
    
                            } else {
    
                                $vk->sendMessage($peer_id, "Укажи кого кикать");
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Кик' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if (explode("\n", $cmd[0])[0] == '!нп') {
    
                        $rang = $db->query("SELECT new_welcome FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['new_welcome'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $welcome_text_new = explode("\n", $original)[1];
                            $welcome_text = $cmdobrorig[1];
    
                            if (isset($welcome_text)) {
    
                                if (mb_strlen($welcome_text) < 1000) {
    
                                    if (preg_match('/(п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)к(а|a)|п(о|o)дпишит(е|e)(с|c)ь)/', $welcome_text) == false) {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' Текст приветствия успешно обновлён');
    
                                        $db->query("UPDATE peers SET welcome = '$welcome_text' WHERE peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, 'пиздец ты долбоёб братишка, пошел на хуй в глобальный бан, чертила блять');
    
                                        $db->query("INSERT INTO logs (id, vk_id, command, param_command) VALUES (NULL, '$from_id', '!нп', '$welcome_text')");
    
                                        $db->query("INSERT INTO gban (vk_id) VALUES ($from_id)");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Ваше приветствие превышает 1000 символов!');
    
                                }
    
                            } elseif (isset($welcome_text_new)) {
    
                                if (mb_strlen($welcome_text_new) < 1000) {
    
                                    if (preg_match('/(п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)к(а|a)|п(о|o)дпишит(е|e)(с|c)ь)/', $welcome_text_new) == false) {
    
                                        $vk->sendMessage($peer_id, BT_SUC . ' Текст приветствия успешно обновлён');
    
                                        $db->query("UPDATE peers SET welcome = '$welcome_text_new' WHERE peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, 'пиздец ты долбоёб братишка, пошел на хуй в глобальный бан, чертила блять');
    
                                        $db->query("INSERT INTO logs (id, vk_id, command, param_command) VALUES (NULL, '$from_id', '!нп', '$welcome_text_new')");
    
                                        $db->query("INSERT INTO gban (vk_id) VALUES ($from_id)");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Ваше приветствие превышает 1000 символов!');
    
                                }
    
                            } else {
    
                                $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                                if ($mentions !== '1') {
    
                                    $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!нп' ограничена для вас, так как ваш ранг ниже требуемого.");
                                    return;
    
                                }
    
                            }
    
                        }
    
                    }
                    if ($message == '!обновить') {
    
                        $rang = $db->query("SELECT `update` FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['update'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $members = $vk->request('messages.getConversationMembers', ['peer_id' => $peer_id]);
    
                            foreach ($members['items'] as $key) {
    
                                $member_ids = $key['member_id'];
    
                                $rangmembers = $db->query("SELECT rang FROM users WHERE vk_id = '$member_ids' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                $member_ids_admin = $vk->isAdmin($member_ids, $peer_id);
    
                                $check1 = $db->query("SELECT vk_id FROM users WHERE vk_id = '$member_ids' AND peer_id = '$peer_id'")->getNumRows();
    
                                if (!$check1 && $member_ids_admin !== 'owner' && $member_ids_admin !== 'admin' && !preg_match('/-/', $member_ids)) {
    
                                    $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member_ids', '0', '$peer_id')");
    
                                } elseif (!$check1 && $member_ids_admin == 'admin' && !preg_match('/-/', $member_ids)) {
    
                                    $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member_ids', '4', '$peer_id')");
    
                                } elseif (!$check1 && $member_ids_admin == 'owner' && !preg_match('/-/', $member_ids)) {
    
                                    $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$member_ids', '5', '$peer_id')");
    
                                }
                                if ($check1 && $member_ids_admin !== 'owner' && $member_ids_admin !== 'admin' && !preg_match('/[1-5]/', $rangmembers)) {
    
                                    $db->query("UPDATE users SET rang = '0' WHERE vk_id = '$member_ids' AND peer_id = '$peer_id'");
    
                                } elseif ($check1 && $member_ids_admin == 'admin' && $rangmembers == '0') {
    
                                    $db->query("UPDATE users SET rang = '4' WHERE vk_id = '$member_ids' AND peer_id = '$peer_id'");
    
                                } elseif ($check1 && $member_ids_admin == 'owner' && $rangmembers == '0') {
    
                                    $db->query("UPDATE users SET rang = '5' WHERE vk_id = '$member_ids' AND peer_id = '$peer_id'");
    
                                }
    
                                $checkverifyb = $db->query("SELECT peer_id FROM rang_commands WHERE peer_id = '$peer_id'")->getNumRows();
    
                                if (!$checkverifyb) {
    
                                    $db->query("INSERT INTO rang_commands (peer_id) VALUES ('$peer_id')");
    
                                }
    
                            }
                            $vk->sendMessage($peer_id, 'Успешно обновлено');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!обновить' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!нранг') {
    
                        $rang = $db->query("SELECT set_new_rang FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_new_rang'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $cmds = $cmd[1];
                            $rangcmd = $cmd[2];
    
                            switch ($cmds) {
    
                                case '1':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT kick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['kick'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET kick = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Исключение из беседы (кик)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Исключение из беседы (кик)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET kick = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Исключение из беседы (кик)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Исключение из беседы (кик)'");
    
                                }
                                break;
                                case '2':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT new_welcome FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['new_welcome'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET new_welcome = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Установка новых правил (!нп)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Установка новых правил (!нп)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET new_welcome = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Установка новых правил (!нп)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Установка новых правил (!нп)'");
    
                                }
                                break;
                                case '3':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT autokick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['autokick'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET autokick = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Включение / Отключение авто - исключения при выходе ((+/-)акик)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Включение / Отключение авто - исключения при выходе ((+/-)акик)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET autokick = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Включение / Отключение авто - исключения при выходе ((+/-)акик)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Включение / Отключение авто - исключения при выходе ((+/-)акик)'");
    
                                }
                                break;
                                case '4':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT set_new_rang FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_new_rang'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET set_new_rang = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Установка нового ранга команды (!нранг)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Установка нового ранга команды (!нранг)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET set_new_rang = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Установка нового ранга команды (!нранг)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Установка нового ранга команды (!нранг)'");
    
                                }
                                break;
                                case '5':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT rang_up FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_up'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET rang_up = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Повышение ранга пользователя (!up / !повышение)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Повышение ранга пользователя (!up / !повышение)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET rang_up = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Повышение ранга пользователя (!up / !повышение)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Повышение ранга пользователя (!up / !повышение)'");
    
                                }
                                break;
                                case '6':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT rang_dw FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_dw'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET rang_dw = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Понижение ранга пользователя (!понизить)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Понижение ранга пользователя (!понизить)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET rang_dw = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Понижение ранга пользователя (!понизить)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Понижение ранга пользователя (!понизить)'");
    
                                }
                                break;
                                case '7':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT `update` FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['update'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET `update` = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Обновление чата (!обновить)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Обновление чата (!обновить)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET `update` = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Обновление чата (!обновить)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Обновление чата (!обновить)'");
    
                                }
                                break;
                                case '8':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT `rang_up` FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_up'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET rang_up = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Мгновенное повышение ранга пользователя (!мгнранг)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Мгновенное повышение ранга пользователя (!мгнранг)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET rang_up = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Мгновенное повышение ранга пользователя (!мгнранг)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Мгновенное повышение ранга пользователя (!мгнранг)'");
    
                                }
                                break;
                                case '9':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT gbots FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['gbots'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET gbots = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Включение / Отключение добавления групп ((+/-)гботы)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Включение / Отключение добавления групп ((+/-)гботы)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET gbots = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Включение / Отключение добавления групп ((+/-)гботы)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Включение / Отключение добавления групп ((+/-)гботы)'");
    
                                }
                                break;
                                case '10':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT give_warn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET give_warn = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Выдача предупреждений (warn)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Выдача предупреждений (warn)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET give_warn = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Выдача предупреждений (warn)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Выдача предупреждений (warn)'");
    
                                }
                                break;
                                case '11':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT unwarn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET unwarn = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Снятие предупреждений (unwarn)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Снятие предупреждений (unwarn)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET unwarn = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Снятие предупреждений (unwarn)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Снятие предупреждений (unwarn)'");
    
                                }
                                break;
                                case '12':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT warns FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET warns = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Список предупреждений (warns)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Список предупреждений (warns)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET warns = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Список предупреждений (warns)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Список предупреждений (warns)'");
    
                                }
                                break;
                                case '13':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT ping FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET ping = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Проверка работы бота (пинг / пив)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Проверка работы бота (пинг / пив)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET ping = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Проверка работы бота (пинг / пив)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Проверка работы бота (пинг / пив)'");
    
                                }
                                break;
                                case '14':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT commands FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['commands'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET commands = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Вызов списка команд с рангами (/рк)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Вызов списка команд с рангами (/рк)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET commands = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Вызов списка команд с рангами (/рк)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Вызов списка команд с рангами (/рк)'");
    
                                }
                                break;
                                case '15':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT welcome FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['welcome'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET welcome = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Вызов приветствия (!приветствие)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Вызов приветствия (!приветствие)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET welcome = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Вызов приветствия (!приветствие)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Вызов приветствия (!приветствие)'");
    
                                }
                                break;
                                case '16':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT roulette FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['roulette'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET roulette = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Рулетка (!рулетка)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Рулетка (!рулетка)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET roulette = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Рулетка (!рулетка)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Рулетка (!рулетка)'");
    
                                }
                                break;
                                case '17':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT `write` FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['write'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET `write` = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Напиши ((! или /)напиши / (! или /)say / (! или /)скажи / (! или /)произнеси)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Напиши ((! или /)напиши / (! или /)say / (! или /)скажи / (! или /)произнеси)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET `write` = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Напиши ((! или /)напиши / (! или /)say / (! или /)скажи / (! или /)произнеси)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Напиши ((! или /)напиши / (! или /)say / (! или /)скажи / (! или /)произнеси)'");
    
                                }
                                break;
                                case '18':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT easter_egg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['easter_egg'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET easter_egg = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Подразделу 'Пасхалки' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для подраздела 'Пасхалки' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET easter_egg = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Подраздел 'Пасхалки' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для подраздела 'Пасхалки'");
    
                                }
                                break;
                                case '19':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT whoami FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['whoami'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET whoami = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Анкета пользователя (Кто я / Кто ты)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команд 'Анкета пользователя (Кто я / Кто ты)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET whoami = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команды 'Анкета пользователя (Кто я / Кто ты)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для команд 'Анкета пользователя (Кто я / Кто ты)'");
    
                                }
                                break;
                                case '20':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT snick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['snick'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET snick = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Установка нового ник - нейма (snick)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Установка нового ник - нейма (snick)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET snick = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Установка нового ник - нейма (snick)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Установка нового ник - нейма (snick)'");
    
                                }
                                break;
                                case '21':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT rules FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rules'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET rules = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Установка / просмотр правил (!правила)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Установка / просмотр правил (!правила)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET rules = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Установка / просмотр правил (!правила)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Установка / просмотр правил (!правила)'");
    
                                }
                                break;
                                case '22':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT mentions FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mentions'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET mentions = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Отключение / включение уведомлений о недостаточном ранге ((-/+)уведы)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Отключение / включение уведомлений о недостаточном ранге ((-/+)уведы)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET mentions = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Отключение / включение уведомлений о недостаточном ранге ((-/+)уведы)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Отключение / включение уведомлений о недостаточном ранге ((-/+)уведы)'");
    
                                }
                                break;
                                case '23':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT mailing FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mailing'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET mailing = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Отключение / включение рассылки новостей ((-/+)рассылка)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Отключение / включение рассылки новостей ((-/+)рассылка)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET mailing = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Отключение / включение рассылки новостей ((-/+)рассылка)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Отключение / включение рассылки новостей ((-/+)рассылка)'");
    
                                }
                                break;
                                case '24':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT toplist FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['toplist'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET toplist = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Просмотр активности (статистика) участников (топ)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Просмотр активности (статистика) участников (топ)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET toplist = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Просмотр активности (статистика) участников (топ)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Просмотр активности (статистика) участников (топ)'");
    
                                }
                                break;
                                case '25':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT ban FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ban'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET ban = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Выдача блокировки (бан)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Выдача блокировки (бан)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET ban = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Выдача блокировки (бан)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Выдача блокировки (бан)'");
    
                                }
                                break;
                                case '26':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT unban FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unban'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET unban = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Снятие блокировки (разбан)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Снятие блокировки (разбан)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET unban = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Снятие блокировки (разбан)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Снятие блокировки (разбан)'");
    
                                }
                                break;
                                case '27':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT mute FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['mute'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET mute = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Ограничение слова (мут)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Ограничение слова (мут)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET mute = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Ограничение слова (мут)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Ограничение слова (мут)'");
    
                                }
                                break;
                                case '28':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT unmute FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unmute'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET unmute = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Снятие ограничения слова (размут)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Снятие ограничения слова (размут)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET unmute = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Снятие ограничения слова (размут)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Снятие ограничения слова (размут)'");
    
                                }
                                break;
                                case '29':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT getBans FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['getBans'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET getBans = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Просмотр списка банов (баны)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Просмотр списка банов (баны)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET getBans = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Просмотр списка банов (баны)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Просмотр списка банов (баны)'");
    
                                }
                                break;
                                case '30':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT getMutes FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['getMutes'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET getMutes = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Просмотр списка мутов (муты)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Просмотр списка мутов (муты)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET getMutes = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Просмотр списка мутов (муты)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Просмотр списка мутов (муты)'");
    
                                }
                                break;
                                case '31':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT delete_msg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['delete_msg'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET delete_msg = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Удаление сообщений (-del)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Удаление сообщений (-del)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET delete_msg = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Удаление сообщений (-del)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Удаление сообщений (-del)'");
    
                                }
                                break;
                                case '32':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT set_calls FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_calls'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET set_calls = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Привязка чата (+calls)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Привязка чата (+calls)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET set_calls = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Привязка чата (+calls)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Привязка чата (+calls)'");
    
                                }
                                break;
                                case '33':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT set_silent FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['set_silent'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET set_silent = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Беззвучный мут (+silent_mute) / Удаление смс без оповещения «Удалено.» (+silent_mode)' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Беззвучный мут (+silent_mute) / Удаление смс без оповещения «Удалено.» (+silent_mode)' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET set_silent = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Беззвучный мут (+silent_mute) / Удаление смс без оповещения «Удалено.» (+silent_mode)' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Беззвучный мут (+silent_mute) / Удаление смс без оповещения «Удалено.» (+silent_mode)'");
    
                                }
                                break;
                                case '34':
    
                                if ($rangcmd) {
    
                                    if ($rangcmd !== 'вкл') {
    
                                        if (is_numeric($rangcmd)) {
    
                                            $arang = $db->query("SELECT execute_android_command FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['execute_android_command'];
    
                                            if ($rangcmd !== $arang) {
    
                                                $db->query("UPDATE rang_commands SET execute_android_command = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, "Команде 'Выполнение действий от лица Андроида (!а {доступная команда андроидам}).' был установлен ранг " . $rangcmd);
    
                                            } else {
    
                                                $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Выполнение действий от лица Андроида (!а {доступная команда андроидам}).' был вызван не правильно");
    
                                        }
    
                                    } else {
    
                                        $db->query("UPDATE rang_commands SET execute_android_command = '0' WHERE peer_id = '$peer_id'");
                                        $vk->sendMessage($peer_id, "Команда 'Выполнение действий от лица Андроида (!а {доступная команда андроидам}).' была включена для всех!");
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Выполнение действий от лица Андроида (!а {доступная команда андроидам}).'");
    
                                }
                                break;
                                case '35':
    
                                    if ($rangcmd) {
        
                                        if ($rangcmd !== 'вкл') {
        
                                            if (is_numeric($rangcmd)) {
        
                                                $arang = $db->query("SELECT return_user FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['execute_android_command'];
        
                                                if ($rangcmd !== $arang) {
        
                                                    $db->query("UPDATE rang_commands SET return_user = '$rangcmd' WHERE peer_id = '$peer_id'");
                                                    $vk->sendMessage($peer_id, "Команде 'Возвращение пользователя с помощью команды !обратно.' был установлен ранг " . $rangcmd);
        
                                                } else {
        
                                                    $vk->sendMessage($peer_id, "Вы пытались установить ранг на существующий");
        
                                                }
        
                                            } else {
        
                                                $vk->sendMessage($peer_id, "Один из параметров (ранг) для команды 'Возвращение пользователя с помощью команды !обратно.' был вызван не правильно");
        
                                            }
        
                                        } else {
        
                                            $db->query("UPDATE rang_commands SET return_user = '0' WHERE peer_id = '$peer_id'");
                                            $vk->sendMessage($peer_id, "Команда 'Возвращение пользователя с помощью команды !обратно.' была включена для всех!");
        
                                        }
        
                                    } else {
        
                                        $vk->sendMessage($peer_id, "Не указан ранг для установления команды 'Возвращение пользователя с помощью команды !обратно.'");
        
                                    }
                                    break;

                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!нранг' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!напиши' || $cmd[0] == '!say' || $cmd[0] == '!скажи' || $cmd[0] == '!произнеси' || $cmd[0] == '/напиши' || $cmd[0] == '/say' || $cmd[0] == '/скажи' || $cmd[0] == '/произнеси') {
    
                        $rang = $db->query("SELECT `write` FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['write'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($write = $cmdobrorig[1]) {
    
                                if (preg_match('/(п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)к(а|a)|п(о|o)дпишит(е|e)(с|c)ь)/', $write) == false || $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows() || $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows()) {
    
                                    $writefl = $vk->request("users.get", ['user_ids' => $from_id]);
                                    $name = $writefl[0]['first_name'];
                                    $pname = $writefl[0]['last_name'];
    
                                    $vk->sendMessage($peer_id, '@id' . $from_id . ' (' . $name . ' ' . $pname . '): ' . $write, ['disable_mentions' => '1']);
    
                                } else {
    
                                    $writefl = $vk->request("users.get", ['user_ids' => $from_id]);
                                    $name = $writefl[0]['first_name'];
                                    $pname = $writefl[0]['last_name'];
    
                                    $vk->sendMessage($peer_id, 'Не скажу.');
    
                                    $vk->sendMessage(2000000317, '<WARNING> @id' . $from_id . ' (' . $name . ' ' . $pname . ') попытался с помощью команды "написание текста от бота" написать предложении с просьбой подписки. Занес его в глобальный бан, так же, в случае ложного срабатывания предоставлю текст: ' . $write . PHP_EOL . 'В случае если срабатывание ложное: гразбан @id' . $from_id);
    
                                    $db->query("INSERT INTO logs (id, vk_id, command, param_command) VALUES (NULL, '$from_id', '.напиши', '$write')");
    
                                    $db->query("INSERT INTO gban (vk_id) VALUES ($from_id)");
    
                                }
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Напиши' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == '!приветствие') {
    
                        $rang = $db->query("SELECT welcome FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['welcome'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $welcome_text = $db->query("SELECT welcome FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['welcome'];
    
                            $vk->sendMessage($peer_id, 'Текст приветствия: ' . $welcome_text);
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!приветствие' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($message == '/рк') {
    
                        $rang = $db->query("SELECT commands FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['commands'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $rangs = $db->query("SELECT * FROM rang_commands WHERE peer_id = '$peer_id'");
    
                            while ($res = $rangs->fetch_assoc()) {
    
                                $kick_rang = $res['kick'];
                                $nw_rang = $res['new_welcome'];
                                $atkick_rang = $res['autokick'];
                                $snr_rang = $res['set_new_rang'];
                                $r_up_rang = $res['rang_up'];
                                $r_dw_rang = $res['rang_dw'];
                                $update_rang = $res['update'];
                                $ping_rang = $res['ping'];
                                $com_rang = $res['commands'];
                                $wel_rang = $res['welcome'];
                                $roul_rang = $res['roulette'];
                                $write_rang = $res['write'];
                                $eaeggs_rang = $res['easter_egg'];
                                $whoami_rang = $res['whoami'];
                                $gbots_rang = $res['gbots'];
                                $givewarn_rang = $res['give_warn'];
                                $unwarn_rang = $res['unwarn'];
                                $awarns_rang = $res['warns'];
                                $rules_rang = $res['rules'];
                                $mentions_rang = $res['mentions'];
                                $snick_rang = $res['snick'];
                                $mailing_rang = $res['mailing'];
                                $toplist = $res['toplist'];
                                $banrang = $res['ban'];
                                $unbanrang = $res['unban'];
                                $muterang = $res['mute'];
                                $unmuterang = $res['unmute'];
                                $getBanrang = $res['getBans'];
                                $getMuterang = $res['getMutes'];
                                $deletemsgrang = $res['delete_msg'];
                                $setcallsrang = $res['set_calls'];
                                $setsilentrang = $res['set_silent'];
                                $execandroidcmd = $res['execute_android_command'];
                                $returnuserrang = $res['return_user'];
    
                            }
    
                            $vk->sendMessage($peer_id, 'Ранги команд (номер команды - команда - ранг)' . PHP_EOL . PHP_EOL .
                                'Раздел \'Настройки беседы\':' . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Приветствие\':' . PHP_EOL .
                                '-&#2;-> 2. Установка нового приветствия (!нп) ' . $nw_rang . PHP_EOL .
                                '-&#2;-> 15. Вызов приветствия (!приветствие) ' . $wel_rang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Защита беседы от рейд атак\':' . PHP_EOL .
                                '-&#2;-> 9. Включение / Отключение добавления групп ((+/-)гботы) ' . $gbots_rang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Прочее\':' . PHP_EOL .
                                '-&#2;-> 3. Включение / Отключение авто - исключения при выходе ((+/-)акик) ' . $atkick_rang . PHP_EOL .
                                '-&#2;-> 7. Обновление чата (!обновить) ' . $update_rang . PHP_EOL .
                                '-&#2;-> 21. Установка / просмотр правил (!правила) ' . $rules_rang . PHP_EOL .
                                '-&#2;-> 23. Отключение / включение рассылки новостей ((-/+)рассылка) ' . $mailing_rang . PHP_EOL . PHP_EOL .
                                'Раздел \'Администрирование\':' . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Исключение / блокировка пользователей\':' . PHP_EOL .
                                '-&#2;-> 1. Исключение из беседы (кик) ' . $kick_rang . PHP_EOL .
                                '-&#2;-> 25. Выдача блокировки (бан) ' . $banrang . PHP_EOL .
                                '-&#2;-> 26. Снятие блокировки (разбан) ' . $unbanrang . PHP_EOL .
                                '-&#2;-> 29. Просмотр списка банов (баны) ' . $getBanrang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Ограничение слова\':' . PHP_EOL .
                                '-&#2;-> 27. Выдача ограничения слова (мут) ' . $muterang . PHP_EOL .
                                '-&#2;-> 28. Снятие ограничения слова (размут) ' . $unmuterang . PHP_EOL .
                                '-&#2;-> 30. Просмотр списка мутов (муты) ' . $getMuterang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Предупреждения\':' . PHP_EOL .
                                '-&#2;-> 10. Выдача предупреждений (warn) ' . $givewarn_rang . PHP_EOL .
                                '-&#2;-> 11. Снятие предупреждений (unwarn) ' . $unwarn_rang . PHP_EOL .
                                '-&#2;-> 12. Список предупреждений (warns) ' . $awarns_rang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Ранги\':' . PHP_EOL .
                                '-&#2;-> 4. Установка нового ранга команды (!нранг) ' . $snr_rang . PHP_EOL .
                                '-&#2;-> 5. Повышение ранга пользователя (!up / !повышение) ' . $r_up_rang . PHP_EOL .
                                '-&#2;-> 6. Понижение ранга пользователя (!понизить) ' . $r_dw_rang . PHP_EOL .
                                '-&#2;-> 8. Мгновенное повышение ранга пользователя (!мгнранг) ' . $r_up_rang . PHP_EOL .
                                '-&#2;-> 14. Вызов списка команд с рангами (/рк) ' . $com_rang . PHP_EOL . PHP_EOL .
                                'Раздел \'Пользовательское\':' . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Настройка пользователя\':' . PHP_EOL .
                                '-&#2;-> 19. Анкета пользователя (кто я / кто ты) ' . $whoami_rang . PHP_EOL .
                                '-&#2;-> 20. Установка нового ник - нейма (snick) ' . $snick_rang . PHP_EOL . PHP_EOL .
                                '-> Подраздел \'Прочее\':' . PHP_EOL .
                                '-&#2;-> 13. Проверка работы бота (пинг / пив) ' . $ping_rang . PHP_EOL .
                                '-&#2;-> 16. Рулетка (!рулетка) ' . $roul_rang . PHP_EOL .
                                '-&#2;-> 18. Пасхалки ' . $eaeggs_rang . PHP_EOL .
                                '-&#2;-> 24. Просмотр активности (статистика) участников (топ) ' . $toplist . PHP_EOL . PHP_EOL .
                                'Раздел \'Андроиды\':' . PHP_EOL . PHP_EOL .
                                '-&#2;-> 31. Удаление сообщений (-del) ' . $deletemsgrang . PHP_EOL .
                                '-&#2;-> 32. Привязка чата (+calls) ' . $setcallsrang . PHP_EOL .
                                '-&#2;-> 33. Беззвучный мут (+silent_mute) / Удаление смс без оповещения «Удалено.» (+silent_mode) ' . $setsilentrang . PHP_EOL .
                                '-&#2;-> 34. Выполнение действий от лица Андроида (!а {доступная команда андроидам}) ' . $execandroidcmd . PHP_EOL .
                                '-&#2;-> 35. Возвращение пользователя с помощью команды !обратно ' . $returnuserrang);
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '/рк' ограничена для вас, так как ваш ранг ниже требуемого.");
                                return;
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!мгнранг') {
    
                        $rang = $db->query("SELECT rang_up FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['rang_up'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $from_id_up = $cmd[1];
                            $rang = $cmd[2];
    
                            $from_id_up = explode("|", mb_substr($from_id_up ,3))[0];
    
                            if ($from_id_up) {
    
                                $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
    
                                while ($names_result = $names_query->fetch_assoc()) {
    
                                    $moder_name = $names_result['name_moder'];
                                    $m_adm_name = $names_result['name_m_adm'];
                                    $adm_name = $names_result['name_adm'];
                                    $s_adm_name = $names_result['name_s_adm'];
                                    $own_name = $names_result['name_own'];
    
                                }
    
                                switch ($rang) {
    
                                    case '0':
    
                                    $vk->sendMessage($peer_id, 'Ранг @id' . $from_id_up . ' (пользователя) был установлен на Обычный пользователь', ['disable_mentions' => '1']);
    
                                    $db->query("UPDATE users SET rang = '0' WHERE vk_id = '$from_id_up' AND peer_id = '$peer_id'");
    
                                    break;
                                    case '1':
    
                                    if ($userrang > $rang) {
    
                                        $vk->sendMessage($peer_id, 'Ранг @id' . $from_id_up . ' (пользователя) был установлен на ' . $moder_name, ['disable_mentions' => '1']);
    
                                        $db->query("UPDATE users SET rang = '1' WHERE vk_id = '$from_id_up' AND peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Вашего ранга недостаточно чтобы повысить данного пользователя');
    
                                    }
    
                                    break;
                                    case '2':
    
                                    if ($userrang > $rang) {
    
                                        $vk->sendMessage($peer_id, 'Ранг @id' . $from_id_up . ' (пользователя) был установлен на ' . $m_adm_name, ['disable_mentions' => '1']);
    
                                        $db->query("UPDATE users SET rang = '2' WHERE vk_id = '$from_id_up' AND peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Вашего ранга недостаточно чтобы повысить данного пользователя');
    
                                    }
    
                                    break;
                                    case '3':
    
                                    if ($userrang > $rang) {
    
                                        $vk->sendMessage($peer_id, 'Ранг @id' . $from_id_up . ' (пользователя) был установлен на ' . $adm_name, ['disable_mentions' => '1']);
    
                                        $db->query("UPDATE users SET rang = '3' WHERE vk_id = '$from_id_up' AND peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Вашего ранга недостаточно чтобы повысить данного пользователя');
    
                                    }
    
                                    break;
                                    case '4':
    
                                    if ($userrang > $rang) {
    
                                        $vk->sendMessage($peer_id, 'Ранг @id' . $from_id_up . ' (пользователя) был установлен на ' . $s_adm_name, ['disable_mentions' => '1']);
    
                                        $db->query("UPDATE users SET rang = '4' WHERE vk_id = '$from_id_up' AND peer_id = '$peer_id'");
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Вашего ранга недостаточно чтобы повысить данного пользователя');
    
                                    }
    
                                    break;
                                    case '5':
    
                                    if ($userrang = $rang) {
    
                                        $captcha = rand(1, 100000);
    
                                        $db->query("INSERT INTO capt_own (peer_id, vk_id, own_id, captcha) VALUES ('$peer_id', '$from_id_up', '$from_id', '$captcha')");
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' ВНИМАНИЕ!' . PHP_EOL .
                                            'Вы собираетесь поставить @id' . $from_id_up . '(пользователя) на ранг Основателя!' . PHP_EOL .
                                            'Если вы уверены что хотите поставить @id' . $from_id_up . '(данного) пользователя на высокий ранг введите !подтвердить ' . $captcha, ['disable_mentions' => '1']);
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Вашего ранга недостаточно чтобы повысить данного пользователя');
    
                                    }
    
                                    break;
    
                                }
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!мгнранг' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!подтвердить') {
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($userrang == '5') {
    
                            $captcha = $cmd[1];
    
                            $own_id = $db->query("SELECT vk_id FROM capt_own WHERE captcha = '$captcha'")->fetch_assoc()['vk_id'];
    
                            $captcha_sql = $db->query("SELECT captcha FROM capt_own WHERE vk_id = '$own_id' AND own_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['captcha'];
    
                            if ($captcha == $captcha_sql && is_numeric($captcha)) {
    
                                $vk->sendMessage($peer_id, '@id' . $own_id . '(Пользователю) установлен ранг Основатель', ['disable_mentions' => '1']);
                                $db->query("UPDATE users SET rang = '5' WHERE vk_id = '$own_id' AND peer_id = '$peer_id'");
                                $db->query("DELETE FROM capt_own WHERE vk_id = '$own_id' AND own_id = '$from_id' AND peer_id = '$peer_id'");
    
                            } elseif ($captcha !== $captcha_sql) {
    
                                $vk->sendMessage($peer_id, 'Капча введена неверно! Сбрасываю');
                                $db->query("DELETE FROM capt_own WHERE peer_id = '$peer_id' AND own_id = '$from_id'");
    
                            } elseif (!is_numeric($captcha)) {
    
                                $vk->sendMessage($peer_id, 'Капча должна состоять только из цифр.');
    
                            }
    
                        }
    
                    }
                    if ($message == '!вс') {
    
                        $check = $vk->isAdmin($from_id, $peer_id);
    
                        if ($check == 'owner') {
    
                            $vk->sendMessage($peer_id, 'Создатель успешно восстановлен!');
                            $query = "UPDATE users SET rang = '5' WHERE vk_id = '$from_id' AND peer_id = '$peer_id'";
                            $db->query($query);
    
                        }
    
                    }
                    if (explode("\n", $cmd[0])[0] == 'warn') {
    
                        $rang = $db->query("SELECT give_warn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['give_warn'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $idd = explode("|", mb_substr(explode("\n", $cmd[1])[0], 1))[0];
                            $warn_id_user = mb_substr($idd ,2);
    
                            if (explode("\n", $cmd[1])[0] && !$from_id_fwd) {
    
                                if ($warn_id_user && !preg_match("/club/", $idd)) {
    
                                    $limit = $db->query("SELECT limit_warns FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['limit_warns'];
                                    $all_warns_user = $db->query("SELECT warns FROM users WHERE vk_id = '$warn_id_user' AND peer_id = '$peer_id'")->fetch_assoc()['warns'];
                                    $awarn = $all_warns_user+1;
    
                                    if ($warn_id !== $from_id) {
    
                                        $peerrang = $db->query("SELECT rang FROM users WHERE vk_id = '$warn_id_user' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                        if ($userrang > $peerrang && $userrang !== $peerrang) {
    
                                            if ($awarn < $limit) {
    
                                                $reason = explode("\n", $original)[1];
    
                                                if ($reason) {
    
                                                    $db->query("UPDATE users SET warns = $awarn WHERE vk_id = '$warn_id_user' AND peer_id = '$peer_id'");
                                                    $vk->sendMessage($peer_id, BT_WARN . 'У @id' . $warn_id_user . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений' . PHP_EOL . 'Причина: ' . $reason, ['disable_mentions' => '1']);
    
                                                } else {
    
                                                    $db->query("UPDATE users SET warns = $awarn WHERE vk_id = '$warn_id_user' AND peer_id = '$peer_id'");
                                                    $vk->sendMessage($peer_id, BT_WARN . 'У @id' . $warn_id_user . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений', ['disable_mentions' => '1']);
    
                                                }
    
                                            } elseif ($awarn >= $limit) {
    
                                                $vk->sendMessage($peer_id, BT_WARN . ' К сожалению, @id' . $warn_id_user . ' (данный) пользователь получает последнее предупреждение и вылетает из конференции', ['disable_mentions' => '1']);
                                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'user_id' => $warn_id_user]);
                                                $db->query("UPDATE users SET warns = '0' WHERE vk_id = '$warn_id_user' AND peer_id = '$peer_id'");
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, 'Вы не можете выдать предупреждение пользователю ранг которого равен или выше вашего.');
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать варн.');
                                        exit;
    
                                    }
    
                                } elseif ($warn_id_user && preg_match("/club/", $idd)) {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать варн группе.');
                                    exit;
    
                                }
    
                            } elseif (!$cmd[1] && !$from_id_fwd) {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Не указан пользователь.');
                                exit;
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'warn' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if (explode("\n", $cmd[0])[0] == 'warn') {
    
                        $rang = $db->query("SELECT give_warn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['give_warn'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($from_id_fwd && !preg_match("/-/", $from_id_fwd)) {
    
                                $limit = $db->query("SELECT limit_warns FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['limit_warns'];
                                $all_warns_user = $db->query("SELECT warns FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['warns'];
                                $awarn = $all_warns_user+1;
    
                                if ($from_id_fwd !== $from_id) {
    
                                    $peerrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                    if ($userrang > $peerrang && $userrang !== $peerrang) {
    
                                        if ($awarn < $limit) {
    
                                            $reason = explode("\n", $original)[1];
    
                                            if ($reason) {
    
                                                $db->query("UPDATE users SET warns = $awarn WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, BT_WARN . 'У @id' . $from_id_fwd . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений' . PHP_EOL . 'Причина: ' . $reason, ['disable_mentions' => '1']);
    
                                            } else {
    
                                                $db->query("UPDATE users SET warns = $awarn WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
                                                $vk->sendMessage($peer_id, BT_WARN . 'У @id' . $from_id_fwd . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений', ['disable_mentions' => '1']);
    
                                            }
    
                                        } elseif ($awarn >= $limit) {
    
                                            $vk->sendMessage($peer_id, BT_WARN . ' К сожалению, @id' . $from_id_fwd . ' (данный) пользователь получает последнее предупреждение и вылетает из конференции', ['disable_mentions' => '1']);
                                            $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'user_id' => $from_id_fwd]);
                                            $db->query("UPDATE users SET warns = '0' WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, 'Вы не можете выдать предупреждение пользователю ранг которого равен или выше вашего.');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Не могу выдать варн.');
                                    exit;
    
                                }
    
                            } elseif ($from_id_fwd && preg_match("/-/", $from_id_fwd)) {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Не могу выдать варн группе.');
                                exit;
    
                            } elseif (!$from_id_fwd && !explode("\n", $cmd[1])[0]) {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Не указан пользователь.');
                                exit;
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'warn' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'unwarn') {
    
                        $rang = $db->query("SELECT unwarn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unwarn'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $limit = $db->query("SELECT limit_warns FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['limit_warns'];
                            $all_warns_user = $db->query("SELECT warns FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['warns'];
                            $awarn = $all_warns_user-1;
    
                            if ($from_id_fwd) {
    
                                if ($awarn >= '0') {
    
                                    $db->query("UPDATE users SET warns = '$awarn' WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
                                    $vk->sendMessage($peer_id, BT_SUC . ' У @id' . $from_id_fwd . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений', ['disable_mentions' => '1']);
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Некуда больше убирать предупреждения.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Не указан пользователь.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'unwarn' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'unwarn') {
    
                        $rang = $db->query("SELECT unwarn FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['unwarn'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $unwarn_idd = $cmd[1];
                            $unwarn_idd = explode("|", $unwarn_idd)[0];
                            $unwarn_id = mb_substr($unwarn_idd ,3);
    
                            $limit = $db->query("SELECT limit_warns FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['limit_warns'];
                            $all_warns_user = $db->query("SELECT warns FROM users WHERE vk_id = '$unwarn_id' AND peer_id = '$peer_id'")->fetch_assoc()['warns'];
                            $awarn = $all_warns_user-1;
    
                            if ($unwarn_id && !$from_id_fwd) {
    
                                if ($awarn >= '0') {
    
                                    $db->query("UPDATE users SET warns = '$awarn' WHERE vk_id = '$unwarn_id' AND peer_id = '$peer_id'");
                                    $vk->sendMessage($peer_id, BT_SUC . ' У @id' . $unwarn_id . ' (пользователя) теперь ' . $awarn . '/' . $limit . ' предупреждений', ['disable_mentions' => '1']);
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Некуда больше убирать предупреждения.');
    
                                }
    
                            } elseif (!$unwarn_id && !$from_id_fwd) {
    
                                $vk->sendMessage($peer_id, 'Не указан пользователь.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'unwarn' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'warns') {
    
                        $rang = $db->query("SELECT warns FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['warns'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $all_warns = $db->query("SELECT vk_id, warns FROM users WHERE warns > '0' AND peer_id = '$peer_id'");
    
                            while ($warns_all_res = $all_warns->fetch_assoc()) {
    
                                $warns_all = $warns_all_res['warns'];
                                $warns_limit_peer = $db->query("SELECT limit_warns FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['limit_warns'];
                                $user = $warns_all_res['vk_id'];
    
                                $getInfoWarns = $vk->request("users.get", ['user_ids' => $user]);
                                $name = $getInfoWarns[0]['first_name'];
                                $pname = $getInfoWarns[0]['last_name'];
    
                                $list_of_warns .= '@id' . $user . ' (' . $name . ' ' . $pname . ') - ' . $warns_all . '/' . $warns_limit_peer . PHP_EOL;
    
                            }
    
                            if ($list_of_warns) {
    
                                $vk->sendMessage($peer_id, 'Пользователи, у которых есть предупреждения: ' . PHP_EOL . $list_of_warns, ['disable_mentions' => '1']);
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'В беседе мир и спокойствее, поэтому ни у кого предупреждений нету. :)');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'warns' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    // Пасхалки
                    if ($message == 'казино') {
    
                        $rang = $db->query("SELECT easter_egg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['easter_egg'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, 'Проблемы с доступом в джойказино? Ебаный в рот этого казино, блять. Ты кто такой, сука? Чтоб это делать? Вы че, дебилы? Вы че, ебанутые? Вы внатуте ебанутые. Эта сидит там, чешет колоду, блять. Этот стоит, говорит "Я тебе щас тоже раздам"... Еб твою мать, у вас диллер есть, чтоб это делать мудак ебаный! Дегенерат ебаный! Вот пока ты это делал, дибил ебаный, сука, блять, так все и происходило');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, данная пасхалка ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'последние слова?') {
    
                        $rang = $db->query("SELECT easter_egg FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['easter_egg'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, '', ['attachment' => 'video537096011_456239074']);
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, данная пасхалка ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    // ОБЩЕДОСТУПНЫЕ
                    if ($cmd[0] == 'приобрести') {
    
                        $amount = $cmd[1];
    
                        if ($amount <= '70000' && is_numeric($amount)) {
    
                            if ($amount) {
    
                                $getInfo = $vk->request('users.get', ['user_ids' => $from_id, 'name_case' => 'gen']);
                                $name = $getInfo[0]['first_name'];
                                $pname = $getInfo[0]['last_name'];
    
                                $vk->sendMessage($peer_id, 'Покупка ' . $amount . ' коинов.' . PHP_EOL . 'Ссылка для покупки (ТОЛЬКО ДЛЯ @id' . $from_id . ' (' . $name . ' ' . $pname . ')): https://evistbot.space/redirect.php?vkid=https://vk.com/id' . $from_id . '&coins=' . $amount, ['disable_mentions' => '1']);
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'А сколько покупаем то?');
    
                            }
    
                        } elseif (is_numeric($amount) && $amount > '70000') {
    
                            $vk->sendMessage($peer_id, 'Я очень благодарен что ты хочешь задонатить мне сумму больше 100000 руб. но ограничься 70000 коинами');
    
                        } elseif (!is_numeric($amount)) {
    
                            $vk->sendMessage($peer_id, 'Нужно указать сколько покупаем, а не текст.');
    
                        }
    
                    }
                    if ($cmd[0] == 'перевести' || $cmd[0] == 'передать' || $cmd[0] == 'отдать') {
    
                        $amount = $cmd[1];
                        $group = explode("|", mb_substr($cmd[2], 1))[0];
                        $cgroup = preg_match("/(club|public)/", $group);
                        $peer = explode("|", mb_substr($cmd[2] ,3))[0];
    
                        if (!$peer && $from_id_fwd && !$cgroup) {
    
                            $ecoin_from = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id'")->fetch_assoc()['ecoins'];
                            $ecoin_peer = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id_fwd'")->fetch_assoc()['ecoins'];
    
                            if ($from_id !== $peer) {
    
                                if (is_numeric($amount)) {
    
                                    if ($amount >= '1') {
    
                                        if ($ecoin_from >= $amount) {
    
                                            $an = explode("\n", $message)[1];
    
                                            if (!$an) {
    
                                                $amount_from = $ecoin_from-$amount;
                                                $amount_peer = $ecoin_peer+$amount;
    
                                                $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                                $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$from_id_fwd'");
    
                                                $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $from_id_fwd . ' (пользователю) ' . $amount . ' коинов', ['disable_mentions' => '1']);
                                                $vk->sendMessage($from_id_fwd, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                            } else {
    
                                                $amount_from = $ecoin_from-$amount;
                                                $amount_peer = $ecoin_peer+$amount;
    
                                                $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                                $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$from_id_fwd'");
    
                                                $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $from_id_fwd . ' (пользователю) ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an, ['disable_mentions' => '1']);
                                                $vk->sendMessage($from_id_fwd, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести сумму которой у вас нету...');
    
                                        }
    
                                    } elseif ($amount < '1') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести 0 а то и меньше коинов..');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Укажите число, а не текст.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Утю-тю, ты хотел себе передать Коины? А вот нельзя.');
    
                            }
    
                        } elseif ($peer && !$from_id_fwd && !$cgroup) {
    
                            $ecoin_from = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id'")->fetch_assoc()['ecoins'];
                            $ecoin_peer = $db->query("SELECT ecoins FROM users WHERE vk_id = '$peer'")->fetch_assoc()['ecoins'];
    
                            if ($from_id !== $peer) {
    
                                if (is_numeric($amount)) {
    
                                    if ($amount >= '1') {
    
                                        if ($ecoin_from >= $amount) {
    
                                            $an = explode("\n", $message)[1];
    
                                            if (!$an) {
    
                                                $amount_from = $ecoin_from-$amount;
                                                $amount_peer = $ecoin_peer+$amount;
    
                                                $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                                $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$peer'");
    
                                                $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $peer . ' (пользователю) ' . $amount . ' коинов', ['disable_mentions' => '1']);
                                                $vk->sendMessage($peer, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                            } else {
    
                                                $amount_from = $ecoin_from-$amount;
                                                $amount_peer = $ecoin_peer+$amount;
    
                                                $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                                $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$peer'");
    
                                                $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $peer . ' (пользователю) ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an, ['disable_mentions' => '1']);
                                                $vk->sendMessage($peer, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                            }
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести сумму которой у вас нету...');
    
                                        }
    
                                    } elseif ($amount < '1') {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести 0 а то и меньше коинов..');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'Укажите число, а не текст.');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Утю-тю, ты хотел себе передать Коины? А вот нельзя.');
    
                            }
    
                        } elseif (!$peer && !$from_id_fwd && !$cgroup) {
    
                            $vk->sendMessage($peer_id, 'Укажи кому нужно перевести хотя бы.');
    
                        } elseif ($peer || $from_id_fwd && $cgroup) {
    
                            $vk->sendMessage($peer_id, 'Переводить группе нельзя, прости..');
    
                        }
    
                    }
                    if ($message == 'админы' || $message == 'admins') {
    
                        $sql = $db->query("SELECT rang, vk_id FROM users WHERE peer_id = '$peer_id' AND rang > '0' ORDER BY rang DESC");
    
                        $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
    
                        while ($admins_result = $sql->fetch_assoc()) {
    
                            $rang_admin = $admins_result['rang'];
                            $admin_id = $admins_result['vk_id'];
    
                            $getInfoAdmin = $vk->request('users.get', ['user_ids' => $admin_id]);
                            $name = $getInfoAdmin[0]['first_name'];
                            $pname = $getInfoAdmin[0]['last_name'];
    
                            while ($names_result = $names_query->fetch_assoc()) {
    
                                $moder_name = $names_result['name_moder'];
                                $m_adm_name = $names_result['name_m_adm'];
                                $adm_name = $names_result['name_adm'];
                                $s_adm_name = $names_result['name_s_adm'];
                                $own_name = $names_result['name_own'];
    
                            }
    
                            switch ($rang_admin) {
    
                                case '1':
    
                                if (count($admin_id) >= 1) {
    
                                    $list_admin .= '@id' . $admin_id . ' (' . $name . ' ' . $pname . ')' . ' - ' . $moder_name . PHP_EOL . PHP_EOL;
    
                                }
    
                                break;
                                case '2':
    
                                if (count($admin_id) >= 1) {
    
                                    $list_admin .= '@id' . $admin_id . ' (' . $name . ' ' . $pname . ')' . ' - ' . $m_adm_name . PHP_EOL . PHP_EOL;
    
                                }
    
                                break;
                                case '3':
    
                                if (count($admin_id) >= 1) {
    
                                    $list_admin .= '@id' . $admin_id . ' (' . $name . ' ' . $pname . ')' . ' - ' . $adm_name . PHP_EOL . PHP_EOL;
    
                                }
    
                                break;
                                case '4':
    
                                if (count($admin_id) >= 1) {
    
                                    $list_admin .= '@id' . $admin_id . ' (' . $name . ' ' . $pname . ')' . ' - ' . $s_adm_name . PHP_EOL . PHP_EOL;
    
                                }
    
                                break;
                                case '5':
    
                                if (count($admin_id) >= 1) {
    
                                    $list_admin .= '@id' . $admin_id . ' (' . $name . ' ' . $pname . ')' . ' - ' . $own_name . PHP_EOL . PHP_EOL;
    
                                }
    
                                break;
    
                            }
    
                        }
                        $vk->sendMessage($peer_id, 'Список администраторов: ' . PHP_EOL . PHP_EOL . $list_admin, ['disable_mentions' => '1']);
    
                    }
                    if (mb_substr($message,0,6) == 'snick ') {
    
                        $rang = $db->query("SELECT snick FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['snick'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $obj = mb_substr($original ,6);
    
                            $nick_check = $db->query("SELECT nick FROM users WHERE nick = '$obj' AND peer_id = '$peer_id'")->getNumRows();
    
                            if ($obj && !$nick_check && preg_match("/^[a-zA-Z0-9]/", $obj)) {
    
                                if (mb_strlen($obj) <= '20') {
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' Установлен ник: ' . $obj);
                                    $db->query("UPDATE users SET nick = '$obj' WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
    
                                } else {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' Ник превышает 20 символов');
    
                                }
    
                            } elseif ($obj && $nick_check && preg_match("/^[a-zA-Z0-9]/", $obj)) {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Ник уже занят.');
    
                            } elseif ($obj && !$nick_check && !preg_match("/^[a-zA-Z0-9]/", $obj)) {
    
                                $vk->sendMessage($peer_id, BT_WARN . ' Разрешено использовать только англ. и рус. алфавит.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'snick' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'топ') {
    
                        $rang = $db->query("SELECT toplist FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['toplist'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($cmd[1]) {
    
                                switch ($cmd[1]) {
    
                                    case 'всего':
    
                                    $list = $db->query("SELECT vk_id, sms_all FROM users WHERE peer_id = '$peer_id' AND sms_all > '0' ORDER BY sms_all DESC");
    
                                    while ($list_result = $list->fetch_assoc()) {
    
                                        $vk_id = $list_result['vk_id'];
                                        $sms_all = $list_result['sms_all'];
    
                                        $getTop = $vk->request('users.get', ['user_ids' => $vk_id]);
                                        $name = $getTop[0]['first_name'];
                                        $pname = $getTop[0]['last_name'];
    
                                        $list_sms .= '@id' . $vk_id . ' (' . $name . ' ' . $pname . ') - ' . $sms_all . PHP_EOL;
    
                                    }
    
                                    $vk->sendMessage($peer_id, 'Топ за всё время:' . PHP_EOL . $list_sms . PHP_EOL, ['disable_mentions' => '1']);
    
                                    break;
                                    case 'день':
    
                                    $list = $db->query("SELECT vk_id, sms_day FROM users WHERE peer_id = '$peer_id' AND sms_day > '0' ORDER BY sms_day DESC");
    
                                    while ($list_result = $list->fetch_assoc()) {
    
                                        $vk_id = $list_result['vk_id'];
                                        $sms_day = $list_result['sms_day'];
    
                                        $getTop = $vk->request('users.get', ['user_ids' => $vk_id]);
                                        $name = $getTop[0]['first_name'];
                                        $pname = $getTop[0]['last_name'];
    
                                        $list_sms .= '@id' . $vk_id . ' (' . $name . ' ' . $pname . ') - ' . $sms_day . PHP_EOL;
    
                                    }
    
                                    $vk->sendMessage($peer_id, 'Топ за день:' . PHP_EOL . $list_sms . PHP_EOL, ['disable_mentions' => '1']);
    
                                    break;
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Укажите за какой период вывести топ!');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'топ' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'конноры') {
    
                        $query_agents = $db->query("SELECT * FROM agents");
    
                        while ($results_agents = $query_agents->fetch_assoc()) {
    
                            $agents_id = $results_agents['vk_id'];
                            $agent = $results_agents['id'];
    
                            $getInfoagents = $vk->request("users.get", ['user_ids' => $agents_id, 'fields' => 'online']);
                            $name = $getInfoagents[0]['first_name'];
                            $pname = $getInfoagents[0]['last_name'];
                            $online = $getInfoagents[0]['online'];
    
                            if ($online == '1') {
    
                                $spisok .= $agent . '. @id' . $agents_id . '(' . $name . ' ' . $pname . ') - ' . BT_SUC . ' Коннор работает!' . PHP_EOL;
    
                            } elseif ($online == '0') {
    
                                $spisok .= $agent . '. @id' . $agents_id . '(' . $name . ' ' . $pname . ') - ' . BT_DEN . ' Коннор отключен!' . PHP_EOL;
    
                            }
    
                        }
    
                        $vk->sendMessage($peer_id, 'Список Конноров: ' . $agent . PHP_EOL . PHP_EOL .
                            $spisok . PHP_EOL .
                            'Есть вопрос на который нету ответа в командах или сами не знаете ответа? Напиши /репорт {текст}, и на ваш вопрос, Коннор ответит вам.', ['disable_mentions' => '1']);
    
                    }
                    if ($message == 'кто ты' && !$cmd[2]) {
    
                        $rang = $db->query("SELECT whoami FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['whoami'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            if ($from_id_fwd) {
    
                                if (!preg_match('/-/', $from_id_fwd)) {
    
                                    $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
    
                                    while ($names_result = $names_query->fetch_assoc()) {
    
                                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                        $stats = $db->query("SELECT sms_day, sms_all FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'");
    
                                        while ($stats_result = $stats->fetch_assoc()) {
    
                                            $sms_all = $stats_result['sms_all'];
                                            $sms_day = $stats_result['sms_day'];
    
                                        }
    
                                        switch ($userrang) {
    
                                            case '0':
    
                                            $position = 'Обычный пользователь';
    
                                            break;
                                            case '1':
    
                                            $position = $names_result['name_moder'];
    
                                            break;
                                            case '2':
    
                                            $position = $names_result['name_m_adm'];
    
                                            break;
                                            case '3':
    
                                            $position = $names_result['name_adm'];
    
                                            break;
                                            case '4':
    
                                            $position = $names_result['name_s_adm'];
    
                                            break;
                                            case '5':
    
                                            $position = $names_result['name_own'];
    
                                            break;
    
                                        }
    
                                    }
    
                                    $ecoins = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['ecoins'];
    
                                    $request = $vk->request('users.get', ['user_ids' => $from_id_fwd, 'name_case' => 'gen']);
                                    $name = $request[0]['first_name'];
                                    $pname = $request[0]['last_name'];
    
                                    if ($from_id_fwd) {
    
                                        $nick = $db->query("SELECT nick FROM users WHERE vk_id = '$from_id_fwd' AND peer_id = '$peer_id'")->fetch_assoc()['nick'];
    
                                        if ($nick !== 'kgszbkzxfklbzjomdgbq') {
    
                                            $vk->sendMessage($peer_id, 'Профиль @id' . $from_id_fwd . ' (' . $nick . '):' .     PHP_EOL .
                                                '⚙ Должность: ' . $position . PHP_EOL .
                                                '💵 Коинов: ' . $ecoins . PHP_EOL .
                                                '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, 'Профиль @id' . $from_id_fwd . ' (' . $name . ' ' . $pname . '):' .     PHP_EOL .
                                                '⚙ Должность: ' . $position . PHP_EOL .
                                                '💵 Коинов: ' . $ecoins . PHP_EOL .
                                                '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, 'Не удалось найти пользователя в этой беседе.');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'К сожалению, я не могу собрать информацию о сообществе. :(');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Не удалось найти пользователя в этой беседе.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Анкета пользователя' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == 'кто' && $cmd[1] == 'ты' && $cmd[2]) {
    
                        $rang = $db->query("SELECT whoami FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['whoami'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $idfdb = explode("|", mb_substr($cmd[2], 3))[0];
    
                            $checkuserinpeer = $db->query("SELECT * FROM users WHERE peer_id = '$peer_id' AND vk_id = '$idfdb'")->getNumRows();
    
                            if ($checkuserinpeer) {
    
                                $idd = explode("|", $cmd[2])[0];
                                $id = explode("|", mb_substr($cmd[2], 1))[0];
    
                                if (!preg_match("/club/", $idd)) {
    
                                    $who_id = mb_substr($id ,2);
    
                                    $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM peers WHERE peer_id = '$peer_id'");
    
                                    while ($names_result = $names_query->fetch_assoc()) {
    
                                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$who_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                                        $stats = $db->query("SELECT sms_day, sms_all FROM users WHERE vk_id = '$who_id' AND peer_id = '$peer_id'");
    
                                        while ($stats_result = $stats->fetch_assoc()) {
    
                                            $sms_all = $stats_result['sms_all'];
                                            $sms_day = $stats_result['sms_day'];
    
                                        }
    
                                        switch ($userrang) {
    
                                            case '0':
    
                                            $position = 'Обычный пользователь';
    
                                            break;
                                            case '1':
    
                                            $position = $names_result['name_moder'];
    
                                            break;
                                            case '2':
    
                                            $position = $names_result['name_m_adm'];
    
                                            break;
                                            case '3':
    
                                            $position = $names_result['name_adm'];
    
                                            break;
                                            case '4':
    
                                            $position = $names_result['name_s_adm'];
    
                                            break;
                                            case '5':
    
                                            $position = $names_result['name_own'];
    
                                            break;
    
                                        }
    
                                    }
    
                                    $ecoins = $db->query("SELECT ecoins FROM users WHERE vk_id = '$who_id' AND peer_id = '$peer_id'")->fetch_assoc()['ecoins'];
    
                                    $request = $vk->request('users.get', ['user_ids' => $who_id, 'name_case' => 'gen']);
                                    $name = $request[0]['first_name'];
                                    $pname = $request[0]['last_name'];
    
                                    if ($who_id) {
    
                                        $nick = $db->query("SELECT nick FROM users WHERE vk_id = '$who_id' AND peer_id = '$peer_id'")->fetch_assoc()['nick'];
    
                                        if ($nick !== 'kgszbkzxfklbzjomdgbq') {
    
                                            $vk->sendMessage($peer_id, 'Профиль @id' . $who_id . ' (' . $nick . '):' .     PHP_EOL .
                                                '⚙ Должность: ' . $position . PHP_EOL .
                                                '💵 Коинов: ' . $ecoins . PHP_EOL .
                                                '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                                        } else {
    
                                            $vk->sendMessage($peer_id, 'Профиль @id' . $who_id . ' (' . $name . ' ' . $pname . '):' .     PHP_EOL .
                                                '⚙ Должность: ' . $position . PHP_EOL .
                                                '💵 Коинов: ' . $ecoins . PHP_EOL .
                                                '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, 'Не удалось найти пользователя в этой беседе.');
    
                                    }
    
                                } else {
    
                                    $vk->sendMessage($peer_id, 'К сожалению, я не могу собрать информацию о сообществе. :(');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Не удалось найти пользователя в этой беседе.');
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Анкета пользователя' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'кто я') {
    
                        $rang = $db->query("SELECT whoami FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['whoami'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $names_query = $db->query("SELECT name_moder, name_m_adm, name_adm, name_s_adm, name_own FROM     peers WHERE peer_id = '$peer_id'");
    
                            while ($names_result = $names_query->fetch_assoc()) {
    
                                $stats = $db->query("SELECT sms_day, sms_all FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
    
                                while ($stats_result = $stats->fetch_assoc()) {
    
                                    $sms_all = $stats_result['sms_all'];
                                    $sms_day = $stats_result['sms_day'];
    
                                }
    
                                switch ($userrang) {
    
                                    case '0':
    
                                    $position = 'Обычный пользователь';
    
                                    break;
                                    case '1':
    
                                    $position = $names_result['name_moder'];
    
                                    break;
                                    case '2':
    
                                    $position = $names_result['name_m_adm'];
    
                                    break;
                                    case '3':
    
                                    $position = $names_result['name_adm'];
    
                                    break;
                                    case '4':
    
                                    $position = $names_result['name_s_adm'];
    
                                    break;
                                    case '5':
    
                                    $position = $names_result['name_own'];
    
                                    break;
    
                                }
    
                            }
    
                            $ecoins = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id'")->fetch_assoc()['ecoins'];
    
                            $request = $vk->request('users.get', ['user_ids' => $from_id, 'name_case' => 'gen']);
                            $name = $request[0]['first_name'];
                            $pname = $request[0]['last_name'];
    
                            $nick = $db->query("SELECT nick FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['nick'];
    
                            if ($nick !== 'kgszbkzxfklbzjomdgbq') {
    
                                $vk->sendMessage($peer_id, 'Профиль @id' . $from_id . ' (' . $nick . '):' . PHP_EOL .
                                    '⚙ Должность: ' . $position . PHP_EOL .
                                    '💵 Коинов: ' . $ecoins . PHP_EOL .
                                    '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Профиль @id' . $from_id . ' (' . $name . ' ' . $pname . '):' .     PHP_EOL .
                                    '⚙ Должность: ' . $position . PHP_EOL .
                                    '💵 Коинов: ' . $ecoins . PHP_EOL .
                                    '📝 Статистика за день / все время: ' . $sms_day . ' / ' . $sms_all, ['disable_mentions' => '1']);
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Анкета пользователя' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($cmd[0] == '!ид') {
    
                        if ($from_id_fwd) {
    
                            if (!preg_match('/-/', $from_id_fwd)) {
    
                                $vk->sendButton($peer_id, BT_SUC . ' ID пользователя = ' . $from_id_fwd);
    
                            } elseif (preg_match('/-/', $from_id_fwd)) {
    
                                $vk->sendButton($peer_id, BT_SUC . ' ID сообщества = ' . $from_id_fwd);
    
                            }
    
                        } else {
    
                            $idd = $cmd[1];
    
                            if ($from_id > 0) {
    
                                $id = explode("|", mb_substr($idd ,3))[0];
    
                            } else {
    
                                $id = explode("|", mb_substr($idd ,5))[0];
    
                            }
    
                            if (!$idd && $from_id > 0) {
    
                                $vk->sendMessage($peer_id, BT_SUC . ' ID ' . $from_id);
    
                            } elseif (!$idd && $from_id < 0) {
    
                                $vk->sendMessage($peer_id, BT_SUC . ' ID сообщества = ' . $from_id);
    
                            } elseif ($idd && !preg_match('/club/', $idd)) {
    
                                $vk->sendMessage($peer_id, BT_SUC . ' ID ' . $id);
    
                            } elseif ($idd && preg_match('/club/', $idd)) {
    
                                $vk->sendMessage($peer_id, BT_SUC . ' ID сообщества = -' . $id);
    
                            }
    
                        }
    
                    }
                    if ($message == 'убериклаву') {
    
                        $vk->sendButton($peer_id, BT_SUC . '', [[]]);
    
                    }
                    if ($message == 'пинг') {
    
                        $rang = $db->query("SELECT ping FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $vk->sendMessage($peer_id, 'Интернет провели.');
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Проверка работы бота' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
                    if ($message == 'пив') {
    
                        $rang = $db->query("SELECT ping FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['ping'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $first = rand(1,1000000);
                            $second = rand(1,1000000);
                            $third = rand($first, $second);
                            $four = rand($first, $second);
                            $fresult = rand($third, $four);
                            $sresult = rand($third, $four);
    
                            if ($fresult < $sresult) {
    
                                $vk->sendMessage($peer_id, 'О!');
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'ПАВ!');
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда 'Проверка работы бота' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                        }
    
                    }
                    if ($message == 'команды') {
    
                        $vk->sendMessage($peer_id, "Мои команды в данной статье -- vk.com/@evicm-commands");
    
                    }
                    if (explode("\n", $cmd[0])[0] == '!рулетка') {
    
                        $rang = $db->query("SELECT roulette FROM rang_commands WHERE peer_id = '$peer_id'")->fetch_assoc()['roulette'];
    
                        $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                        if ($rang <= $userrang) {
    
                            $time = time();
    
                            $getTimeRoul = $db->query("SELECT timeroulette FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['timeroulette'];
    
                            if ($time > $getTimeRoul) {
    
                                $randomvk = $vk->request('users.get', ['user_ids' => $from_id]);
                                $rname = $randomvk[0]['first_name'];
                                $rpname = $randomvk[0]['last_name'];
    
                                $poslslovrul = explode("\n", $original)[1];
    
                                $first = rand(1,1000000);
                                $second = rand(1,1000000);
                                $third = rand($first, $second);
                                $four = rand($first, $second);
                                $fresult = rand($third, $four);
                                $sresult = rand($third, $four);
    
                                $next_roul = $time + 86400;
    
                                $db->query("UPDATE users SET timeroulette = $next_roul WHERE vk_id = '$from_id' AND peer_id = '$peer_id'");
    
                                if ($fresult < $sresult && $poslslovrul) {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $from_id . ' (Брат).. Тебе обязательно повезет.. В жизни, может с девушкой повезет, но прости, в этом чате тебе не повезло остаться в живых так что.. твоими последними словами стали «' . $poslslovrul . "»", 'disable_mentions' => '1']);
    
                                    $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                                } elseif ($fresult < $sresult && !$poslslovrul) {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $from_id . ' (Брат).. Тебе обязательно повезет.. В жизни, может с девушкой повезет, но прости, в этом чате тебе не повезло остаться в живых так что.. Прощай.. Ты мне был как брат..', 'disable_mentions' => '1']);
    
                                    $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                                } elseif ($fresult > $sresult) {
    
                                    $vk->sendMessage($peer_id, 'Сегодня удача на твоей стороне, @id' . $from_id . ' (' . $rname . ' ' . $rpname . ')', ['disable_mentions' => '1']);
    
                                } elseif ($fresult == $sresult) {
    
                                    $vk->sendMessage($peer_id, 'Сегодня удача точно на твоей стороне, @id' . $from_id . ' (' . $rname . ' ' . $rpname . '), ведь тебе выпали два одинаковых числа из рандома (что почти что невозможно): ' . $fresult . '=' . $sresult, ['disable_mentions' => '1']);
    
                                }
    
                            } else {
    
                                $next_roul = date("d.m в H:i:s", $getTimeRoul);
    
                                $vk->sendMessage($peer_id, 'Смотри. @id' . $from_id . ' (Ты) уже играл сегодня в рулетку. Я думаю тебе не стоит так часто играть в нее, но зато я могу назвать с точностью до секунды когда ты сможешь поиграть. Так.. где же я это записывал.. А! Нашёл! Ты сможешь поиграть ' . $next_roul);
    
                            }
    
                        } else {
    
                            $mentions = $db->query("SELECT disable_mentions FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['disable_mentions'];
    
                            if ($mentions !== '1') {
    
                                $vk->sendMessage($peer_id, BT_WARN . "К сожалению, команда '!рулетка' ограничена для вас, так как ваш ранг ниже требуемого.");
    
                            }
    
                        }
    
                    }
    
                    if ($message == 'помощь') {
    
                        $query_agents = $db->query("SELECT * FROM agents");
    
                        while ($results_agents = $query_agents->fetch_assoc()) {
    
                            $agents_id = $results_agents['vk_id'];
                            $agent = $results_agents['id'];
    
                            $getInfoagents = $vk->request("users.get", ['user_ids' => $agents_id, 'fields' => 'online']);
                            $name = $getInfoagents[0]['first_name'];
                            $pname = $getInfoagents[0]['last_name'];
                            $online = $getInfoagents[0]['online'];
    
                            if ($online == '1') {
    
                                $spisok .= $agent . '. @id' . $agents_id . '(' . $name . ' ' . $pname . ') - ' . BT_SUC . ' в сети!' . PHP_EOL;
    
                            } elseif ($online == '0') {
    
                                $spisok .= $agent . '. @id' . $agents_id . '(' . $name . ' ' . $pname . ') - ' . BT_DEN . ' не в сети!' . PHP_EOL;
    
                            }
    
                        }
    
                        $vk->sendMessage($peer_id, 'Мои команды -> vk.cc/aA9uQi' . PHP_EOL . 'Список Конноров: ' . $agent . PHP_EOL . PHP_EOL .
                            $spisok . PHP_EOL .
                            'Есть вопрос на который нету ответа в командах или сами не знаете? Напиши /репорт {текст}, и на ваш вопрос, Коннор ответит вам.', ['disable_mentions' => '1']);
    
                    }
                    // СОБЫТИЯ
                    if ($invite == 'chat_invite_user') {
    
                        if ($check == '-191095367') {
    
                            $vk->sendMessage($peer_id, BT_SUC . ' Спасибо за то что добавили в беседу. Выдайте мне права администратора что бы я смог нормально функционировать в вашей беседе. Так-же, узнать мои команды можно с помощью данной статьи - vk.cc/aA9uQi');
    
                            $db->query("INSERT INTO peers (peer_id) VALUES ('$peer_id')");
    
                            $db->query("INSERT INTO rang_commands (peer_id) VALUES ('$peer_id')");
    
                        } elseif ($check !== '-191095367' && !preg_match("/-/", $check)) {
    
                            $userrang = $db->query("SELECT rang FROM users WHERE vk_id = '$from_id' AND peer_id = '$peer_id'")->fetch_assoc()['rang'];
    
                            $vkidban = $db->query("SELECT vk_id FROM bans WHERE peer_id = '$peer_id' AND vk_id = '$check'")->getNumRows();
    
                            if ($vkidban && $userrang < 4) {
    
                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => '@id' . $check . ' (Пользователь) забанен в данной беседе. Исключаю добавившего и забаненного']);
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $check]);
    
                            } elseif ($vkidban && $userrang >= 4) {
    
                                $vk->sendMessage($peer_id, '@id' . $from_id . ' (Администратор) добавил забаненного пользователя, удалю его из списка банов.');
                                $db->query("DELETE FROM bans WHERE vk_id = '$check' AND peer_id = '$peer_id'");
    
                            }
    
                            $groups_check = $db->query("SELECT gbots FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['gbots'];
    
                            $checkonclub = preg_match('/-/', $check);
    
                            if ($checkonclub && $groups_check == '1' && !preg_match("/-191095367/", $check)) {
    
                                $clubcheck_id = mb_substr($check ,1);
    
                                $vk->sendMessage($peer_id, '@id' . $from_id . ' (Пользователь) добавил @club' . $clubcheck_id . ' (сообщество), исключаю сообщество и добавившего.');
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $check]);
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                            }
    
                            $check_spammer = $db->query("SELECT vk_id FROM users WHERE vk_id = '$check' AND spammed = '1'")->fetch_assoc()['vk_id'];
                            $spam_reason = $db->query("SELECT spam_reason FROM users WHERE vk_id = '$check_spammer'")->fetch_assoc()['spam_reason'];
                            $spammerclub = preg_match('/-/', $check_spammer);
                            $clubnormal = mb_substr($check_spammer, 1);
    
                            if ($check_spammer == $check && $spammerclub && $userrang < 4) {
    
                                if ($spam_reason !== 'none') {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавил в вашу беседу @club' . $clubnormal . ' (сообщество) помеченное как Спамерское по причине: ' . $spam_reason . ', но я могу его удалить.']);
    
                                } else {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавил в вашу беседу @club' . $clubnormal . ' (сообщество) помеченное как Спамерское, но я могу его удалить.']);
    
                                }
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $check]);
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                            } elseif ($check_spammer == $check && !$spammerclub && $userrang < 4) {
    
                                if ($spam_reason !== 'none') {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавил в вашу беседу @id' . $from_id . ' (пользователя) помеченного как Спамер по причине: ' . $spam_reason . ', но я могу его удалить.']);
    
                                } else {
    
                                    $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавил в вашу беседу @club' . $clubnormal . ' (сообщество) помеченное как Спамерское, но я могу его удалить.']);
    
                                }
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $check]);
                                $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                            } elseif ($check_spammer == $check && !$spammerclub || $spammerclub && $userrang >= 4) {
    
                                $vk->sendMessage($peer_id, 'Администратор добавил спамера, исключать не буду но.. Дело ваше.');
    
                            }
    
                            $inv = $vk->request('users.get', ['user_ids' => $check]);
                            $name = $inv[0]['first_name'];
                            $pname = $inv[0]['last_name'];
    
                            $get_welcome = $db->query("SELECT welcome FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['welcome'];
    
                            if ($check !== $from_id) {
    
                                if ($get_welcome == 'none') {
    
                                    $vk->sendMessage($peer_id, ' @id' . $check . ' (' . $name . ' ' . $pname . '), добро пожаловать.' . PHP_EOL . PHP_EOL . 'Примечание: Это сообщение вызвано отсутствием приветствия.');
    
                                } else {
    
                                    $vk->sendMessage($peer_id, ' @id' . $check . ' (' . $name . ' ' . $pname . '), ' . $get_welcome);
    
                                }
    
                                if (!preg_match("/-/", $check)) {
    
                                    if (!$db->query("SELECT * FROM users WHERE vk_id = '$check' AND peer_id = '$peer_id'")->getNumRows()) {
    
                                        $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$check', '0', '$peer_id')");
                                        
                                    }
    
                                }
    
                            }
    
                        }
    
                    }
                    if ($invite == 'chat_invite_user_by_link') {
    
                        $check_spammer = $db->query("SELECT vk_id FROM users WHERE vk_id = '$from_id' AND spammed = '1'")->fetch_assoc()['vk_id'];
                        $spam_reason = $db->query("SELECT spam_reason FROM users WHERE vk_id = '$check_spammer'")->fetch_assoc()['spam_reason'];
    
                        if ($check_spammer == $from_id) {
    
                            if ($spam_reason !== 'none') {
    
                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавился в вашу беседу, он помечен как Спамер по причине: ' . $spam_reason . ', но я могу его удалить.']);
    
                            } else {
    
                                $vk->request('messages.send', ['peer_id' => $peer_id, 'message' => 'К нашему и вашему большому сожалению @id' . $from_id . ' (пользователь) добавился в вашу беседу, он помечен как Спамер, но я могу его удалить.']);
    
                            }
                            $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'member_id' => $from_id]);
    
                        }
    
                        $enter = $vk->request('users.get', ['user_ids' => $from_id]);
                        $entname = $enter[0]['first_name'];
                        $entpname = $enter[0]['last_name'];
    
                        $get_welcome = $db->query("SELECT welcome FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['welcome'];
    
                        if ($get_welcome == 'none') {
    
                            $vk->sendMessage($peer_id, ' @id' . $from_id . ' (' . $name . ' ' . $pname . '), добро пожаловать.' . PHP_EOL . PHP_EOL . 'Примечание: Это сообщение вызвано отсутствием приветствия.');
    
                        } else {
    
                            $vk->sendMessage($peer_id, ' @id' . $from_id . ' (' . $entname . ' ' . $entpname . '), ' . $get_welcome);
    
                        }
    
                        $db->query("INSERT INTO users (vk_id, rang, peer_id) VALUES ('$from_id', '0', '$peer_id')");
    
    
                    }
                    if ($invite == 'chat_kick_user') {
    
                        $autokick = $db->query("SELECT autokick FROM peers WHERE peer_id = '$peer_id'")->fetch_assoc()['autokick'];
    
                        if ($check == $from_id && $autokick == '0') {
    
                            $vk->sendButton($peer_id, BT_WARN . ' Из беседы вышел @id' . $check . ' (пользователь), выберите одно из действий:', [[$vk->buttonText('Исключить', 'red', ['command' => "кик $check"])]], true);
    
                        } elseif ($check == $from_id && $autokick == '1') {
    
                            $vk->request('messages.removeChatUser', ['chat_id' => $chat_id, 'user_id' => $from_id]);
    
                        }
                        if ($check !== $from_id) {
    
                            $checkonclub = preg_match('/-/', $from_id);
                            $clubcheck_id = mb_substr($from_id ,1);
    
                            $check_on_club = preg_match('/-/', $check);
                            $club_id = mb_substr($check ,1);
    
                            if ($checkonclub && $check_on_club) {
    
                                $vk->sendMessage($peer_id, BT_WARN . '@club' . $clubcheck_id . ' (Сообщество) удалило из беседы @club' . $club_id . ' (cообщество)');
    
                            } elseif (!$check_on_club && !$checkonclub) {
    
                                $delete = $vk->request('users.get', ['user_ids' => $from_id]);
                                $delname2 = $delete[0]['first_name'];
                                $delpname2 = $delete[0]['last_name'];
    
                                $vk->sendMessage($peer_id, BT_WARN . '@id' . $from_id . ' (' . $delname2 . ' ' . $delpname2 . ') удалил из беседы @id' . $check . ' (пользователя)');
    
                            } elseif ($check_on_club && !$checkonclub) {
    
                                $delete = $vk->request('users.get', ['user_ids' => $from_id,]);
                                $delname2 = $delete[0]['first_name'];
                                $delpname2 = $delete[0]['last_name'];
    
                                $vk->sendMessage($peer_id, BT_WARN . '@id' . $from_id . ' (' . $delname2 . ' ' . $delpname2 . ') удалил из беседы @club' . $club_id . ' (cообщество)');
    
                            } elseif (!$check_on_club && $checkonclub) {
    
                                $delete = $vk->request('users.get', ['user_ids' => $check, 'name_case' => 'acc']);
                                $delname2 = $delete[0]['first_name'];
                                $delpname2 = $delete[0]['last_name'];
    
                                $vk->sendMessage($peer_id, BT_WARN . '@club' . $clubcheck_id . ' (Сообщество) удалило из беседы @id' . $check . ' (' . $delname2 . ' ' . $delpname2 . ')');
    
                            }
    
                        }
    
                    }
    
                } catch (Exception $e) {
    
                    $a = json_decode($e->getMessage());
    
                    $vk->sendMessage($peer_id, 'Ошибка ВКонтакте: ' . $a->error->error_msg);
    
                    if ($e->getCode() == '917') {
    
                        $vk->sendMessage($peer_id, 'Вы дали мне доступ только к переписке и командам начинающимся с !, но мне нужна админка, чтобы корректно работать.');
                        return;
    
                    }
    
                    if ($e->getCode() == '15') {
    
                        $vk->sendMessage($peer_id, BT_WARN . ' Вылетела ошибка при выполнении запроса, вот ее код: ' . $e->getCode() . '. Обычно данная ошибка означает, что бот попытался удалить создателя или администратора беседы или бот является не администратором в данной беседе.');
    
                    } elseif ($e->getCode() !== '935' && $e->getCode() !== '917') {
    
                        $vk->sendMessage(435952306, BT_WARN . $e->getMessage());
    
                    }
    
                }
    
            } elseif (!$checkpeerid && $peer_id == $from_id) {
    
                // АДМИН - КОМАНДЫ (Не беседы)
                /*if (mb_substr($message,0,12) == '/банрепорта ') {
    
                    $agentsql = $db->query("SELECT * FROM agents WHERE vk_id = '$from_id'")->getNumRows();
    
                    if ($admsql) {
    
                        $repban_id = mb_substr($message ,12);
                        $repban_id = explode("|", mb_substr($repban_id ,3))[0];
    
                        if ($from_id_fwd) {
    
                            return;
    
                        } else {
    
                            if ($repban_id) {
    
                                $vk->sendMessage($peer_id, '@id' . $repban_id . ' (Пользователю) было выдано ограничение писать в репорт на 1 день');
                                $db->query("INSERT INTO reportban (id, vk_id) VALUES (NULL, '$repban_id')");
    
                            }
    
                        }
    
                    }
    
                }*
                if (mb_substr($message,0,5) == 'гбан ') {
    
                    $admsqlcheck = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id_fwd'")->getNumRows();
                    $admsql = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                    if ($admsql) {
    
                        $globan_id = mb_substr($message ,5);
                        $globan_id = explode("|", mb_substr($globan_id ,3))[0];
    
                        if ($from_id_fwd) {
    
                            return;
    
                        }
    
                        if ($globan_id) {
    
                            if ($admsqlcheck) {
    
                                return;
    
                            } else {
    
                                $chckbansql = $db->query("SELECT * FROM gban WHERE vk_id = $globan_id")->getNumRows();
    
                                if ($chckbansql) {
    
                                    $vk->sendMessage($peer_id, BT_WARN . 'Пользователь уже есть в списке глобана');
    
                                } else {
    
                                    $db->query("INSERT INTO gban (vk_id) VALUES ('$globan_id')");
    
                                    $vk->sendMessage($peer_id, BT_SUC . ' @id' . $globan_id . ' (Пользователь) был добавлен в глобальный бан бота.');
    
                                }
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, BT_WARN . 'Не указан ид');
    
                        }
    
                    } else {
    
                        return;
    
                    }
    
                }*/
                if (mb_substr($message,0,8) == 'гразбан ') {
    
                    $admsql = $db->query("SELECT * FROM admin WHERE vk_id = $from_id")->getNumRows();
    
                    if ($admsql) {
    
                        $glouban_id = mb_substr($message ,8);
                        $glouban_id = explode("|", mb_substr($glouban_id ,3))[0];
    
                        if ($from_id_fwd) {
    
                            return;
    
                        }
    
                        if ($glouban_id) {
    
                            $chcksql = $db->query("SELECT * FROM gban WHERE vk_id = $glouban_id")->getNumRows();
    
                            if ($chcksql) {
    
                                $db->query("DELETE FROM gban WHERE vk_id = $glouban_id");
    
                                $vk->sendMessage($peer_id, BT_SUC . ' @id' . $glouban_id . ' (Пользователь) был успешно вынесен из глобального бана.');
    
                                $vk->sendMessage($glouban_id, BT_SUC . ' Вы были успешно вынесены из глобального бана.' . PHP_EOL . 'В последующем разбан будет стоить 50 е - коинов, дальше 100 е - коинов и так далее (т.е каждый раз будет прибавлятся 50).' . PHP_EOL . 'Помните: вы попали туда не просто так.');
    
                            } else {
    
                                $vk->sendMessage($peer_id, BT_DEN . ' Пользователя и так нету в списке');
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, BT_WARN . 'Не указан ид');
    
                        }
    
                    } else {
    
                        return;
    
                    }
    
                }
                if ($message == 'гбаны') {
    
                    $gbanslist = $db->query("SELECT * FROM gban")->getNumRows();
    
                    if ($gbanslist) {
    
                        $id = $db->query("SELECT * FROM gban")->fetch_assoc();
    
                        while ($row = $id) {
    
                            $gbanid = $vk->request('users.get', ['user_ids' => $row['vk_id']]);
                            $gbanname = $gbanid[0]['first_name'];
                            $gbanpname = $gbanid[0]['last_name'];
    
                            $gban .= '@id' . $row['vk_id'] . ' (' . $gbanname . ' ' . $gbanpname . ')' . PHP_EOL;
    
                        }
    
                        $vk->sendMessage($peer_id, 'Пользователи находящиеся в глобальном бане бота:' . PHP_EOL . $gban);
    
                    } else {
    
                        $vk->sendMessage($peer_id, BT_WARN . 'Никого не найдено');
    
                    }
    
                }
                if (mb_substr($message,0,9) == '!writein ') {
    
                    $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
                    $checkadmin = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                    if ($checkagent || $checkadmin) {
    
                        $agent_id = $db->query("SELECT id FROM agents WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
                        $admin_id = $db->query("SELECT id FROM admin WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                        $obj = mb_substr($original ,9);
                        $obl = explode(" ", $obj, 2);
    
                        $idpeer = $obl[0];
                        $ansmsg = $obl[1];
    
                        $vk->sendMessage($peer_id, 'Отправлено');
    
                        if ($checkagent) {
    
                            $vk->sendMessage(2000000000 + $idpeer, 'Коннор#' . $agent_id . ' написал Вам: ' . $ansmsg);
    
                        } elseif ($checkadmin) {
    
                            $vk->sendMessage(2000000000 + $idpeer, 'Администратор#' . $admin_id . ' написал Вам: ' . $ansmsg);
    
                        }
    
                    }
    
                }
                if (mb_substr($message,0,4) == '!pm ') {
    
                    $checkagent = $db->query("SELECT * FROM agents WHERE vk_id = $from_id")->getNumRows();
                    $checkadmin = $db->query("SELECT * FROM admin WHERE vk_id = '$from_id'")->getNumRows();
    
                    if ($checkagent) {
    
                        $agent_id = $db->query("SELECT id FROM agents WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
                        $admin_id = $db->query("SELECT id FROM admin WHERE vk_id = '$from_id'")->fetch_assoc()['id'];
    
                        $obj = mb_substr($original ,4);
                        $obl = explode(" ", $obj, 2);
    
                        $idpeer = $obl[0];
                        $idpeer = explode("|", mb_substr($idpeer ,3))[0];
                        $ansmsg = $obl[1];
    
                        $vk->sendMessage($peer_id, 'Отправлено');
    
                        if ($checkagent) {
    
                            $vk->sendMessage($idpeer, 'Коннор#' . $agent_id . ' написал Вам: ' . $ansmsg);
    
                        } elseif ($checkadmin) {
    
                            $vk->sendMessage($idpeer, 'Администратор#' . $admin_id . ' написал Вам: ' . $ansmsg);
    
                        }
    
                    }
    
                }
                // Андроиды
                if ($cmd[0] == '+андроид') {
    
                    $codename = $cmdorig[1];
                    $address = $cmdorig[2];
    
                    $own_code = $db->query("SELECT user_id FROM android_data WHERE codename = '$codename'")->fetch_assoc()['user_id'];
                    $checkvalid = $db->query("SELECT codename FROM android_data WHERE user_id = '$own_code'")->fetch_assoc()['codename'];
    
                    if ($codename) {
    
                        if ($checkvalid == $codename && $own_code == $from_id) {
    
                            if (mb_strlen($codename) > '8' || mb_strlen($codename) <= '20') {
    
                                if ($address && $codename) {
    
                                    $arra = array("response" => "activating_user_bot", "object" => array("user_id" => $from_id, "codename" => "$codename"));

                                    $dat = json_encode($arra);
    
                                    $outorig = postResponse($address, $dat);
    
                                    $out = json_decode($outorig, true);
    
                                    if ($out['answer'] == 'ok') {
    
                                        $vk->sendMessage($peer_id, 'Вы успешно стали Андроидом.');
                                        $db->query("UPDATE android_data SET activated = '1' WHERE codename = '$codename'");
                                        $checkaddress = $db->query("SELECT * FROM android_settings WHERE user_id = '$from_id'")->getNumRows();
    
                                        if (!$checkaddress) {
    
                                            $db->query("INSERT INTO android_settings (user_id, address) VALUES ('$from_id', '$address')");
    
                                        } else {
    
                                            $db->query("UPDATE android_settings SET address = '$address' WHERE user_id = '$from_id'");
    
                                        }
    
                                    }
                                    if ($out['answer'] !== 'ok' && $out['answer'] !== 'already registred') {
    
                                        $vk->sendMessage($getAndroid, BT_DEN . ' Ответ вашего сервера: ' . $outorig . PHP_EOL . 'Если вам пришло данное сообщение значит на сервере произошла ошибка');
    
                                    }
                                    if ($out['answer'] == 'already registred') {
    
                                        $vk->sendMessage($peer_id, 'Вы успешно перепривязали андроида.');
                                        $db->query("UPDATE android_data SET activated = '1' WHERE codename = '$codename'");
                                        $checkaddress = $db->query("SELECT * FROM android_settings WHERE user_id = '$from_id'")->getNumRows();
    
                                        if (!$checkaddress) {
    
                                            $db->query("INSERT INTO android_settings (user_id, address) VALUES ('$from_id', '$address')");
    
                                        } else {
    
                                            $db->query("UPDATE android_settings SET address = '$address' WHERE user_id = '$from_id'");
    
                                        }
    
                                    }
    
                                } elseif (!$address && $codename) {
    
                                    $vk->sendMessage($peer_id, 'Не указан адрес.');
    
                                }
    
                            } elseif (mb_strlen($codename) < '8' || mb_strlen($codename) > '20') {
    
                                $vk->sendMessage($peer_id, 'Введеное вами кодовое имя меньше 8 символов или больше 20');
    
                            }
    
                        } elseif ($checkvalid == $codename && $own_code !== $from_id) {
    
                            $vk->sendMessage($peer_id, 'К нашему сожалению, мы сообщим владельцу Андроида о том что коднейм украли.');
                            $vk->sendMessage($own_code, 'Дорогой Андроид! Сообщаем о том, что ваше кодовое имя слили и сейчас идет попытка привязки его к другому профилю. Мы всеми силами хотим помочь вам, поэтому напишите в репорт о слитом кодовом имени и вам, как можно скорее помогут.');
    
                        } elseif ($checkvalid !== $codename && $own_code !== $from_id) {
    
                            $vk->sendMessage($peer_id, 'Я конечно пытался найти данный коднейм в базе.. у меня не вышло.');
    
                        }
    
                    } elseif (!$codename && !$address) {
    
                        $vk->sendMessage($peer_id, 'Не указано кодовое имя.');
    
                    }
    
                }
                if ($peer_id == $from_id) {
    
                    if ($message == 'начать' || $payload == 'start') {
    
                        $vk->sendMessage($peer_id, 'Привет, я evistbot, готов служить тебе верной честью, помоги же мне, добавь меня в беседу или напиши мне команды, что бы узнать команды.');
    
                    }
    
                }
                // Общедоступные
                if ($message == 'пинг') {
    
                    $vk->sendMessage($peer_id, 'Интернет провели.');
    
                }
                if ($message == 'пив') {
    
                    $first = rand(1,10000);
                    $second = rand(1,10000);
                    $third = rand($first, $second);
                    $four = rand($first, $second);
                    $fresult = rand($third, $four);
                    $sresult = rand($third, $four);
    
                    if ($fresult < $sresult) {
    
                        $vk->sendMessage($peer_id, 'О!');
    
                    } else {
    
                        $vk->sendMessage($peer_id, 'ПАВ!');
    
                    }
    
                }
                if (mb_substr($message,0,7) == 'рандом ') {
    
                    $values = mb_substr($message, 7);
                    $values = explode(" ", $values);
                    $first = $values[0];
                    $second = $values[1];
    
                    if (is_numeric($first) && is_numeric($second)) {
    
                        if (mb_strlen($first) <= '19' && mb_strlen($second) <= '19') {
    
                            $result = rand($first, $second);
    
                            if ($first <= '10382718929' && $second <= '10382718929') {
    
                                $vk->sendMessage($peer_id, 'Выбираю число: ' . $result);
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Одно из указанных чисел больше 10382718929');
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, 'Одно из чисел больше 19 символов');
    
                        }
    
                    } else {
    
                        $vk->sendMessage($peer_id, 'Один из параметров указан не верно!');
    
                    }
    
                }
                if ($message == 'последние слова?') {
    
                    $vk->sendMessage($peer_id, '', ['attachment' => 'video537096011_456239074']);
    
                }
                if ($message == 'казино') {
    
                    $vk->sendMessage($peer_id, 'Проблемы с доступом в джойказино? Ебаный в рот этого казино, блять. Ты кто такой, сука, чтоб это делать? Вы че, дебилы? Вы че, ебанутые? Вы внатуре ебанутые. Эта сидит там, чешет колоду, блять. Этот стоит, говорит "Я тебе щас тоже раздам"... Еб твою мать, у вас диллер есть, чтоб это делать, мудак ебаный! Дегенерат ебаный! Вот пока ты это делал, дебил ебаный, сука, блять, так все и происходило');
    
                }
                if ($message == 'команды') {
    
                    $vk->sendMessage($peer_id, "Мои команды в данной статье -- vk.com/@evicm-commands");
    
                }
                if ($cmd[0] == '!напиши' || $cmd[0] == '!say' || $cmd[0] == '!скажи' || $cmd[0] == '!произнеси' || $cmd[0] == '/напиши' || $cmd[0] == '/say' || $cmd[0] == '/скажи' || $cmd[0] == '/произнеси') {
    
                    if ($write = $cmdobrorig[1]) {
    
                        if (preg_match('/(п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)(а|a)ны|п(о|o)дпи(с|c)к(а|a)|п(о|o)дпишит(е|e)(с|c)ь)/', $write) == false) {
    
                            $writefl = $vk->request("users.get", ['user_ids' => $from_id]);
                            $name = $writefl[0]['first_name'];
                            $pname = $writefl[0]['last_name'];
    
                            $vk->sendMessage($peer_id, '@id' . $from_id . ' (' . $name . ' ' . $pname . '): ' . $write, ['disable_mentions' => '1']);
    
                        } else {
    
                            $writefl = $vk->request("users.get", ['user_ids' => $from_id]);
                            $name = $writefl[0]['first_name'];
                            $pname = $writefl[0]['last_name'];
    
                            $vk->sendMessage($peer_id, 'Не скажу.');
    
                            $vk->sendMessage(2000000317, '<WARNING> @id' . $from_id . ' (' . $name . ' ' . $pname . ') попытался с помощью команды "написание текста от бота" написать предложении с просьбой подписки. Занес его в глобальный бан, так же, в случае ложного срабатывания предоставлю текст: ' . $write . PHP_EOL . 'В случае если срабатывание ложное: гразбан @id' . $from_id);
    
                            $db->query("INSERT INTO logs (id, vk_id, command, param_command) VALUES (NULL, '$from_id', '.напиши', '$write')");
    
                            $db->query("INSERT INTO gban (vk_id) VALUES ($from_id)");
    
                        }
    
                    }
    
                }
                if ($message == 'помощь') {
    
                    $vk->sendMessage($peer_id, 'Мои команды -> vk.cc/aA9uQi' . PHP_EOL . 'Есть вопрос на который нету ответа в командах или сами не знаете? Напиши /репорт {текст}, и на ваш вопрос, тех. поддержка ответит вам.');
    
                }
                if ($cmd[0] == 'приобрести') {
    
                    $amount = $cmd[1];
    
                    if ($amount <= '70000' && is_numeric($amount)) {
    
                        if ($amount) {
    
                            $getInfo = $vk->request('users.get', ['user_ids' => $from_id, 'name_case' => 'gen']);
                            $name = $getInfo[0]['first_name'];
                            $pname = $getInfo[0]['last_name'];
    
                            $vk->sendMessage($peer_id, 'Покупка ' . $amount . ' коинов.' . PHP_EOL . 'Ссылка для покупки (ТОЛЬКО ДЛЯ @id' . $from_id . ' (' . $name . ' ' . $pname . ')): https://evistbot.space/redirect.php?vkid=vk.com/id' . $from_id . '&coins=' . $amount, ['disable_mentions' => '1']);
    
                        } else {
    
                            $vk->sendMessage($peer_id, 'А сколько покупаем то?');
    
                        }
    
                    } elseif (is_numeric($amount) && $amount > '70000') {
    
                        $vk->sendMessage($peer_id, 'Я очень благодарен что ты хочешь задонатить мне сумму больше 100000 руб. но ограничься 70000 коинами');
    
                    } elseif (!is_numeric($amount)) {
    
                        $vk->sendMessage($peer_id, 'Нужно указать сколько покупаем, а не текст.');
    
                    }
    
                }
                if ($cmd[0] == 'перевести' || $cmd[0] == 'передать' || $cmd[0] == 'отдать') {
    
                    $amount = $cmd[1];
                    $group = explode("|", mb_substr($cmd[2], 1))[0];
                    $cgroup = preg_match("/(club|public)/", $group);
                    $peer = explode("|", mb_substr($cmd[2] ,3))[0];
    
                    if (!$peer && $from_id_fwd && !$cgroup) {
    
                        $ecoin_from = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id'")->fetch_assoc()['ecoins'];
                        $ecoin_peer = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id_fwd'")->fetch_assoc()['ecoins'];
    
                        if ($from_id !== $peer) {
    
                            if (is_numeric($amount)) {
    
                                if ($amount >= '1') {
    
                                    if ($ecoin_from >= $amount) {
    
                                        $an = explode("\n", $message)[1];
    
                                        if (!$an) {
    
                                            $amount_from = $ecoin_from-$amount;
                                            $amount_peer = $ecoin_peer+$amount;
    
                                            $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                            $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$from_id_fwd'");
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $from_id_fwd . ' (пользователю) ' . $amount . ' коинов', ['disable_mentions' => '1']);
                                            $vk->sendMessage($from_id_fwd, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                        } else {
    
                                            $amount_from = $ecoin_from-$amount;
                                            $amount_peer = $ecoin_peer+$amount;
    
                                            $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                            $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$from_id_fwd'");
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $from_id_fwd . ' (пользователю) ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an, ['disable_mentions' => '1']);
                                            $vk->sendMessage($from_id_fwd, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести сумму которой у вас нету...');
    
                                    }
    
                                } elseif ($amount < '1') {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести 0 а то и меньше коинов..');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Укажите число, а не текст.');
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, 'Утю-тю, ты хотел себе передать Коины? А вот нельзя.');
    
                        }
    
                    } elseif ($peer && !$from_id_fwd && !$cgroup) {
    
                        $ecoin_from = $db->query("SELECT ecoins FROM users WHERE vk_id = '$from_id'")->fetch_assoc()['ecoins'];
                        $ecoin_peer = $db->query("SELECT ecoins FROM users WHERE vk_id = '$peer'")->fetch_assoc()['ecoins'];
    
                        if ($from_id !== $peer) {
    
                            if (is_numeric($amount)) {
    
                                if ($amount >= '1') {
    
                                    if ($ecoin_from >= $amount) {
    
                                        $an = explode("\n", $message)[1];
    
                                        if (!$an) {
    
                                            $amount_from = $ecoin_from-$amount;
                                            $amount_peer = $ecoin_peer+$amount;
    
                                            $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                            $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$peer'");
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $peer . ' (пользователю) ' . $amount . ' коинов', ['disable_mentions' => '1']);
                                            $vk->sendMessage($peer, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                        } else {
    
                                            $amount_from = $ecoin_from-$amount;
                                            $amount_peer = $ecoin_peer+$amount;
    
                                            $db->query("UPDATE users SET ecoins = $amount_from WHERE vk_id = '$from_id'");
                                            $db->query("UPDATE users SET ecoins = $amount_peer WHERE vk_id = '$peer'");
    
                                            $vk->sendMessage($peer_id, BT_SUC . ' Вы перевели @id' . $peer . ' (пользователю) ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an, ['disable_mentions' => '1']);
                                            $vk->sendMessage($peer, 'Вам перевели ' . $amount . ' коинов' . PHP_EOL . 'Коментарий: ' . $an . PHP_EOL . 'Отправитель: @id' . $from_id . ' (' . $vk->request('users.get', ['user_ids' => $from_id])[0]['first_name'] . ' ' . $vk->request('users.get', ['user_ids' => $from_id])[0]['last_name'] . ')');
    
                                        }
    
                                    } else {
    
                                        $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести сумму которой у вас нету...');
    
                                    }
    
                                } elseif ($amount < '1') {
    
                                    $vk->sendMessage($peer_id, BT_WARN . ' К сожалению я не могу перевести 0 а то и меньше коинов..');
    
                                }
    
                            } else {
    
                                $vk->sendMessage($peer_id, 'Укажите число, а не текст.');
    
                            }
    
                        } else {
    
                            $vk->sendMessage($peer_id, 'Утю-тю, ты хотел себе передать Коины? А вот нельзя.');
    
                        }
    
                    } elseif (!$peer && !$from_id_fwd && !$cgroup) {
    
                        $vk->sendMessage($peer_id, 'Укажи кому нужно перевести хотя бы.');
    
                    } elseif ($peer || $from_id_fwd && $cgroup) {
    
                        $vk->sendMessage($peer_id, 'Переводить группе нельзя, прости.');
    
                    }
    
                }
    
            } else {
    
                $db->query("INSERT INTO peers (peer_id) VALUES ('$peer_id')");
    
            }
    
        }
        
    }

}
if ($data->type == 'wall_post_new') {

    $own_id = $data->object->owner_id;

    $id_post = $data->object->id;

    $mailing_q = $db->query("SELECT peer_id FROM peers WHERE mailing = '1'");

    while ($mailing_res = $mailing_q->fetch_assoc()) {

        $mail = $mailing_res['peer_id'];
        $vk->sendMessage($mail, BT_WARN . ' Новый пост! Проявите актив под постом. Для отключения рассылки введите "-рассылка"', ['attachment' => 'wall' . $own_id . '_' . $id_post]);

    }

}