<?php
namespace Splot\FrameworkExtraModule\Mailer\Swiftmailer;

use Swift_Mime_Message;
use Swift_NullTransport;

class NullTransport extends Swift_NullTransport
{

    /**
     * Create a new NullTransport instance.
     *
     * @return NullTransport
     */
    public static function newInstance() {
        return new static();
    }

    /**
     * Sends the given message.
     *
     * @param Swift_Mime_Message $message
     * @param string[]           $failedRecipients An array of failures by-reference
     * @return integer The number of sent emails
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null) {
        parent::send($message, $failedRecipients);

        $count = (
            count((array) $message->getTo())
            + count((array) $message->getCc())
            + count((array) $message->getBcc())
        );
        return $count;
    }

}