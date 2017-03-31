<?php

use \framework\services\Factory as ServiceFactory;
final class ProfileControl extends \base\Control
{
    
    public function actionIndex()
    {
        //$res = ServiceFactory::getInstance()->getService('ITest')->sayHello('11');
        $this->render('hello', array(
            'key' => 'value'
        ));
    }
}