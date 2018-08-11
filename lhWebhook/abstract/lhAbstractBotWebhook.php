<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lhAbstractBot
 *
 * @author user
 */
require_once __DIR__ . '/../interface/lhWebhookInterface.php';
require_once LH_LIB_ROOT . '/lhUnifiedBotApi/classes/lhUBA.php';

abstract class lhAbstractBotWebhook implements lhWebhookInterface{
    
    protected $botdata;
    protected $request;
    protected $chatterbox;
    protected $session;
    protected $uba;

    abstract protected function initRequest();                  // Получает данные запроса. Зависит от платформы
    abstract protected function getRequestText();               // Получает текст полученный ботом в запросе. Зависит от платформы
    abstract protected function getRequestChat();               // Получает id чата с префиксом из которого сделан запрос. Зависит от платформы
    abstract protected function getRequestSender();             // Получает id отправителя с префиксом (может отличаться от id чата если это группа)
    abstract protected function sessionPrefix();                // Возвращает префикс для сессии. Собственно это внутренний id платформы

    public function __construct($token) {
        $this->botdata = new lhSessionFile($token);
        if ($this->botdata->get('existing') != 'yes') {
            $this->botdata->destroy();
            throw new Exception("Can't find session id $token");
        }
        $this->uba = new lhUBA((array)$this->botdata->get('secrets'));
    }
    
    public function run() {
        $this->initRequest();
        $text = $this->getRequestText();
        
        if ($text) {
            $answer = $this->processAdminActions($text);
            if (!$answer) {
                $this->initChatterBox();
                $answer = $this->processChatterbox($text); 
            }
        } else {
            $answer = ['text' => lhTextConv::smilesSubstitutions(":think:")];
        }
        $this->sendMessage($answer);
        return '';
    }
    
    protected function sendMessage($answer, $chat=false) {
        $api = $this->uba;
        $api->sendTextWithHints(
            $chat ? $chat : $this->getRequestChat(), 
            $answer
        );
    }

    protected function notifyAdmin($answer) {
        $this->sendMessage($answer, $this->botdata->get('bot_admin'));
    }
    
    protected function notifyOwner($answer) {
        $this->sendMessage($answer, $this->botdata->get('bot_owner'));
    }
    
    protected function notifyOperator($answer) {
        $this->sendMessage($answer, $this->botdata->get('bot_operator'));
    }
    

    protected function processChatterbox($text) {
        if (preg_match("/^\/(\w+)/", $text, $matches)) {
            $answer = $this->chatterbox->scriptStart($matches[1]);
        } else {
            $answer = $this->chatterbox->process($text);
        }
        return $answer;
    }
    
    protected function processAdminActions($text) {
        $this->session = new lhSessionFile($this->sessionPrefix().$this->getRequestSender());
        $full_command = $this->session->get('bot_command', '') . ' ' . $text;
        if (preg_match("/\/(\S+)(\s*(.*))$/", $full_command, $matches)) {
            switch ($matches[1]) {
                case 'wantadmin':
                    return $this->cmdWantAdmin();
                case 'setadmin':
                    return $this->cmdSetAdmin($matches[3]);
                case 'wantoperator':
                    return $this->cmdWantOperator();
                case 'setoperator':
                    return $this->cmdSetOperator($matches[3]);
                default:
                    return $this->cmdSessionId($matches[1]);
            }
        }
        return false;
    }
    
    protected function isAdmin() {
        return ($this->sessionPrefix().$this->getRequestChat() == $this->botdata->bot_admin);
    }

    protected function isOwner() {
        return ($this->sessionPrefix().$this->getRequestChat() == $this->botdata->bot_owner);
    }

    protected function isOperator() {
        return ($this->sessionPrefix().$this->getRequestChat() == $this->botdata->bot_operator);
    }
  
    protected function initChatterBox() {
        $c = new lhChatterBox($this->sessionPrefix().$this->getRequestSender());
        $script = new lhCSML();
        $script->loadCsml(LH_SESSION_DIR.$this->botdata->get('session_id')."/csml.xml");
        $aiml = new lhAIML();
        $aiml->loadAiml(LH_SESSION_DIR.$this->botdata->get('session_id')."/aiml.xml");
        $c->setAIProvider($aiml);
        $c->setScriptProvider($script);
        $this->chatterbox = $c;
    }
    
    
    // Команды для администрирования ботов привязанных к сервису
    
    protected function cmdWantAdmin() {
        $user = $this->getRequestChat();
        $this->botdata->set('wantadmin', $user);
        $user_data = $this->uba->getUserData($user);
        $this->notifyOwner([ 'text' => "Пользователь $user_data[full_name] ($user) запрашивает права администратора. Для установки прав введите /setadmin" ]); 
        $this->session->set('bot_command', '');
        return [ 'text' => 'Владельцу бота направлен запрос на предоставление прав администратора' ];
    }
    
    protected function cmdWantOperator() {
        $user = $this->getRequestChat();
        $this->botdata->set('wantoperator', $user);
        $user_data = $this->uba->getUserData($user);
        $this->notifyAdmin([ 'text' => "Пользователь $user_data[full_name] ($user) запрашивает права оператора. Для установки прав введите /setoperator" ]); 
        $this->session->set('bot_command', '');
        return [ 'text' => 'Администратору бота направлен запрос на предоставление прав оператора' ];
    }
    
    protected function cmdSetAdmin($yes) {
        if ($yes == 'Да') {
            if ( $this->isOwner() ) {
                $wantadmin = $this->botdata->get('wantadmin', '');
                if ($wantadmin) {
                    $this->botdata->set('bot_admin', $wantadmin);
                    $answer = [ 'text' => 'Администратор бота установлен'];
                    $this->notifyAdmin([ 'text' => 'Владелец бота одобрил предоставление вам прав администратора' ]);
                }
            } else {
                $answer = ['text'=>'Недостаточно прав'];
            }
            $this->session->set('bot_command', '');
        } elseif(!$yes) {
            $this->session->set('bot_command', '/setadmin');
            $answer = [ 'text' => 'Предоставить права администратора?', 'hints' => ['Да', 'Нет']];
        } else {
            $this->session->set('bot_command', '');
            $answer = [ 'text' => 'Отменено'];
        }
        return $answer;
    }
    
    protected function cmdSetOperator($yes) {
        if ($yes == 'Да') {
            if ( $this->isAdmin() ) {
                $wantoperator = $this->botdata->get('wantoperator', '');
                if ($wantoperator) {
                    $this->botdata->set('bot_operator', $wantoperator);
                    $answer = [ 'text' => 'Оператор бота установлен'];
                    $this->notifyAdmin([ 'text' => 'Администратор бота одобрил предоставление вам прав оператора' ]);
                }
            } else {
                $answer = ['text'=>'Недостаточно прав'];
            }
            $this->session->set('bot_command', '');
        } elseif(!$yes) {
            $this->session->set('bot_command', '/setoperator');
            $answer = [ 'text' => 'Предоставить права оператора?', 'hints' => ['Да', 'Нет']];
        } else {
            $this->session->set('bot_command', '');
            $answer = [ 'text' => 'Отменено' ];
        }
        return $answer;
    }

    protected function cmdSessionId($session_id) {
        if ( $this->isOperator() ) {
            $session = new lhSessionFile($session_id);
            if ($session->get('status')) {
                $session->set('proxy_to', $this->getRequestSender());
                $this->session->set('operator_for', $session_id);
                //$this->sendChatHistory();
            } else {
                $session->destroy();
            }
        }
        return false;
    }
    
}
