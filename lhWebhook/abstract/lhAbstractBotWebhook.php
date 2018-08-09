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

    abstract protected function initRequest();          // Получает текст полученный ботом в запросе
    abstract protected function getRequestText();       // Получает текст полученный ботом в запросе
    abstract protected function sendMessage($answer);   // Отправляет ответное сообщение пользователю
    abstract protected function sessionPrefix();        // Возвращает префикс для сессии

    
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
        
        $answer = $this->processAdminActions($text);
        if (!$answer) {
            $this->initChatterBox();
            $answer = $this->processChatterbox($text); 
        }
        
        $this->sendMessage($answer);
        return '';
    }

    private function processChatterbox($text) {
        if (preg_match("/^\/(\w+)/", $text, $matches)) {
            $answer = $this->chatterbox->scriptStart($matches[1]);
        } else {
            $answer = $this->chatterbox->process($text);
        }
        return $answer;
    }
    
    private function processAdminActions($text) {
        $session = new lhSessionFile($this->sessionPrefix().$this->request->message->from->id);
        return false;
    }
    
    private function isAdmin() {
        return ($this->sessionPrefix().$this->request->message->chat->id == $this->botdata->bot_admin);
    }

    private function isOwner() {
        return ($this->sessionPrefix().$this->request->message->chat->id == $this->botdata->bot_owner);
    }

    private function isOperator() {
        return ($this->sessionPrefix().$this->request->message->chat->id == $this->botdata->bot_operator);
    }
  
    private function initChatterBox() {
        $c = new lhChatterBox($this->sessionPrefix().$this->request->message->from->id);
        $script = new lhCSML();
        $script->loadCsml(LH_SESSION_DIR.$this->botdata->get('session_id')."/csml.xml");
        $aiml = new lhAIML();
        $aiml->loadAiml(LH_SESSION_DIR.$this->botdata->get('session_id')."/aiml.xml");
        $c->setAIProvider($aiml);
        $c->setScriptProvider($script);
        $this->chatterbox = $c;
    }
}
