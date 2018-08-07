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
class lhTgBot extends lhTestWebhook {
    
    protected $request;
    protected $chatterbox;

    public function __construct($token) {
        parent::__construct($token);
    }
    
    public function run() {
        $this->initRequest();
        $this->initChatterBox();
        
        $answer = $this->chatterbox->answer($this->request->message->text);
        
        $this->apiQuery('sendMessage', [
            'text' => $answer->text,
            'chat_id' => $this->request->message->chat->id
        ]);
        
        return '';
    }
    
    private function initRequest() {
        $f = fopen('php://input', 'r');
        $json = stream_get_contents($f);
        
        $this->request = json_decode($json);
    }
    
    private function initChatterBox() {
        $c = new lhChatterBox($this->session->get('session_id'));
        $script = new lhCSML();
        $script->loadCsml(LH_SESSION_DIR.$this->session->get('session_id')."/csml.xml");
        $aiml = new lhAIML();
        $aiml->loadAiml(LH_SESSION_DIR.$this->session->get('session_id')."/aiml.xml");
        $c->setAIProvider($aiml);
        $c->setScriptProvider($script);
        $this->chatterbox = $c;
    }

    private function apiQuery($func, $data) {
        $ch = curl_init('https://api.telegram.org/bot'.$this->session->get('bot_token').'/'.$func);
        if ( $ch ) {
            if (curl_setopt_array( $ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $data
            ))) {    
                $content=curl_exec($ch);
                if (curl_errno($ch)) throw new Exception (curl_error ($ch).' Content provided: '.$content);
                curl_close($ch);
                return json_decode($content);
            }
        }
        return false;
    }
    
}
