<?php
declare(strict_types=1);

namespace Fandi\PaymentReminder\Cron;

class SendReminder
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Fandi\PaymentReminder\Model\Order\Email\Sender\ReminderSender */
    protected $reminderSender;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Fandi\PaymentReminder\Model\Order\Email\Sender\ReminderSender $reminderSender,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->reminderSender = $reminderSender;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        try {
            $counter = $this->reminderSender->sendAllPending();
            $this->logger->addInfo("[Payment Reminder] - ".$counter." reminder sent.");
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

    }
}

