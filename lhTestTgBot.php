<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * lhTestTgBot класс для тестирования lhAbstractBotWebhook и lhTgBot
 *
 * Переопределяет методы 
 *      initRequest - заглушка, вместо него до run() вызывать setTestRequest
 *      sendMessage - вывода ответов через echo (ob_start() для тестирования) 
 *      sessionPrefix - для тестовых целей префикс tst
 * 
 * @author Peter Datahider
 */
require_once __DIR__ . '/lhWebhook/classes/lhTgBot.php';
class lhTestTgBot extends lhTgBot {

    public function setTestRequest($json) {
        $this->request = json_decode($json);
    }

    protected function initRequest() {
        // Для тестов используется setTestRequest
    }
    
    protected function sendMessage($answer, $chat=null) {
        if (isset($answer['mute'])) return;
        if ($chat) {
            (new lhSessionFile($chat))->log('fullchat', 'OUT', $answer['text']);
        } else {
            $this->session->log('fullchat', 'OUT', $answer['text']);
            $chat = $this->getRequestChat();
        }
        echo "to $chat: $answer[text]";
        if (isset($answer['hints']) && count($answer['hints'])) {
            echo " hints:". implode(':', $answer['hints']);
        }
    }
    
    protected function sessionPrefix() {
        return 'tst';
    }
}
