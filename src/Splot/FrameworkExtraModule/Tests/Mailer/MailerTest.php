<?php
namespace Splot\FrameworkExtraModule\Tests\Mailer;

use Swift_TransportException;

use MD\Foundation\Exceptions\InvalidArgumentException;

use Splot\Framework\Resources\Exceptions\ResourceNotFoundException;

use Splot\FrameworkExtraModule\Mailer\Mailer;
use Splot\FrameworkExtraModule\Mailer\Message;

/**
 * @coversDefaultClass Splot\FrameworkExtraModule\Mailer\Mailer
 */
class MailerTest extends \PHPUnit_Framework_TestCase
{

    // @todo Test logging.
    
    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfig($config, $msg = null) {
        $mocks = $this->provideMocks();
        $mocks['config'] = $config;

        $exceptionThrown = false;
        $message = null;

        try {
            $mailer = $this->provideMailer($mocks);
        } catch(\RuntimeException $e) {
            $exceptionThrown = true;
            $message = $e->getMessage();
        }

        $this->assertTrue($exceptionThrown);

        if ($msg) {
            $this->assertContains($msg, $message);
        }
    }

    public function testComposingMessage() {
        $variables = array(
            'user' => 'James',
            'title' => 'CEO of Lipsum',
            'offer' => 'discount',
            'why' => 'because'
        );

        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->returnValue('/some/dummy/path'));
        $mocks['twig']->expects($this->any())
            ->method('render')
            ->with($this->anything(), $variables)
            ->will($this->onConsecutiveCalls(
                $this->returnValue('This is some title'),
                $this->returnValue('This is HTML content'),
                $this->returnValue('This is text content')
            ));
        $mailer = $this->provideMailer($mocks);

        $message = $mailer->composeMessage('::test', $variables);

        $this->assertTrue($message instanceof Message);
        $this->assertEquals('This is some title', $message->getSubject());
        $this->assertEquals('This is HTML content', $message->getBody());
        $this->assertContains('This is text content', $message->toString());
        $this->assertContains('Content-Type: text/html; charset=utf-8', $message->toString());
        $this->assertContains('Content-Type: text/plain; charset=utf-8', $message->toString());
    }

    public function testComposingOnlyTextMessage() {
        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('/some/dummy/path'),
                $this->throwException(new ResourceNotFoundException()),
                $this->returnValue('/some/dummy/path')
            ));
        $mocks['twig']->expects($this->any())
            ->method('render')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('This is some title'),
                $this->returnValue('This is text content')
            ));
        $mailer = $this->provideMailer($mocks);

        $message = $mailer->composeMessage('::test');

        $this->assertTrue($message instanceof Message);
        $this->assertEquals('This is some title', $message->getSubject());
        $this->assertEquals('This is text content', $message->getBody());
        $this->assertNotContains('Content-Type: text/html; charset=utf-8', $message->toString());
        $this->assertContains('Content-Type: text/plain; charset=utf-8', $message->toString());
    }

    public function testComposingOnlyHTMLMessage() {
        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('/some/dummy/path'),
                $this->returnValue('/some/dummy/path'),
                $this->throwException(new ResourceNotFoundException())
            ));
        $mocks['twig']->expects($this->any())
            ->method('render')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('This is some title'),
                $this->returnValue('This is HTML content')
            ));
        $mailer = $this->provideMailer($mocks);

        $message = $mailer->composeMessage('::test');

        $this->assertTrue($message instanceof Message);
        $this->assertEquals('This is some title', $message->getSubject());
        $this->assertEquals('This is HTML content', $message->getBody());
        $this->assertContains('Content-Type: text/html; charset=utf-8', $message->toString());
        $this->assertNotContains('Content-Type: text/plain; charset=utf-8', $message->toString());
    }

    /**
     * @expectedException \Splot\FrameworkExtraModule\Mailer\Exceptions\MailNotFoundException
     */
    public function testComposingMessageAndNotFindingSubjectFile() {
        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new ResourceNotFoundException()), // subject
                $this->returnValue('This is HTML content'),
                $this->returnValue('This is text content')
            ));

        $mailer = $this->provideMailer($mocks);

        $mailer->composeMessage('::test');
    }

    /**
     * @expectedException \Splot\FrameworkExtraModule\Mailer\Exceptions\MailNotFoundException
     */
    public function testComposingMessageAndNotFindingAnyTemplates() {
        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('test.subject.twig'),
                $this->throwException(new ResourceNotFoundException()),
                $this->throwException(new ResourceNotFoundException())
            ));

        $mailer = $this->provideMailer($mocks);

        $mailer->composeMessage('::test');
    }

    public function testSendingMessage() {
        $mocks = $this->provideMocks();
        // make sure that logging happened
        $mocks['logger']->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->anything());
        $mailer = $this->provideMailer($mocks);
        $message = $this->provideMessage();

        $success = $mailer->sendMessage($message, 'spam@domain.com');
        $this->assertTrue($success);
    }

    /**
     * @expectedException \MD\Foundation\Exceptions\InvalidArgumentException
     */
    public function testSendingMessageWithInvalidAddress() {
        $mailer = $this->provideMailer();
        $message = $this->provideMessage();

        $mailer->sendMessage($message, 'lipsum.com');
    }

    public function testForcingRecipient() {
        $mocks = $this->provideMocks();
        $mocks['config']['force_to'] = 'forced@domain.com';
        $mocks['logger']->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->callback(function($logData) {
                if ($logData['to_forced'] === 'forced@domain.com'
                    && count($logData['message']['to']) === 1
                    && array_key_exists('forced@domain.com', $logData['message']['to'])
                ) {
                    return true;
                }
                return false;
            }));
        $mailer = $this->provideMailer($mocks);

        $message = $this->provideMessage();

        $success = $mailer->sendMessage($message, 'custom@domain.com');
    }

    public function testAddingBcc() {
        $mocks = $this->provideMocks();
        $mocks['config']['bcc'] = 'observer@domain.com';
        $mocks['logger']->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->callback(function($logData) {
                if ($logData['bcc'] === 'observer@domain.com'
                    && count($logData['message']['bcc']) === 1
                    && array_key_exists('observer@domain.com', $logData['message']['bcc'])
                ) {
                    return true;
                }
                return false;
            }));
        $mailer = $this->provideMailer($mocks);

        $message = $this->provideMessage();

        $success = $mailer->sendMessage($message, 'custom@domain.com');
        $this->assertTrue($success);
    }

    public function testSendEmail() {
        $variables = array(
            'var1' => 'val1',
            'var2' => 'val2'
        );
        $mocks = $this->provideMocks();
        $mocks['resource_finder']->expects($this->any())
            ->method('find')
            ->will($this->returnValue('/some/dummy/path'));
        $mocks['twig']->expects($this->any())
            ->method('render')
            ->with($this->anything(), $variables)
            ->will($this->onConsecutiveCalls(
                $this->returnValue('This is some title'),
                $this->returnValue('This is HTML content'),
                $this->returnValue('This is text content')
            ));
        $mailer = $this->provideMailer($mocks);

        $success = $mailer->sendEmail('::test', 'custom@domain.com', $variables);
        $this->assertTrue($success);
    }

    public function testMail() {
        $mocks = $this->provideMocks();
        $mocks['logger']->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->callback(function($logData) {
                if ($logData['subject'] === 'Email subject'
                    && $logData['to'] === 'custom@domain.com'
                    && count($logData['message']['to']) === 1
                    && array_key_exists('custom@domain.com', $logData['message']['to'])
                ) {
                    return true;
                }
                return false;
            }));

        $mailer = $this->provideMailer($mocks);

        $success = $mailer->mail('Email subject', 'Some email content', 'custom@domain.com');
        $this->assertTrue($success);
    }

    /**
     * @dataProvider provideTransportConfigs
     */
    public function testSettingAllTransports(array $config) {
        $mocks = $this->provideMocks();
        $mocks['config'] = array_merge($mocks['config'], $config);
        $mailer = $this->provideMailer($mocks);

        try {
            $success = $mailer->mail('Email subject', 'Some email content', 'custom@domain.com');
        } catch(Swift_TransportException $e) {} // ignore as obviously we can't send emails via all transports
    }

    public function testMultipleMails() {
        $mailer = $this->provideMailer();

        for($i = 1; $i <= 10; $i++) {
            $mailer->mail('Email #'. $i, 'Some email content', 'custom@domain.com');
        }
    }

    public function testVerifyingEmailAddresses() {
        $mailer = $this->provideMailer();

        $this->assertTrue($mailer->verifyEmailAddress('lipsum@domain.com'));
        $this->assertFalse($mailer->verifyEmailAddress('lorem_ipsum'));
        $this->assertFalse($mailer->verifyEmailAddress('a@b.c'));
        $this->assertTrue($mailer->verifyEmailAddress(array('lipsum@domain.com' => 'Lorem ipsum')));
        $this->assertFalse($mailer->verifyEmailAddress(array('Lorem ipsum' => 'lipsum@domain.com')));
        $this->assertTrue($mailer->verifyEmailAddress(array(
            'lipsum@domain.com' => 'Lorem ipsum',
            'email@domain.com' => 'Somebodys email',
            'address@address.com' => 'Another email address'
        )));
        $this->assertFalse($mailer->verifyEmailAddress(array(
            'lipsum@domain.com' => 'Lorem ipsum',
            'email@domain.com' => 'Somebodys email',
            'address@address.com' => 'Another email address'
        ), false));
        $this->assertTrue($mailer->verifyEmailAddress(array(
            'lipsum@domain.com' => 'Lorem ipsum',
            'email@whatever.com',
            'email@domain.com' => 'Somebodys email',
            'address@address.com' => 'Another email address',
            'nameless@email.com'
        )));
        $this->assertFalse($mailer->verifyEmailAddress(array(
            'lipsum@domain.com' => 'Lorem ipsum',
            'Name Surname' => 'lipsum@lipsum.com'
        )));
        $this->assertFalse($mailer->verifyEmailAddress(array(
            'lipsum@domain.com' => 'Lorem ipsum',
            'Name Surname'
        )));

        $invalidArgument = false;
        try {
            $mailer->verifyEmailAddress(new \stdClass());
        } catch(InvalidArgumentException $e) {
            $invalidArgument = true;
        }

        $this->assertTrue($invalidArgument);
    }





    /*****************************************
     * CLASS PROVIDERS
     *****************************************/
    protected function provideMailer(array $mocks = array()) {
        if (empty($mocks)) {
            $mocks = $this->provideMocks();
        }
        return new Mailer($mocks['resource_finder'], $mocks['twig'], $mocks['logger'], $mocks['config']);
    }

    protected function provideMocks() {
        $resourceFinder = $this->getMockBuilder('Splot\Framework\Resources\Finder')
            ->disableOriginalConstructor()
            ->getMock();
        $twig = $this->getMock('Twig_Environment');
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $config = array(
            'from' => 'email@whatever.com',
            'transport' => 'test'
        );

        return array(
            'resource_finder' => $resourceFinder,
            'twig' => $twig,
            'logger' => $logger,
            'config' => $config
        );
    }

    protected function provideMessage() {
        $message = new Message();
        $message->setSubject('Test');
        $message->setBody('Test');
        return $message;
    }

    public function provideInvalidConfigs() {
        return array(
            array(array()), // no from, no transport
            array(array(
                'transport' => 'test' // no from
            ), 'From:'),
            array(array(
                'from' => 'no-reply@test.com' // no transport
            ), 'transport'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'lipsum' // invalid transport
            ), 'transport'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'test',
                'force_to' => 'Invalid mate' // invalid forced to address
            ), 'To:'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'mail',
                'bcc' => 'Invalid email' // invalid bcc address
            ), 'Bcc:'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'sendmail',
                'sendmail_cmd' => '' // empty sendmail command
            ), 'sendmail'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'smtp' // no smtp configuration
            ), 'SMTP'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'smtp', // invalid smtp configuration
                'smtp_port' => 25,
                'smtp_username' => 'login',
                'smtp_password' => 'password'
            ), 'SMTP'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'smtp', // invalid smtp configuration
                'smtp_host' => 'localhost',
                'smtp_port' => null,
                'smtp_username' => 'login',
                'smtp_password' => 'password'
            ), 'SMTP'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'smtp', // invalid smtp configuration
                'smtp_host' => 'localhost',
                'smtp_port' => 25,
                'smtp_password' => 'password'
            ), 'SMTP'),
            array(array(
                'from' => 'no-reply@test.com',
                'transport' => 'smtp', // invalid smtp configuration
                'smtp_host' => 'localhost',
                'smtp_port' => 25,
                'smtp_username' => 'login',
            ), 'SMTP')
        );
    }

    public function provideTransportConfigs() {
        return array(
            array(array(
                'transport' => 'mail'
            )),
            array(array(
                'transport' => 'sendmail'
            )),
            array(array(
                'transport' => 'smtp',
                'smtp_host' => 'localhost',
                'smtp_username' => 'username',
                'smtp_password' => 'password'
            )),
            array(array(
                'transport' => 'test'
            ))
        );
    }

}