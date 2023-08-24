<?php

namespace MDC\CheckoutStep\Controller\DeliverySlot;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class SetDeliveryNote extends Action
{
    
    /**
     * Init
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param array $data
     */
    
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        parent::__construct($context);
    }

    /**
     * Main function
     *
     * @return void|mixed
     */
    public function execute()
    {
        try {
            $postData = $this->getRequest()->getPost();
            $addressId = $postData['addressId'];
            $deliveryNote = "";
            if ($addressId) {
                $connection  =  $this->resource->getConnection();
                $select = $connection->select();
                $select->from( $connection->getTableName('customer_address_entity'), 'address_delivery_instructions');
                $select->where('entity_id = ?', $addressId);
                $deliveryNote = $connection->fetchOne($select);
            }
        } 
        catch (\Exception $e) {
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData(['delivery_note' => $deliveryNote]);
    }
}