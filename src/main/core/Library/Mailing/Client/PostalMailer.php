<?php

namespace Claroline\CoreBundle\Library\Mailing\Client;

use Claroline\CoreBundle\Library\Configuration\PlatformConfigurationHandler;
use Claroline\CoreBundle\Library\Mailing\Message;
use Postal\SendMessage;

class PostalMailer implements MailClientInterface
{
    /** @var PlatformConfigurationHandler */
    private $ch;

    public function __construct(PlatformConfigurationHandler $ch)
    {
        $this->ch = $ch;
    }

    public function getTransports()
    {
        return ['postal'];
    }

    public function send(Message $message)
    {
        $sendMessage = $this->createSendMessage();
        foreach ($message->getAttribute('bcc') as $bcc) {
            $sendMessage->bcc($bcc);
        }

        foreach ($message->getAttribute('to') as $recipient) {
            $sendMessage->to($recipient);
        }

        $sendMessage->from($message->getAttribute('from'));

        $tag = $this->ch->getParameter('mailer_tag');
        if ($tag) {
            $sendMessage->tag($tag);
        }

        $sendMessage->subject($message->getAttribute('subject'));
        $sendMessage->htmlBody($message->getAttribute('body'));

        if ($message->hasAttribute('reply_to')) {
            $sendMessage->replyTo($message->getAttribute('reply_to'));
        }

        foreach ($message->getAttribute('attachments') as $attachment) {
            $sendMessage->attach($attachment['name'], $attachment['type'], file_get_contents($attachment['url']));
        }

        return $sendMessage->send();
    }

    protected function createSendMessage()
    {
        $client = new PostalRequestClient(
            $this->ch->getParameter('mailer_host'),
            $this->ch->getParameter('mailer_api_key')
        );

        return new SendMessage($client);
    }
}
