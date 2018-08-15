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
    
    /**
     * run() - starts webhook processing
     * 
     * @todo bot mention processing; 
     * @todo return have to be platform dependent;
     * @return string - text answer that have to be returned to calling peer
     */
    public function run() {
        $this->initRequest();
        $text = $this->getRequestText();
        
        $this->session = new lhSessionFile($this->getRequestSender());
        try {
            if ($text) {
                $answer = $this->processAdminActions($text);
                if (!$answer) {
                    $this->initChatterBox();
                    $answer = $this->processChatterbox($text); 
                    $answer = $this->processProxy($text, $answer);
                }
            } else {
                $answer = ['text' => lhTextConv::smilesSubstitutions(":think:")];
            }
            $this->sendMessage($answer);
        }
        catch (Exception $e) {
            $message = $e->getMessage();
            $trace = $e->getTraceAsString();
            $from = $this->getRequestSender();
            $this->sendMessage([ 'text' => "Вызвано исключение при обработке запроса \"$text\" от $from.\nСообщение: $message\nТрассировка:\n$trace"], $this->botdata->get('bot_admin'));
        }
        return '';
    }
    
    protected function sendMessage($answer, $chat=false) {
        if (isset($answer['mute'])) return;
        if ($chat) {
            (new lhSessionFile($chat))->log('fullchat', 'OUT', $answer['text']);
        } else {
            $this->session->log('fullchat', 'OUT', $answer['text']);
            $chat = $this->getRequestChat();
        }
        $api = $this->uba;
        $api->sendTextWithHints(
            $chat, 
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
    
    /**
     * Начало обработки ИИ
     * 
     * @param type $text
     * @return type
     * @todo Сделать, чтобы в группах реагировал только на команды с суффиксом @bot_username
     */
    protected function processChatterbox($text) {
        if (preg_match("/\/([^\s@]+)(\@(\w*))?(\s+(.*)|)$/", $text, $matches)) {
            if (!$matches[2] || ($matches[3] == $this->botdata->get('bot_username'))) {
                $answer = $this->chatterbox->scriptStart($matches[1]);
            } else {
                $answer = ['text' => 'Это не мне', 'mute' => true ];
            }
        } else {
            $answer = $this->chatterbox->process($text);
        }
        return $answer;
    }
    
    protected function processAdminActions($text) {
        $this->session->log('fullchat', 'IN', $text);
        $full_command = $this->session->get('bot_command', '') . ' ' . $text;
        $bot_username = $this->botdata->get('bot_username');
        $match = preg_match("/\/([^\s@]+)(\@(\w*))?(\s+(.*)|)$/", $full_command, $matches);
        if ($match && (($matches[3] == $bot_username) || (!$matches[2]))) {
            echo "$matches[1];$matches[2];$matches[3];$matches[4]\n";
            switch ($matches[1]) {
                case 'wantadmin':
                    return $this->cmdWantAdmin();
                case 'setadmin':
                    return $this->cmdSetAdmin(isset($matches[5]) ? $matches[5] : '');
                case 'wantoperator':
                    return $this->cmdWantOperator();
                case 'setoperator':
                    return $this->cmdSetOperator(isset($matches[5]) ? $matches[5] : '');
                case 'stopproxy':
                    return $this->cmdStopProxy();
                default:
                    return $this->cmdSessionId($matches[1]);
            }
        } else {
            return $this->processProxy($text);            
        }
        return false;
    }
    
    protected function isAdmin() {
        return ($this->getRequestChat() == $this->botdata->get('bot_admin'));
    }

    protected function isOwner() {
        return ($this->getRequestChat() == $this->botdata->get('bot_owner'));
    }

    protected function isOperator() {
        return ($this->getRequestChat() == $this->botdata->get('bot_operator'));
    }
  
    protected function initChatterBox() {
        $c = new lhChatterBox($this->getRequestSender());
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
                    $this->notifyOperator([ 'text' => 'Администратор бота одобрил предоставление вам прав оператора' ]);
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

    protected function cmdStopProxy() {
        $operator_for = $this->session->get('operator_for', '');
        if ($operator_for) {
            $session = new lhSessionFile($operator_for);
            $session->set('proxy_to','');
            $this->session->set('operator_for', '');
            $answer = ['text' => "Сеанс прокси с пользователем /$operator_for завершен"];
        } else {
            $answer = [ 'text' => 'Сеанс прокси не активен' ];
        }
        return $answer;
    }
    
    protected function cmdSessionId($session_id) {
        if ( $this->isOperator() ) {
            $session = new lhSessionFile($session_id);
            if ($session->get('status')) {
                $session->set('proxy_to', $this->getRequestSender());
                $session->set('status', 'babbler');
                $session->set('needhelp', '');
                $this->session->set('operator_for', $session_id);
                $this->sendChatHistory($this->getRequestSender(), $session); 
                return ['text'=>"Зер гуд!!!!!", 'mute' => 'yes'];
            } else {
                $session->destroy();
            }
        }
        return false;
    }
    
    protected function sendChatHistory($to_id, $which_session) {
        $chat = $which_session->readLog('fullchat', $this->botdata->get('send_chat_history_lines', 10));
        $this->sendMessage(['text' => implode("\n", preg_replace("/^\S+: /u", '', $chat))], $to_id);
    }
    
    protected function processProxy($text, $answer=false) {
        if ($answer === false) { // Вызов из proccessAdminActions для ответа оператора
            $operator_for = $this->session->get('operator_for');
            if ($operator_for) {
                $this->sendMessage(['text'=>$text], $operator_for);
                $answer['text'] = 'Текст отправлен пользователю';
                $answer['mute'] = true;
            }
        } else { // Вызов из run для реплики пользователя с ответом бота
            $this->session = new lhSessionFile($this->getRequestSender());
            if ($this->session->get('needhelp','')) {
                $this->sendMessage([ 'text' => 'Требуется помощь оператора с пользователем /'.$this->getRequestSender()], $this->botdata->get('bot_operator'));
            }
            $proxy_to = $this->session->get('proxy_to');
            if ( $proxy_to ) {
                $this->sendMessage([
                    'text' => $text,
                    'hints' => [ $answer['text'] ]
                ], $proxy_to);
                $answer['mute'] = true;
            }
        }
        return $answer;
    }
}
