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
            throw new \RuntimeException($this->createExceptionMessage('Unable to get SES account details', $response));
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

        if (200 !== $this->parseResponseStatusCode($response)) {
            throw new \RuntimeException($this->createExceptionMessage('SES API request failed', $response));
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
}
