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

require_once __DIR__ . '/../interface/lhWebhookInterface.php';
class lhTestWebhook implements lhWebhookInterface {
    
    protected $botdata;

    public function __construct($token) {
        $this->botdata = new lhSessionFile($token);
        if ($this->botdata->get('existing') != 'yes') {
            $this->botdata->destroy();
            throw new Exception("Can't find session id webhook-$token");
        }
    }
    
    public function run() {
        return json_encode([
            'result' => 'ok',
            'test' => 'yes'
        ]);
    }
    
}
