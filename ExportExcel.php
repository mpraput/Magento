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


use Magento\Ui\Model\Export\SearchResultIteratorFactory;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Convert\Excel;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\LocalizedException;


class ExportExcel extends \Magento\Backend\App\Action
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
    /**
     * @var SearchResultIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var ExcelFactory
     */
    protected $excelFactory;
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $options;


    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Filter $filter,
        Filesystem $filesystem,
        ConvertToCsv $converter,
        FileFactory $fileFactory,
        \Magento\Ui\Model\Export\MetadataProvider $metadataProvider,
        \Ib\Byte\Model\ResourceModel\Byte $resource,
        SearchResultIteratorFactory $iteratorFactory,
        ExcelFactory $excelFactory,
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
            $this->iteratorFactory = $iteratorFactory;
            $this->excelFactory = $excelFactory;
    }

     /**
     * export.
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $component  = $this->filter->getComponent();
        $name       = md5(microtime());
        $file       = 'export/'. $component->getName() . $name . '.xls';

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $component->getContext()->getDataProvider()->setLimit(0, 0);

        /** @var SearchResultInterface $searchResult */
        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();

        /** @var DocumentInterface[] $searchResultItems */        
        $searchResultItems = $searchResult->getItems();
        
        foreach ($searchResultItems as $document) {
            $this->metadataProvider->convertDate($document, $component->getName());
        }
        

        /** @var SearchResultIterator $searchResultIterator */
        $searchResultIterator = $this->iteratorFactory->create(['items' => $searchResultItems]);

        /** @var Excel $excel */
        $excel = $this->excelFactory->create([
            'iterator' => $searchResultIterator,
            'rowCallback'=> [$this, 'getRowData'],
        ]);

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();

        $excel->setDataHeader($this->metadataProvider->getHeaders($component));
        $excel->write($stream, $component->getName() . '.xls'); 

        $stream->unlock();
        $stream->close();
        return $this->fileFactory->create('export.xls', [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ], 'var');

    }
     /**
     * Returns row data
     *
     * @param DocumentInterface $document
     * @return array
     */
    public function getRowData(DocumentInterface $document)
    {
        return $this->metadataProvider->getRowData($document, $this->getFields(), $this->getOptions());
    }

      /**
     * Returns DB fields list
     *
     * @return array
     */
    protected function getFields()
    {
        if (!$this->fields) {
            $component = $this->filter->getComponent();
            $this->fields = $this->metadataProvider->getFields($component);
        }
        return $this->fields;
    }


    /**
     * Returns Filters with options
     *
     * @return array
     */
    protected function getOptions()
    {
        if (!$this->options) {
            $this->options = $this->metadataProvider->getOptions();
        }
        return $this->options;
    }
}