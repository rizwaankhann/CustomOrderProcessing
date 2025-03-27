<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Controller\Adminhtml\OrderStatus;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use SmartWorking\CustomOrderProcessing\Model\ResourceModel\CustomOrderProcessing\CollectionFactory;

class MassDelete extends Action
{

    /**
     * @var Filter
     */
    protected Filter $filter;
    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute(): Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        $resultRedirect = $this->resultRedirectFactory->create();

        if ($collectionSize) {
            try {
                $count = 0;
                foreach ($collection as $model) {
                    $model->delete();
                    $count++;
                }
                $this->messageManager->addSuccessMessage(__('A total of %1 Log(s) have been deleted.', $count));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('There was a problem deleting the log.'));
            }
            $resultRedirect->setPath('*/*/listing');
            return $resultRedirect;
        }

        $this->messageManager->addErrorMessage(__('We can\'t find a log to delete.'));
        $resultRedirect->setPath('*/*/listing');
        return $resultRedirect;

    }
}
