<?php


namespace Fandi\PaymentReminder\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    public function beforeSetLayout(OrderView $subject)
    {
        $order = $subject->getOrder();

        if (strtolower($order->getStatus()) == 'pending') {
            $subject->addButton(
                'order_send_reminder',
                [
                    'label' => __('Send Reminder'),
                    'class' => __('send-reminder'),
                    'id' => 'order-view-send-reminder',
                    'onclick' => 'setLocation(\'' . $subject->getUrl('paymentreminder/index/index') . '\')'
                ]
            );
        }
    }
}
