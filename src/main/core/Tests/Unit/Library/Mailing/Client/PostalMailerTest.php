<?php

namespace Claroline\CoreBundle\Tests\Unit\Library\Mailing\Client;

use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Mailing\Client\PostalMailer;
use Claroline\CoreBundle\Library\Mailing\Client\PostalRequestClient;
use Claroline\CoreBundle\Library\Mailing\Message;
use Claroline\CoreBundle\Library\Testing\MockeryTestCase;

class PostalMailerTest extends MockeryTestCase
{
    public function testSendFlattensRecipients()
    {
        $config = $this->mock(PlatformConfigurationHandler::class);
        $config->shouldReceive('getParameter')
            ->with('mailer_host')
            ->once()
            ->andReturn('https://postal.example');
        $config->shouldReceive('getParameter')
            ->with('mailer_api_key')
            ->once()
            ->andReturn('secret-key');
        $config->shouldReceive('getParameter')
            ->with('mailer_tag')
            ->once()
            ->andReturn('tag-1');

        $sendMessage = \Mockery::mock();
        $sendMessage->shouldReceive('bcc')->once()->with('bcc1@claroline.com');
        $sendMessage->shouldReceive('bcc')->once()->with('bcc2@claroline.com');
        $sendMessage->shouldReceive('to')->once()->with('to1@claroline.com');
        $sendMessage->shouldReceive('to')->once()->with('to2@claroline.com');
        $sendMessage->shouldReceive('from')->once()->with('from@claroline.com');
        $sendMessage->shouldReceive('tag')->once()->with('tag-1');
        $sendMessage->shouldReceive('subject')->once()->with('Subject');
        $sendMessage->shouldReceive('htmlBody')->once()->with('<p>Hello</p>');
        $sendMessage->shouldReceive('replyTo')->once()->with('reply_to@claroline.com');
        $sendMessage->shouldReceive('send')->once()->andReturn('ok');

        $mailer = \Mockery::mock(PostalMailer::class, [$config])->makePartial()->shouldAllowMockingProtectedMethods();
        $mailer->shouldReceive('createSendMessage')->once()->andReturn($sendMessage);

        $message = new Message();
        $message->subject('Subject');
        $message->from('from@claroline.com');
        $message->replyTo('reply_to@claroline.com');
        $message->body('<p>Hello</p>');
        $message->bcc(['bcc1@claroline.com', 'bcc2@claroline.com']);
        $message->to(['to1@claroline.com', 'to2@claroline.com']);

        $this->assertSame('ok', $mailer->send($message));
    }

    public function testCreateSendMessageUsesTimeoutAwarePostalClient()
    {
        $config = $this->mock(PlatformConfigurationHandler::class);
        $config->shouldReceive('getParameter')
            ->with('mailer_host')
            ->once()
            ->andReturn('https://postal.example');
        $config->shouldReceive('getParameter')
            ->with('mailer_api_key')
            ->once()
            ->andReturn('secret-key');

        $mailer = new class($config) extends PostalMailer {
            public function exposeCreateSendMessage()
            {
                return $this->createSendMessage();
            }
        };

        $sendMessage = $mailer->exposeCreateSendMessage();

        $sendMessageReflection = new \ReflectionObject($sendMessage);
        $clientProperty = $sendMessageReflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($sendMessage);

        $this->assertInstanceOf(PostalRequestClient::class, $client);

        $clientReflection = new \ReflectionObject($client);
        $hostProperty = $clientReflection->getProperty('host');
        $hostProperty->setAccessible(true);
        $serverKeyProperty = $clientReflection->getProperty('serverKey');
        $serverKeyProperty->setAccessible(true);

        $this->assertSame('https://postal.example', $hostProperty->getValue($client));
        $this->assertSame('secret-key', $serverKeyProperty->getValue($client));
    }
}
