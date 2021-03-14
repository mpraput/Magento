<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ib\Byte\Controller\Adminhtml\Byte;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToCsv;
use Magento\Framework\App\Response\Http\FileFactory;
use Ib\Byte\Model\ResourceModel\Byte\CollectionFactory;

class Export extends \Magento\Backend\App\Action
{

   /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Massactions filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * @var MetadataProvider
     */
    protected $metadataProvider;
    /**
     * @var WriteInterface
     */
    protected $directory;
    /**
     * @var ConvertToCsv
     */
    protected $converter;
    /**
     * @var FileFactory
     */
    protected $fileFactory;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Filter $filter,
        Filesystem $filesystem,
        ConvertToCsv $converter,
        FileFactory $fileFactory,
        \Magento\Ui\Model\Export\MetadataProvider $metadataProvider,
        \Ib\Byte\Model\ResourceModel\BenfitSelection $resource,
        CollectionFactory $collectionFactory
        ) {
            $this->resources = $resource;
            $this->filter = $filter;
            $this->_connection = $this->resources->getConnection();
            $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $this->metadataProvider = $metadataProvider;
            $this->converter = $converter;
            $this->fileFactory = $fileFactory;
            parent::__construct($context);
            $this->resultForwardFactory = $resultForwardFactory;
            $this->collectionFactory = $collectionFactory;
    }

     /**
     * export.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        //$collection = $this->filter->getCollection($this->collectionFactory->create());
        //var_dump(count($this->collectionFactory->create()));
        $selected = $this->getRequest()->getParam('selected');
        if($selected) {
            $collection = $this->collectionFactory->create();
        }else {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        }
     
        $collection->getSelect()->joinLeft(
                ['secondTable' => 'customer_entity'],
                'main_table.customer_id = secondTable.entity_id',
                ['customer_name' => new \Zend_Db_Expr("group_concat(`secondTable`.firstname,' ',`secondTable`.lastname)")]
                
            )->group('benfitselection_id');       

        $ids = $collection->getAllIds();

        $component = $this->filter->getComponent();
        $this->filter->prepareComponent($component);
        $dataProvider = $component->getContext()->getDataProvider();
        $dataProvider->setLimit(0, false);
        //$ids = [];

        /*foreach ($collection as $document) {
            $ids[] = (int)$document->getId();
        }
*/
        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();
        $fields = $this->metadataProvider->getFields($component);
        $options = $this->metadataProvider->getOptions();
        $name = md5(microtime());
        $file = 'export/'. $component->getName() . $name . '.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($this->metadataProvider->getHeaders($component));

        foreach ($collection as $document) { 
            if( in_array( $document->getId(), $ids ) ) {     
                $itemData = [];
                $itemData['id']             = $document['benfitselection_id'];
                $itemData['benefit_name']   = $document['benefit_product_name'];
                $itemData['customer_name']  = $document['customer_name'];
                $itemData['year']           = $document['year'];
                $itemData['register_dt']    = $document['register_dt'];
                $status   = 'Disable';
                if($document['status'] == 1 ){
                    $status = 'Enable';
                }
                $itemData['status']         = $status;
                $stream->writeCsv($itemData);
            }          
        }

        /*foreach ($searchResult->getItems() as $document) {
            if( in_array( $document->getId(), $ids ) ) {            
                $this->metadataProvider->convertDate($document, $component->getName());
                $stream->writeCsv($this->metadataProvider->getRowData($document, $fields, $options));
            }
        } */

        $stream->unlock();
        $stream->close();
        return $this->fileFactory->create('export.csv', [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ], 'var');

    }
}