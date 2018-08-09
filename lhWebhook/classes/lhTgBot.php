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
class lhTgBot extends lhAbstractBotWebhook {
    
    protected function sendMessage($answer) {
        $api_result = $this->apiQuery('sendMessage', [
            'text' => $answer['text'],
            'chat_id' => $this->request->message->chat->id,
            'parse_mode' => 'HTML',
            'reply_markup' => $this->makeKeyboard($answer)
        ]);
        $this->botdata->log(lhSessionFile::$facility_debug, json_encode($api_result));
    }
    
    protected function makeKeyboard($answer) {
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


    protected function initRequest() {
        $f = fopen('php://input', 'r');
        $json = stream_get_contents($f);
        
        $this->request = json_decode($json);
    }
    
    protected function getRequestText() {
        return $this->request->message->text;
    }

    protected function apiQuery($func, $data) {
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
    
    protected function sessionPrefix() {
        return 'tgu';
    }
}
