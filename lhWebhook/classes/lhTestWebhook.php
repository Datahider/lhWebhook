<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * lhTestWebhook - тестовый обработчик вебхука, который просто возвращает 
 * json-объект созданный из массива:
 * [ 'result' => 'ok', 'test' => 'yes' ] 
 *
 * @author Peter Datahider
 */

require_once __DIR__ . '/../abstract/lhAbstractBotWebhook.php';
class lhTestWebhook extends lhAbstractBotWebhook {
    
    public function run() {
        return json_encode([
            'result' => 'ok',
            'test' => 'yes'
        ]);
    }
    
    protected function initRequest(){}          // Получает текст полученный ботом в запросе
    protected function getRequestText(){}       // Получает текст полученный ботом в запросе
    protected function sessionPrefix(){}        // Возвращает префикс для сессии
    protected function getRequestChat(){}       // Получает id чата из которого сделан запрос
    protected function getRequestSender(){}     // Получает id отправителя (может отличаться от id чата если это группа)
    protected function sendChatHistory($whom_id, $which_session){}     // Отправляет историю чата
    protected function userLink($id){}                  // Ссылка на пользователя с заданным id
    
}
