<?php
namespace Splot\FrameworkExtraModule\Form\Exceptions;

use Exception;
use RuntimeException;

class SimpleFormErrorException extends RuntimeException
{

    const FORM_ERROR = '$form';

    protected $token;

    protected $field = '$form';

    public function __construct($token, $field = self::FORM_ERROR, $message = null, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);

        $this->token = $token;
        $this->field = $field;
    }

    public function getToken() {
        return $this->token;
    }

    public function getField() {
        return $this->field;
    }

}