<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
define('LH_LIB_ROOT', "/Users/User/MyData/phplib");
define('LH_SESSION_DIR', "/Users/User/MyData/lhsessiondata");
require_once LH_LIB_ROOT . '/lhChatterBoxDataProviders/classes/lhSessionFile.php';
require_once 'lhWebhook/classes/lhTestWebhook.php';

$s = new lhSessionFile('webhook-1212121');
$s->set('existing', 'yes');

echo 'Проверка lhTextWebhook';

try {
    $w = new lhTestWebhook('1281803');
    echo "FAIL!!! - создан вебхук из несуществующей сессии 1281803\n";
    die();
} catch (Exception $exc) {
    echo '.';
}
$w = new lhTestWebhook('1212121');
$json = $w->run();

if ( $json != '{"result":"ok","test":"yes"}' ) {
    echo "FAIL!!! - Получено \"$json\", ожидалось \"{\"result\":\"ok\",\"test\":\"yes\"}\"";
}
echo '.';
echo "ok\n";