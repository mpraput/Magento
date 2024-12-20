
app/code/SB/SeoManagement/etc/frontend/events.xml 

      <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework/Event/etc/events.xsd">
        <event name="layout_load_before">
            <observer name="add_meta_robots" instance="SB\SeoManagement\Observer\AddMetaRobots" />
        </event>
    </config>

    
    

      app/code/SB/SeoManagement/Observer/AddMetaRobots.php

  

namespace SB\SeoManagement\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;

class AddMetaRobots implements ObserverInterface
{
    protected $registry;
    protected $request;
    protected $layoutFactory;

    public function __construct(
        Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Page\Config $layoutFactory
    ) {
        $this->registry = $registry;
        $this->request = $request;
        $this->layoutFactory = $layoutFactory;
    }

    public function execute(Observer $observer)
    {
        $fullActionName = $observer->getFullActionName();       
        if ($fullActionName == "catalog_product_view"){
            $product = $this->registry->registry('current_product');
            if ($product && !$product->isSaleable()) {
                $this->layoutFactory->setRobots('NOINDEX,NOFOLLOW');
            }            
        }
    }
}

    