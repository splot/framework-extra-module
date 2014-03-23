<?php
namespace Splot\FrameworkExtraModule\Mailer\Jobs;

use Splot\FrameworkExtraModule\Mailer\Message;
use Splot\WorkQueueModule\WorkQueue\AbstractJob;

class SendEmailJob extends AbstractJob
{

    public function execute(Message $message, $to, array $logData = array()) {
        $mailer = $this->get('mailer.foreground');
        $mailer->setLogger($this->logger);
        $mailer->sendMessage($message, $to, $logData);
    }

}