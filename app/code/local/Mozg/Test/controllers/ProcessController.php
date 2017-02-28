<?php
/**
 * Copyright © 2016 Mozg. All rights reserved.
 * See LICENSE.txt for license details.
 */

class Mozg_Test_ProcessController extends Mage_Core_Controller_Front_Action {

    // /index.php/mozg_test/process/index/
    public function indexAction() {

        echo '1';
    }

    // /index.php/mozg_test/process/cron/
    public function cronAction()
    {
        $cron = Mage::getModel('mozg_test/cronjob')->ordersInvoice();

        Zend_Debug::dump($cron);
    }

}
