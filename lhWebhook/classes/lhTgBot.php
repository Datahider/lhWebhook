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
    
    protected function initRequest() {
        $f = fopen('php://input', 'r');
        $json = stream_get_contents($f);
        
        $this->request = json_decode($json);
    }
    
    protected function getRequestText() {
        return isset($this->request->message->text) ? $this->request->message->text : '';
    }
    
    protected function getRequestChat() {
        return $this->request->message->chat->id;
    }
    
    protected function getRequestSender() {
        return $this->request->message->from->id;
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
    
    // текстовые ответы
    protected function answerCmdWantAdmin() {
        return 'Владельцу бота направлен запрос на предоставление прав администратора';
    }
    
    protected function notificationCmdWantAdmin() {
        $chat = $this->apiQuery('getChat', [ 'chat_id' => $this->getRequestChat()]);
        if ($chat->ok) {
            if ($chat->result->type == 'group') {
                $text = 'Поступил запрос на предоставление прав администратора '
                . 'для группы <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->title
                .'</a>. Для предоставления прав введите /setadmin';
            } else {
                $text = 'Поступил запрос на предоставление прав администратора '
                . 'для пользователя <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->first_name.' '.$chat->result->last_name
                .'</a>. Для предоставления прав введите /setadmin';
            }
        } else {
            $text = 'Не удается получить данные чата '.$this->getRequestChat();
        }
        return [ 'text' => $text ];
    }
    
    protected function answerCmdWantOperator() {
        return 'Администратору бота направлен запрос на предоставление прав оператора';
    }
    
    protected function notificationCmdWantOperator() {
        $chat = $this->apiQuery('getChat', [ 'chat_id' => $this->getRequestChat()]);
        if ($chat->ok) {
            if ($chat->result->type == 'group') {
                $text = 'Поступил запрос на предоставление прав оператора '
                . 'для группы <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->title
                .'</a>. Для предоставления прав введите /setoperator';
            } else {
                $text = 'Поступил запрос на предоставление прав оператора '
                . 'для пользователя <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->first_name.' '.$chat->result->last_name
                .'</a>. Для предоставления прав введите /setoperator';
            }
        } else {
            $text = 'Не удается получить данные чата '.$this->getRequestChat();
        }
        return [ 'text' => $text ];
    }
    
}
