<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Nexmo;

trigger_deprecation('symfony/nexmo-notifier', '5.4', 'The "symfony/nexmo-notifier" package is deprecated, use "symfony/vonage-notifier" instead.');

use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since Symfony 5.4, use the Vonage bridge instead.
 */
final class NexmoTransport extends AbstractTransport
{
    protected const HOST = 'rest.nexmo.com';

    private $apiKey;
    private $apiSecret;
    private $from;

    public function __construct(string $apiKey, string $apiSecret, string $from, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->from = $from;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('nexmo://%s?from=%s', $this->getEndpoint(), $this->from);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $response = $this->client->request('POST', 'https://'.$this->getEndpoint().'/sms/json', [
            'body' => [
                'from' => $this->from,
                'to' => $message->getPhone(),
                'text' => $message->getSubject(),
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ],
        ]);

        try {
            $result = $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote Nexmo server.', $response, 0, $e);
        }

        foreach ($result['messages'] as $msg) {
            if ($msg['status'] ?? false) {
                throw new TransportException('Unable to send the SMS: '.$msg['error-text'].sprintf(' (code %s).', $msg['status']), $response);
            }
        }

        $success = $response->toArray(false);

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($success['messages'][0]['message-id']);

        return $sentMessage;
    }
}
