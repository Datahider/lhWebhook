<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * lhWebhookInterface - интерфейс для всех классов реализующих обработку 
 * веб-хуков на сайте webhook.dabot.net
 *
 * @author Peter Datahider
 */
interface lhWebhookInterface {
    
    /**
     * public function __construct($token) - конструктор, создающий объект 
     * обработчика вебхука по переданному токену
     * 
     * @param string $token Токен, по которому определяются настройки обработчика
     */
    public function __construct($token);
    
    /**
     * public function run() - основная функция запускающая обработку вебхука
     * 
     * @return string Сткрока, которая должна быть возвращена вызывающей стороне
     */
    public function run();
}
