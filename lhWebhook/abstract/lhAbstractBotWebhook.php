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

abstract class lhAbstractBotWebhook implements lhWebhookInterface{
    
    protected $botdata;
    protected $request;
    protected $chatterbox;
    protected $session;

    abstract protected function initRequest();                  // Получает текст полученный ботом в запросе
    abstract protected function getRequestText();               // Получает текст полученный ботом в запросе
    abstract protected function getRequestChat();               // Получает id чата из которого сделан запрос
    abstract protected function getRequestSender();             // Получает id отправителя (может отличаться от id чата если это группа)
    abstract protected function sendMessage($answer);           // Отправляет ответное сообщение пользователю
    abstract protected function sessionPrefix();                // Возвращает префикс для сессии
    abstract protected function notificationCmdWantAdmin();     // Возвращает текст уведомления владельза о запросе прав админа
    abstract protected function answerCmdWantAdmin();           // Возвращает текст ответа пользоавтелю запросившему админские права
    abstract protected function notificationCmdWantOperator();  // Возвращает текст уведомления владельза о запросе прав админа
    abstract protected function answerCmdWantOperator();        // Возвращает текст ответа пользоавтелю запросившему админские права
    abstract protected function notifyOwner($answer);           // Уведомляет владельца бота
    abstract protected function notifyAdmin($answer);           // Уведомляет администратора бота
    abstract protected function notifyOperator($answer);        // Уведомляет оператора бота


    public function __construct($token) {
        $this->botdata = new lhSessionFile($token);
        if ($this->botdata->get('existing') != 'yes') {
            $this->botdata->destroy();
            throw new Exception("Can't find session id webhook-$token");
        }
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
        $this->botdata->set('wantadmin', $this->getRequestChat());
        $this->notifyOwner($this->notificationCmdWantAdmin()); 
        $this->session->set('bot_command', '');
        return $this->answerCmdWantAdmin();
    }
    
    protected function cmdWantOperator() {
        $this->botdata->set('wantoperator', $this->getRequestChat());
        $this->notifyAdmin($this->notificationCmdWantOperator()); 
        $this->session->set('bot_command', '');
        return $this->answerCmdWantOperator();
    }
    
    protected function cmdSetAdmin($yes) {
        if ($yes == 'Да') {
            $owner = $this->botdata->get('bot_owner');
            if ( $this->getRequestChat() == $owner ) {
                $wantadmin = $this->botdata->get('wantadmin', '');
                if ($wantadmin) {
                    $this->botdata->set('bot_admin', $wantadmin);
                    $answer = [ 'text' => 'Администратор бота установлен'];
                    $this->notifyAdmin([ 'text' => 'Владелец бота одобрил предоставление вам прав администратора' ]);
                }
            } else {
                $answer = $this->answerInsuficientRights();
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
    
    protected function cmdSetOperator() {
        $this->botdata->set('wantadmin', $this->getRequestChat());
        $notification = [ 'text' => $this->notificationCmdWantAdmin() ];
        $this->notifyOwner($notification); 
        $this->sendMessage($this->answerCmdWantAdmin());
    }

    protected function cmdSessionId() {
        $this->botdata->set('wantadmin', $this->getRequestChat());
        $answer = [ 'text' => $this->answerCmdWantAdmin() ];
        $notification = [ 'text' => $this->notificationCmdWantAdmin() ];
        $this->notifyOwner($notification); 
        $this->sendMessage($answer);
    }
    
    // Стандартные ответы
    protected function answerInsuficientRights($param) {
        return ['text'=>'Недостаточно прав'];
    }
}
