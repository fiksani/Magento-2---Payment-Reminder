<?php


namespace Fandi\PaymentReminder\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;

class ReminderSender
{
    protected $storeManager;
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $paymentHelper;
    protected $identityContainer;
    protected $addressRenderer;
    protected $orderCollectionFactory;
    protected $helper;

    /**
     * ReminderSender constructor.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        PaymentHelper $paymentHelper,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Fandi\PaymentReminder\Helper\Data $helper,
        Renderer $addressRenderer
    )
    {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->paymentHelper = $paymentHelper;
        $this->identityContainer = $identityContainer;
        $this->addressRenderer = $addressRenderer;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
    }

    public function send(Order $order)
    {
        if (!$this->helper->isEnable()) return;

        $templateOptions = ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $order->getStore()->getId()];
        $templateVars = [
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentHtml($order),
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'created_at_formatted' => $order->getCreatedAtFormatted(2),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ]
        ];
        $from = ['email' => $this->getStoreEmail() , 'name' => ucwords($this->identityContainer->getEmailIdentity())];
        $this->inlineTranslation->suspend();
        $to = [$order->getCustomerEmail()];
        $transport = $this->transportBuilder->setTemplateIdentifier('payment_reminder_template')
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo($to)
            ->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    private function getStoreEmail()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    private function getPaymentHtml(Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }

    private function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    private function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    public function sendAllPending()
    {
        $pendingOrder = $this->getPendingOrderCollection();
        $counter = 0;
        foreach ($pendingOrder as $order) {
            $this->send($order);
            $counter++;
        }
        return $counter;
    }

    public function getPendingOrderCollection()
    {
        if (!$this->helper->isEnable()) return;

        $afterDay = $this->helper->getDaysReminder();
        $lastTime = time() - (3600 * ($afterDay * 24));
        $from = date('Y-m-d 00:00:00', $lastTime);
        $to = date('Y-m-d 23:59:59', $lastTime);

        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', ['eq'=> 'pending'])
            ->addAttributeToFilter('created_at', array('from' => $from, 'to' => $to));

        return $orderCollection;
    }
}
