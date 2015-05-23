<?php
namespace Splot\FrameworkExtraModule\Mailer;

use Psr\Log\LoggerInterface;

use Twig_Environment;

use MD\Foundation\Exceptions\InvalidArgumentException;

use Splot\Framework\Resources\Finder;
use Splot\FrameworkExtraModule\Mailer\Jobs\SendEmailJob;
use Splot\FrameworkExtraModule\Mailer\Mailer;
use Splot\WorkQueueModule\WorkQueue\WorkQueue;

class BackgroundMailer extends Mailer
{

    protected $workQueue;

    public function __construct(
        Finder $resourceFinder,
        Twig_Environment $twig,
        LoggerInterface $logger,
        array $config,
        WorkQueue $workQueue
    ) {
        parent::__construct($resourceFinder, $twig, $logger, $config);
        $this->workQueue = $workQueue;
    }

    /**
     * Sends a previously prepared email Message to the given recipients.
     *
     * Delegates this task to a worker.
     * 
     * Always returns true for success because cannot verify if email will be
     * properly sent by the worker.
     * 
     * @param Message $message Message to be sent.
     * @param string|array $to Recipient(s) of the email.
     * @param array $logData [optional] Any additional log data that should be logged regarding this email.
     * @return bool
     */
    public function sendMessage(Message $message, $to, $logData = array()) {
        if (!$this->verifyEmailAddress($to)) {
            throw new InvalidArgumentException('valid email address', $to, 2);
        }

        $this->workQueue->addJob(SendEmailJob::getName(), array(
            'message' => $message,
            'to' => $to,
            'logData' => $logData
        ));

        return true;
    }

}