<?php

declare(strict_types=1);

/*
 * This file is part of Ymir WordPress plugin.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Plugin\CloudProvider\Aws;

use Ymir\Plugin\Email\Email;
use Ymir\Plugin\Email\EmailClientInterface;

/**
 * The client for AWS SES API.
 */
class SesClient extends AbstractClient implements EmailClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function canSendEmails(): bool
    {
        $response = $this->request('get', '/v2/email/account');

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException('Unable to get SES account details');
        }

        $response = json_decode($response['body'], true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Unable to parse the SES response body');
        } elseif (!isset($response['ProductionAccessEnabled'])) {
            throw new \RuntimeException('Unable to get SES production access status');
        }

        return (bool) $response['ProductionAccessEnabled'];
    }

    /**
     * {@inheritdoc}
     */
    public function sendEmail(Email $email)
    {
        $response = $this->request('post', '/', http_build_query([
            'Action' => 'SendRawEmail',
            'RawMessage.Data' => base64_encode($email->toString()),
        ]));
        $statusCode = $this->parseResponseStatusCode($response);

        if (400 === $statusCode) {
            throw new \RuntimeException(sprintf('SES API request failed: %s', $this->getErrorMessage($response['body'] ?? '')));
        } elseif (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException(sprintf('SES API request failed with status code %d', $statusCode));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEndpointName(): string
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    protected function getService(): string
    {
        return 'ses';
    }

    /**
     * Get the SES error message.
     */
    private function getErrorMessage($body): string
    {
        $body = simplexml_load_string($body);

        if (!$body instanceof \SimpleXMLElement) {
            return '[unable to parse error message]';
        }

        return (string) $body->Error->Message;
    }
}
