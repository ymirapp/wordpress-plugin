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

namespace Ymir\Plugin\Email;

use Ymir\Plugin\EventManagement\EventManager;

class Email
{
    /**
     * The default charset for the email.
     *
     * @var string
     */
    private $defaultCharset;

    /**
     * The default content type for the email.
     *
     * @var string
     */
    private $defaultContentType;

    /**
     * The default from address for the email.
     *
     * @var string
     */
    private $defaultFromAddress;

    /**
     * The plugin event manager.
     *
     * @var EventManager
     */
    private $eventManager;

    /**
     * WordPress PHPMailer object.
     *
     * @var \PHPMailer
     */
    private $mailer;

    /**
     * Constructor.
     */
    public function __construct(EventManager $eventManager, string $defaultFromAddress, \PHPMailer $mailer, string $defaultCharset = 'UTF-8', string $defaultContentType = 'text/plain')
    {
        $this->defaultCharset = $defaultCharset;
        $this->defaultContentType = $defaultContentType;
        $this->defaultFromAddress = $defaultFromAddress;
        $this->eventManager = $eventManager;
        $this->mailer = $mailer;

        // Remove some of the default values so we can check if they were set and passed through the WordPress filters
        $this->mailer->CharSet = '';
        $this->mailer->ContentType = '';
        $this->mailer->From = '';
    }

    /**
     * Add attachments to the email.
     */
    public function attachments($attachments)
    {
        if (is_string($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        } elseif (!is_array($attachments)) {
            throw new \InvalidArgumentException('"attachments" argument must be an array or a string');
        }

        foreach ($attachments as $attachment) {
            $this->mailer->addAttachment($attachment);
        }
    }

    /**
     * Add "bcc" addresses to the email.
     */
    public function bcc($addresses)
    {
        $this->addAddresses('bcc', $addresses);
    }

    /**
     * Set the email body of the email.
     */
    public function body(string $body)
    {
        $this->mailer->Body = $body;
    }

    /**
     * Add "cc" addresses to the email.
     */
    public function cc($addresses)
    {
        $this->addAddresses('cc', $addresses);
    }

    /**
     * Set the charset used by the email.
     */
    public function charset(string $charset)
    {
        $this->mailer->CharSet = $this->eventManager->filter('wp_mail_charset', $charset);
    }

    /**
     * Set the content type of the email.
     */
    public function contentType(string $contentType)
    {
        $contentType = strtolower((string) $this->eventManager->filter('wp_mail_content_type', $contentType));

        $this->mailer->ContentType = $contentType;
        $this->mailer->isHTML('text/html' === $contentType);
    }

    /**
     * Set the from address of the email.
     */
    public function from(string $address)
    {
        $address = $this->parseAddress($address, 'WordPress');

        $this->mailer->setFrom($this->eventManager->filter('wp_mail_from', $address['address']), $this->eventManager->filter('wp_mail_from_name', $address['name']), false);
    }

    /**
     * Add headers to the email.
     */
    public function headers($headers)
    {
        if (empty($headers)) {
            return;
        } elseif (is_string($headers)) {
            $headers = explode("\n", trim(str_replace("\r\n", "\n", $headers)));
        } elseif (!is_array($headers)) {
            throw new \InvalidArgumentException('"headers" argument must be an array or a string');
        }

        foreach ($headers as $header) {
            $this->processHeader($header);
        }
    }

    /**
     * Add "reply-to" addresses to the email.
     */
    public function replyTo($addresses)
    {
        $this->addAddresses('reply-to', $addresses);
    }

    /**
     * Set the subject of the email.
     */
    public function subject(string $subject)
    {
        $this->mailer->Subject = $subject;
    }

    /**
     * Add "to" addreses to the email.
     */
    public function to($addresses)
    {
        $this->addAddresses('to', $addresses);
    }

    /**
     * Convert the email into a string representing the MIME message.
     */
    public function toString(): string
    {
        // Trigger the filters that haven't been triggered already.
        if (empty($this->mailer->CharSet)) {
            $this->charset($this->defaultCharset);
        }
        if (empty($this->mailer->ContentType)) {
            $this->contentType($this->defaultContentType);
        }
        if (empty($this->mailer->From)) {
            $this->from($this->defaultFromAddress);
        }

        $this->eventManager->execute('phpmailer_init', $this->mailer);

        $this->mailer->preSend();

        return $this->mailer->getSentMIMEMessage();
    }

    /**
     * Add an address to the email.
     */
    private function addAddresses(string $type, $addresses)
    {
        $method = '';
        $type = strtolower($type);

        if (is_string($addresses)) {
            $addresses = explode(',', $addresses);
        } elseif (!is_array($addresses)) {
            throw new \InvalidArgumentException('"addresses" argument must be an array or a string');
        }

        if ('to' === $type) {
            $method = 'addAddress';
        } elseif ('cc' === $type) {
            $method = 'addCC';
        } elseif ('bcc' === $type) {
            $method = 'addBCC';
        } elseif ('reply-to' === $type) {
            $method = 'addReplyTo';
        }

        if (empty($method)) {
            throw new \InvalidArgumentException('Invalid address "type" given');
        }

        foreach (array_map([$this, 'parseAddress'], $addresses) as $address) {
            $this->mailer->$method($address['address'], $address['name']);
        }
    }

    /**
     * Parse the given address into an email address and a recipient name.
     */
    private function parseAddress(string $address, string $name = ''): array
    {
        if (false === preg_match('/(.*)<(.+)>/', $address, $matches)) {
            throw new \RuntimeException('Unable to parse the given "address"');
        } elseif (3 === count($matches)) {
            $address = $matches[2];
            $name = $matches[1];
        }

        return array_map('trim', [
            'address' => $address,
            'name' => $name,
        ]);
    }

    /**
     * Process the given "content-type" header body and add it to the email.
     */
    private function processContentTypeHeader(string $body)
    {
        $body = explode(';', $body);
        $contentType = $body[0];

        if (!empty($body[1]) && false !== stripos($body[1], 'charset=')) {
            $this->charset(trim(str_replace(['charset=', '"'], '', $body[1])));
        } elseif (!empty($body[1]) && false !== stripos($contentType, 'multipart') && false !== stripos($body[1], 'boundary=')) {
            $this->mailer->AddCustomHeader(sprintf("Content-Type: %s;\n\t boundary=\"%s\"", $contentType, trim(str_replace(['BOUNDARY=', 'boundary=', '"'], '', $body[1]))));
        }

        $this->contentType($contentType);
    }

    /**
     * Process the given header and add it to the email.
     */
    private function processHeader(string $header)
    {
        $header = array_map('trim', explode(':', trim($header), 2));

        if (!is_array($header) || 2 !== count($header)) {
            return;
        }

        $body = $header[1];
        $name = strtolower($header[0]);

        if ('from' === $name) {
            $this->from($body);
        } elseif ('content-type' === $name) {
            $this->processContentTypeHeader($body);
        } elseif (in_array($name, ['bcc', 'cc', 'reply-to'])) {
            $this->addAddresses($name, $body);
        } elseif (!in_array($name, ['mime-version', 'x-mailer'])) {
            $this->mailer->addCustomHeader($name, $body);
        }
    }
}
