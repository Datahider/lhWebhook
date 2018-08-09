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
        $text = $this->request->message->text;
        
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
        return false;
    }

    private function sendMessage($answer) {
        $api_result = $this->apiQuery('sendMessage', [
            'text' => $answer['text'],
            'chat_id' => $this->request->message->chat->id,
            'parse_mode' => 'HTML',
            'reply_markup' => $this->makeKeyboard($answer)
        ]);
        $this->botdata->log(lhSessionFile::$facility_debug, json_encode($api_result));
    }
    
    private function makeKeyboard($answer) {
            if (count($answer['hints'])) {
            foreach ($answer['hints'] as $hint) {
                $hints[] = [[ 'text' => $hint ]];
            }
            $keyb = [
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
                'keyboard' => $hints
            ];
        } else {
            $keyb = [ 'remove_keyboard' => true ];
        }
        return json_encode($keyb);
    }


    private function initRequest() {
        $f = fopen('php://input', 'r');
        $json = stream_get_contents($f);
        
        $this->request = json_decode($json);
    }
    
    private function initChatterBox() {
        $c = new lhChatterBox('tgu'.$this->request->message->from->id);
        $script = new lhCSML();
        $script->loadCsml(LH_SESSION_DIR.$this->botdata->get('session_id')."/csml.xml");
        $aiml = new lhAIML();
        $aiml->loadAiml(LH_SESSION_DIR.$this->botdata->get('session_id')."/aiml.xml");
        $c->setAIProvider($aiml);
        $c->setScriptProvider($script);
        $this->chatterbox = $c;
    }

    private function apiQuery($func, $data) {
        $ch = curl_init('https://api.telegram.org/bot'.$this->botdata->get('bot_token').'/'.$func);
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
