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
    
    protected function sendMessage($answer, $chat=false) {
        $api_result = $this->apiQuery('sendMessage', [
            'text' => $answer['text'],
            'chat_id' => $chat ? $chat : $this->getRequestChat(),
            'parse_mode' => 'HTML',
            'reply_markup' => $this->makeKeyboard($answer)
        ]);
        $this->botdata->log(lhSessionFile::$facility_debug, $answer, json_encode($api_result));
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
    
    protected function makeKeyboard($answer) {
            if (isset($answer['hints']) && count($answer['hints'])) {
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
                return '<i>Поступил запрос на предоставление прав администратора '
                . 'для группы</i> <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->title
                .'</a><i>. Для предоставления прав введите </i>/setadmin';
            } else {
                return '<i>Поступил запрос на предоставление прав администратора '
                . 'для пользователя</i> <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->first_name.' '.$chat->result->last_name
                .'</a><i>. Для предоставления прав введите </i>/setadmin';
            }
        } else {
            return '<i>Не удается получить данные чата</i> '.$this->getRequestChat();
        }
    }
    
    protected function answerCmdWantOperator() {
        return 'Администратору бота направлен запрос на предоставление прав оператора';
    }
    
    protected function notificationCmdWantOperator() {
        $chat = $this->apiQuery('getChat', [ 'chat_id' => $this->getRequestChat()]);
        if ($chat->ok) {
            if ($chat->result->type == 'group') {
                return '<i>Поступил запрос на предоставление прав оператора '
                . 'для группы</i> <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->title
                .'</a><i>. Для предоставления прав введите </i>/setoperator';
            } else {
                return '<i>Поступил запрос на предоставление прав оператора '
                . 'для пользователя</i> <a href="tg://user?id='.$chat->result->id
                .'">'.$chat->result->first_name.' '.$chat->result->last_name
                .'</a><i>. Для предоставления прав введите </i>/setoperator';
            }
        } else {
            return '<i>Не удается получить данные чата</i> '.$this->getRequestChat();
        }
    }
    
}
