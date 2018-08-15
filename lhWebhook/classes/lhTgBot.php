<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of lhTgBot
 *
 * @author user
 */
require_once __DIR__ . '/../abstract/lhAbstractBotWebhook.php';
class lhTgBot extends lhAbstractBotWebhook {
    
    protected function initRequest() {
        $f = fopen('php://input', 'r');
        $json = stream_get_contents($f);
        
        $this->request = json_decode($json);
    }
    
    protected function getRequestText() {
        return isset($this->request->message->text) ? $this->request->message->text : '';
    }
    
    protected function getRequestChat() {
        return $this->sessionPrefix().$this->request->message->chat->id;
    }
    
    protected function getRequestSender() {
        return $this->sessionPrefix().$this->request->message->from->id;
    }

    protected function sessionPrefix() {
        return 'tgu';
    }
        
}
