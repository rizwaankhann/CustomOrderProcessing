<?php
declare(strict_types=1);

namespace SmartWorking\CustomOrderProcessing\Controller\Adminhtml\OrderStatus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Listing extends Action
{

    /** @var PageFactory */
    private PageFactory $pageFactory;

    /**
     * @param Context $context
     * @param PageFactory $rawFactory
     */
    public function __construct(
        Context $context,
        PageFactory $rawFactory
    ) {
        $this->pageFactory = $rawFactory;
        parent::__construct($context);
    }

    /**
     * @return Page|ResultInterface
     */
    public function execute(): Page|ResultInterface
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('SmartWorking_CustomOrderProcessing::order_status');
        $resultPage->getConfig()->getTitle()->prepend(__('Order Status Changelogs via WebAPI'));
        return $resultPage;
    }
}
