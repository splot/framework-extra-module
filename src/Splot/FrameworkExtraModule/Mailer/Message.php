<?php
namespace Splot\FrameworkExtraModule\Mailer;

use Swift_Message;

class Message extends Swift_Message
{

    public static function newInstance($subject = null, $body = null, $contentType = null, $charset = null) {
        return new static();
    }

}