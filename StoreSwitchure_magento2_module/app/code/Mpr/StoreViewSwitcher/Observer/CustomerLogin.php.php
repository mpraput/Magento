<?php

namespace Mpr\StoreViewSwitcher\Observer;

use Magento\Store\Model\Store;
use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManagerInterface;

    protected $httpContext;

    protected $storeCookieManager;

    protected $storeRepository;
    
    public function __construct(
        \Magento\Framework\App\Http\Context  $httpContext,
        \Magento\Store\Api\StoreCookieManagerInterface  $storeCookieManager,
        \Magento\Store\Api\StoreRepositoryInterface  $storeRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->httpContext              = $httpContext;
        $this->storeCookieManager       = $storeCookieManager;
        $this->storeRepository          = $storeRepository;
        $this->_storeManagerInterface   = $storeManagerInterface;
    }


    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {   

        $customer = $observer->getEvent()->getCustomer();
        
        //$mycode    = strtolower($customer->getMycode());   
        $mycode    = 'your customer attribute value';  // make change as per requirement
        
        switch ($mycode) {
            case "storeViewCode1":
                $store = $this->storeRepository->getActiveStoreByCode('storeViewCode1'); // storeViewCode1 your store view code
                $this->httpContext->setValue(Store::ENTITY, 'storeViewCode1', 'english'); // english is default store view code
                $this->storeCookieManager->setStoreCookie($store);        
                break;
            case "storeViewCode2":
                $store = $this->storeRepository->getActiveStoreByCode('storeViewCode2');
                $this->httpContext->setValue(Store::ENTITY, 'storeViewCode2', 'english');
                $this->storeCookieManager->setStoreCookie($store);                  
                break;
            default:
                $store = $this->storeRepository->getActiveStoreByCode('english');
                $this->httpContext->setValue(Store::ENTITY, 'english', 'english');
                $this->storeCookieManager->setStoreCookie($store);                
        }
        
    }
}
