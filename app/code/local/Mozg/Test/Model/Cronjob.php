<?php
/**
 * Copyright © 2016 Mozg. All rights reserved.
 * See LICENSE.txt for license details.
 */

class Mozg_Test_Model_Cronjob {

    /**
     * @var array $data
     */
    protected $debugData  = array();

    public function ordersInvoice()
    {

        $this->debugData[] = __METHOD__;

        $collection = Mage::getModel('sales/order')
                    ->getCollection()
                    ->setOrder('created_at', 'desc')
                    ->setPageSize(1)
                    ->setCurPage(1);

        foreach ($collection as $item) {
            //echo '<pre>';print_r($item->getData());

            $order_id = $item->getData('entity_id');

            $this->debugData[][__LINE__]['_ordersInvoice'] = $order_id;

            $order = Mage::getModel('sales/order')->load($order_id);

            $this->_createInvoice($order);
        }


        $log_data=$this;
        Mage::log("\n".__FILE__." (".__LINE__.")\n".__METHOD__."\n".print_r($log_data,true),null,'mozg_test.log');

        return $this;
    }


    protected function _createInvoice($order)
    {

        $this->debugData[] = __METHOD__;

        $this->debugData[][__LINE__]['_createInvoice'] = 'Creating invoice for order';

        //Set order state to new because with order state payment_review it is not possible to create an invoice
        if (strcmp($order->getState(), Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) == 0) {
            $order->setState(Mage_Sales_Model_Order::STATE_NEW);
        }

        if ($order->canInvoice()) {

            /* We do not use this inside a transaction because order->save() is always done on the end of the notification
             * and it could result in a deadlock see https://github.com/Mozg/magento/issues/334
             */
            try {
                $invoice = $order->prepareInvoice();
                $invoice->getOrder()->setIsInProcess(true);


                    $invoice->register()->pay();


                // set the state to pending because otherwise magento will automatically set it to processing when you save the order
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

                /*
                 * Save the order otherwise in old magento versions our status is not updated the
                 * processing status that it gets here because the invoice is created.
                 */
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();

                $this->debugData[][__LINE__]['_createInvoice done'] = 'Created invoice status is: ' . $order->getStatus() . ' state is:' . $order->getState();
            } catch (Exception $e) {
                $this->debugData[][__LINE__]['_createInvoice error'] = 'Error saving invoice. The error message is: ' . $e->getMessage();
                Mage::logException($e);
            }


            $invoice->sendEmail();

        } else {
            $this->debugData[][__LINE__]['_createInvoice error'] = 'It is not possible to create invoice for this order';

            // TODO: check if pending invoice exists if so capture this invoice
        }
    }

}