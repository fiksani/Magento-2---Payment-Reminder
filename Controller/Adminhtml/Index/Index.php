<?php


namespace Fandi\PaymentReminder\Controller\Adminhtml\Index;


use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    protected $_publicActions = ['index'];

    /** @var \Magento\Sales\Api\OrderRepositoryInterface  */
    protected $orderRepository;

    /** @var \Magento\Framework\Message\ManagerInterface  */
    protected $messageManager;

    /** @var \Fandi\PaymentReminder\Model\Order\Email\Sender\ReminderSender  */
    protected $reminderSender;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Fandi\PaymentReminder\Model\Order\Email\Sender\ReminderSender $reminderSender,
        Action\Context $context
    )
    {
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->reminderSender = $reminderSender;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $orderId = $this->getRequest()->getParam('order_id');

        $order = $this->orderRepository->get($orderId);

        if (!is_null($order) && strtolower($order->getStatus()) === 'pending') {
            try {
                $this->reminderSender->send($order);
                $this->messageManager->addSuccessMessage(__("Email reminder already sent to customer."));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("Email reminder failed to sent."));
            }
        }

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }


}
