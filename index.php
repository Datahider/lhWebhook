<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
define('LH_LIB_ROOT', "/Users/User/MyData/phplib");
define('LH_SESSION_DIR', __DIR__.'/');
require_once LH_LIB_ROOT . '/lhChatterBoxDataProviders/classes/lhSessionFile.php';
require_once LH_LIB_ROOT . '/lhChatterBox/classes/lhChatterBox.php';
require_once LH_LIB_ROOT . '/lhChatterBoxDataProviders/classes/lhAIML.php';
require_once LH_LIB_ROOT . '/lhChatterBoxDataProviders/classes/lhCSML.php';
require_once LH_LIB_ROOT . '/lhRuNames/classes/lhRuNames.php';
require_once LH_LIB_ROOT . '/lhTextConv/lhTextConv.php';
require_once 'lhTestTgBot.php';

date_default_timezone_set('UTC');

echo 'Проверка lhTestTgBot';
unlink('1212121.data');
file_put_contents('1212121.data', file_get_contents('initial_test.data'));
unlink('tst0000usr.data'); unlink('tst0000usr.chat'); unlink('tst0000usr.fullchat');
unlink('tst0040usr.data'); unlink('tst0040usr.fullchat'); unlink('tst0040usr.chat');
unlink('tst0050usr.data'); unlink('tst0050usr.fullchat');
unlink('tst0020grp.data'); unlink('tst0020grp.fullchat');
//die;
// '{"message":{"text":"","from":{"id":""},"chat":{"id":""}}}' - пустой объект для копирования
$test_plan = [
    '{"message":{"text":"/start","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Test template. Choose answer 1 or 2',
    '{"message":{"text":"1","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: It is block Test1',
    '{"message":{"text":"Как погод?","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Да ну ее, эту погоду. Я опять на море хочу!',
    '{"message":{"text":"/start","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Test template. Choose answer 1 or 2',
    '{"message":{"text":"/wantadmin","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0050usr: Пользователь  (tst0000usr) запрашивает права администратора. Для установки прав введите /setadminto tst0000usr: Владельцу бота направлен запрос на предоставление прав администратора',
    '{"message":{"text":"/setadmin","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Предоставить права администратора? hints:Да:Нет',
    '{"message":{"text":"Да","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Недостаточно прав',
    '{"message":{"text":"/setadmin","from":{"id":"0050usr"},"chat":{"id":"0050usr"}}}','to tst0050usr: Предоставить права администратора? hints:Да:Нет',
    '{"message":{"text":"Да","from":{"id":"0050usr"},"chat":{"id":"0050usr"}}}','to tst0000usr: Владелец бота одобрил предоставление вам прав администратораto tst0050usr: Администратор бота установлен',
    '{"message":{"text":"/wantoperator","from":{"id":"0040usr"},"chat":{"id":"0020grp"}}}','to tst0000usr: Пользователь  (tst0020grp) запрашивает права оператора. Для установки прав введите /setoperatorto tst0020grp: Администратору бота направлен запрос на предоставление прав оператора',
    '{"message":{"text":"/setoperator","from":{"id":"0050usr"},"chat":{"id":"0050usr"}}}','to tst0050usr: Предоставить права оператора? hints:Да:Нет',
    '{"message":{"text":"Да","from":{"id":"0050usr"},"chat":{"id":"0050usr"}}}','to tst0050usr: Недостаточно прав',
    '{"message":{"text":"/setoperator","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0000usr: Предоставить права оператора? hints:Да:Нет',
    '{"message":{"text":"Да","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0020grp: Администратор бота одобрил предоставление вам прав оператораto tst0000usr: Оператор бота установлен',
    '{"message":{"text":"/tst0050usr","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','Не найден блок "tst0050usr"',
    '{"message":{"text":"/Test1","from":{"id":"0040usr"},"chat":{"id":"0040usr"}}}','to tst0040usr: It is block Test1',
    '{"message":{"text":"/tst0000usr","from":{"id":"0040usr"},"chat":{"id":"0020grp"}}}','to tst0040usr: IN /tst0050usr',
    '{"message":{"text":"Шо ты имела ввиду?","from":{"id":"0040usr"},"chat":{"id":"0040usr"}}}','to tst0000usr: Шо ты имела ввиду?',
    '{"message":{"text":"Как погода?","from":{"id":"0000usr"},"chat":{"id":"0000usr"}}}','to tst0040usr: Как погода? hints:Да ну ее, эту погоду. Я опять на море хочу!',
    '{"message":{"text":"/stopproxy","from":{"id":"0040usr"},"chat":{"id":"0040usr"}}}','to tst0040usr: Сеанс прокси с пользователем tst0000usr завершен',
];

try {
    $w = new lhTestTgBot('1281803');
    echo "FAIL!!! - создан вебхук из несуществующей сессии 1281803\n";
    die();
} catch (Exception $exc) {
    echo '.';
}
$w = new lhTestTgBot('1212121');

for($i=0;isset($test_plan[$i]);$i++) {
    $w->setTestRequest($test_plan[$i]);
    ob_start();
    try {
        $w->run();
    } catch (Exception $exc) {
        echo $exc->getMessage();
    }
    $answer = ob_get_clean();
    $i++;
    if ($answer != $test_plan[$i]) {
        echo "FAIL!!! - Получено \"$answer\", ожидалось \"$test_plan[$i]\"\n";
        die();
    }
    echo '.';
}
echo "ok\n";