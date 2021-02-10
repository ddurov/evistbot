<?php 

    function logg($text) {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'log.txt', $text."\n", FILE_APPEND);
    }
    
    if (!is_numeric($_GET['coins']) && $_GET['coins']) {
        die('Привет! Ты в запросе указал кое - что не правильно.');
    }
    
    if (!$_GET['coins'] || !$_GET['vkid']) {
        die('Привет! Ты в запросе забыл указать кое-что');  
    }

    if ($_GET['coins'] && $_GET['vkid']):
        
        $public_key = "ПУБЛИЧНЫЙ КЛЮЧ МАГАЗИНА UnitPay"; 
        $secret_key = "СЕКРЕТНЫЙ КЛЮЧ МАГАЗИНА UnitPay"; 
        $amount = $_GET['coins'];
        $amount = $amount * 1.2;
        $uid = $_GET['vkid'];
        $user_url = preg_replace("!.*?/!", '', $uid);
        $user_id = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids=$user_url&access_token=0c085baa0c085baa0c085baa250c79a29800c080c085baa52c85921a086c4d8b20fd124&v=5.92"));
        $uid = $user_id->response[0]->id;
        if ($_GET['coins'] == '1') {
            
            $desc = "Покупка " . $_GET['coins'] . " коина";   
            
        } else {
            
            $desc = "Покупка " . $_GET['coins'] . " коинов";
            
        }
        $mysqli = new mysqli('localhost', 'user', 'password', 'db_name'); 
        if (mysqli_connect_errno()) {
    
            die("Ошибка подключения базы данных");
    
        }
        $qu = "INSERT INTO `unitpay` (`uid`, `amount`, `product`) VALUES ('$uid', '$amount', '".$_GET['coins']."')";
        $mysqli->query($qu);
        $id = $mysqli->insert_id;
        
        $hashStr = $uid.'{up}'.$desc.'{up}'.$amount.'{up}'.$secret_key;
        $sign = hash('sha256', $hashStr);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Перенаправление...</title>
        <meta charset="utf-8" />
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    </head>
    <body>
        <form id="payment" action="https://unitpay.money/pay/<?= $public_key;?>" method="POST">
            <input type="hidden" name="sum" value="<?= $amount;?>" />
            <input type="hidden" name="account" value="<?= $uid;?>" />
            <input type="hidden" name="desc" value="<?= $desc;?>" />
            <input type="hidden" name="signature" value="<?= $sign;?>" />
            <input type="hidden" name="hideOrderCost" value="true" />
            <input style="display:none;" id="btn_payment" type="submit" value="Оплатить" />   
        </form>
        <script type="text/javascript">
            $("#payment").submit();
        </script>
    </body>
</html>

<?php

endif;

?>
