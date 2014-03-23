<?php
namespace Splot\FrameworkExtraModule\Mailer;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

use Twig_Environment;

use Swift_Mailer;
use Swift_MailTransport;
use Swift_NullTransport;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Swift_Transport;

use MD\Foundation\Exceptions\InvalidArgumentException;
use MD\Foundation\Utils\StringUtils;

use Splot\Framework\Resources\Finder;
use Splot\Framework\Resources\Exceptions\ResourceNotFoundException;

use Splot\FrameworkExtraModule\Mailer\Exceptions\MailNotFoundException;
use Splot\FrameworkExtraModule\Mailer\Swiftmailer\NullTransport;
use Splot\FrameworkExtraModule\Mailer\Message;

class Mailer implements LoggerAwareInterface
{

    /**
     * Splot Framework Resource Finder.
     * 
     * @var Finder
     */
    protected $resourceFinder;

    /**
     * Twig templating system.
     * 
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * Registered logger.
     * 
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Mailer.
     * 
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * Configuration array.
     * 
     * @var array
     */
    protected $config = array();

    /**
     * Default configuration array.
     * 
     * @var array
     */
    protected $defaults = array(
        'from' => null,
        'force_to' => null,
        'bcc' => null,
        'transport' => null,
        'sendmail_cmd' => '/usr/sbin/exim -bs',
        'smtp_host' => null,
        'smtp_port' => 25,
        'smtp_encrypt' => null,
        'smtp_username' => null,
        'smtp_password' => null
    );

    /**
     * Constructor.
     * 
     * @param Finder $resourceFinder Splot Framework Resource Finder.
     * @param Twig_Environment $twig Twig templating system.
     * @param LoggerInterface $logger Logger to use.
     * @param array $config Array of configuration options. Must contain 'from' key with a valid email address.
     * 
     * @throws \RuntimeException When there is a configuration error.
     */
    public function __construct(
        Finder $resourceFinder,
        Twig_Environment $twig,
        LoggerInterface $logger,
        array $config
    ) {
        $this->resourceFinder = $resourceFinder;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->config = array_merge($this->defaults, $config);

        if (empty($this->config['from']) || !$this->verifyEmailAddress($this->config['from'], false)) {
            throw new \RuntimeException('Invalid "From:" email address specified. Mailer has to have a default "From:" address defined.');
        }

        if ($this->config['bcc'] !== null && !$this->verifyEmailAddress($this->config['bcc'])) {
            throw new \RuntimeException('Invalid "Bcc:" email address specified.');
        }

        if ($this->config['force_to'] !== null && !$this->verifyEmailAddress($this->config['force_to'])) {
            throw new \RuntimeException('Invalid "To:" email address specified for forcing recipient address.');
        }

        if (empty($this->config['transport']) || !in_array($this->config['transport'], array('smtp', 'sendmail', 'mail', 'test'))) {
            throw new \RuntimeException('None or invalid transport specified in config.');
        }

        if ($this->config['transport'] === 'smtp'
            && (empty($this->config['smtp_host']) || empty($this->config['smtp_port'])
                || empty($this->config['smtp_username']) || empty($this->config['smtp_password'])
            )
        ) {
            throw new \RuntimeException('Invalid SMTP transport configuration given.');
        }

        if ($this->config['transport'] === 'sendmail' && empty($this->config['sendmail_cmd'])) {
            throw new \RuntimeException('No initialization command for sendmail transport given.');
        }
    }

    /**
     * Sends a previously prepared email Message to the given recipients.
     * 
     * Returns true on success and false on error.
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

        $logData = array_merge($logData, array(
            'to' => $to,
            'subject' => $message->getSubject(),
            'transport' => $this->config['transport'],
            'message' => array(
                'id' => $message->getId()
            )
        ));

        if (!empty($this->config['force_to'])) {
            $message->setTo($this->config['force_to']);
            $logData['to_forced'] = $this->config['force_to'];
        } else {
            $message->setTo($to);
        }

        $message->setFrom($this->config['from']);
        $logData['from'] = $this->config['from'];

        $totalRecipients = is_string($to) ? 1 : count($to);

        if (!empty($this->config['bcc'])) {
            $message->setBcc($this->config['bcc']);
            $totalRecipients = $totalRecipients + (is_string($this->config['bcc']) ? 1 : count($this->config['bcc']));
            $logData['bcc'] = $this->config['bcc'];
        }

        $mailer = $this->provideMailer();

        $deliveryCount = $mailer->send($message, $failures);
        $success = $deliveryCount === $totalRecipients;

        // log it!
        $logData['message']['to'] = $message->getTo();
        $logData['message']['cc'] = $message->getCc();
        $logData['message']['bcc'] = $message->getBcc();
        $logMessage = 'Sent email with subject "'. $message->getSubject() .'" to '. $deliveryCount .' / '. $totalRecipients .' recipients (To: '. (is_string($to) ? $to : json_encode($to)) .').';
        if ($success) {
            $this->logger->info($logMessage, $logData);
        } else {
            // @codeCoverageIgnoreStart
            // can't really test failures atm
            $logData['failures'] = $failures;
            $this->logger->notice($logMessage, $logData);
            // @codeCoverageIgnoreEnd
        }

        return $success;
    }

    /**
     * Composes an email from the given name (defined as templates inside the app) and sends it.
     * 
     * @param string $name Name of the email to send. In Resource format.
     * @param string|array $to Recipient(s) of the email.
     * @param array $variables [optional] Any variables to be used inside the email template.
     * @param array $logData [optional] Any additional log data that should be logged regarding this email.
     * @return bool
     */
    public function sendEmail($name, $to, array $variables = array(), array $logData = array()) {
        $message = $this->composeMessage($name, $variables);
        return $this->sendMessage($message, $to);
    }

    /**
     * Composes an email from the given subject and content and sends it.
     * 
     * @param string $subject Email subject.
     * @param string $content Content's of the email.
     * @param bool $html [optional] Is it HTML encoded email?
     * @param array $logData [optional] Any additional log data that should be logged regarding this email.
     * @return bool
     */
    public function mail($subject, $content, $to, $html = false, array $logData = array()) {
        $message = Message::newInstance();
        $message->setSubject($subject);
        $message->setBody($content, $html ? 'text/html' : 'text/plan');
        return $this->sendMessage($message, $to);
    }

    /**
     * Composes an email message from the definition by the given name.
     * 
     * @param string $name Name of the message to compose. Must be usable by Splot Framework Resources Finder
     *                     and not contain the final file extensions.
     * @param array $variables [optional] Variables for replacement in the templates.
     * @return Message
     */
    public function composeMessage($name, array $variables = array()) {
        $templates = array(
            'subject' => null,
            'html' => null,
            'text' => null
        );

        foreach($templates as $type => $path) {
            try {
                $templates[$type] = $this->resourceFinder->find($name .'.'. $type .'.twig', 'email');
            } catch(ResourceNotFoundException $e) {} // ignore
        }

        if ($templates['subject'] === null) {
            throw new MailNotFoundException('Could not find "'. $name .'.subject.twig" file to determine mail subject.', 404);
        }

        if ($templates['html'] === null && $templates['text'] === null) {
            throw new MailNotFoundException('Could not find neither html nor text templates for mail "'. $name .'".');
        }

        $message = Message::newInstance();
        $message->setSubject($this->twig->render($templates['subject'], $variables));

        if ($templates['html'] !== null) {
            $message->setBody($this->twig->render($templates['html'], $variables), 'text/html');

            if ($templates['text']) {
                $message->addPart($this->twig->render($templates['text'], $variables), 'text/plain');
            }
        } else {
            $message->setBody($this->twig->render($templates['text'], $variables), 'text/plain');
        }

        return $message;
    }

    /*****************************************
     * HELPERS
     *****************************************/
    /**
     * Provides mailer to send the emails through.
     * 
     * @return Swift_Mailer
     */
    protected function provideMailer() {
        if ($this->mailer) {
            return $this->mailer;
        }

        switch($this->config['transport']) {
            case 'mail':
                $transport = Swift_MailTransport::newInstance();
            break;

            case 'sendmail':
                $transport = Swift_SendmailTransport::newInstance($this->config['sendmail_cmd']);
            break;

            case 'smtp':
                $transport = Swift_SmtpTransport::newInstance()
                    ->setHost($this->config['smtp_host'])
                    ->setPort($this->config['smtp_port'])
                    ->setEncryption($this->config['smtp_encrypt'])
                    ->setUsername($this->config['smtp_username'])
                    ->setPassword($this->config['smtp_password']);
            break;

            case 'test':
                $transport = NullTransport::newInstance();
            break;
        }

        $this->mailer = Swift_Mailer::newInstance($transport);
        return $this->mailer;
    }

    /**
     * Verifies the given email address does it fit the patterns of email addresses for the Mailer.
     * 
     * It can be an email string, an array of email strings or an array where keys are emails and values are names.
     * 
     * @param string|array $address Address to verify.
     * @param bool $allowMulti Are multiple emails allowed? Default: true.
     * @return bool
     * 
     * @throws InvalidArgumentException When $address is neither string nor array.
     */
    public function verifyEmailAddress($address, $allowMulti = true) {
        if (!is_string($address) && !is_array($address)) {
            throw new InvalidArgumentException('string or array', $address);
        }

        if (is_string($address)) {
            return StringUtils::isEmail($address);
        }

        if (!$allowMulti && count($address) > 1) {
            return false;
        }

        foreach($address as $key => $value) {
            if (is_string($key)) {
                if (!StringUtils::isEmail($key)) {
                    return false;
                }
            } else {
                if (!StringUtils::isEmail($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Sets the logger.
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
        $this->logger = $logger;
    }

}