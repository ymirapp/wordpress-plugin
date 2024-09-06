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

use Ymir\Plugin\Email\Email;
use Ymir\Plugin\Email\EmailClientInterface;
use Ymir\Plugin\Plugin;
use Ymir\Plugin\Support\Collection;

/**
 * Pluggable functions used by the Ymir plugin.
 */
global $pagenow, $ymir;

if ($ymir->isSesEnabled() && function_exists('wp_mail') && !in_array($pagenow, ['plugins.php', 'update-core.php'], true)) {
    add_filter('ymir_admin_notices', function ($notices) {
        if ($notices instanceof Collection) {
            $notices[] = [
                'message' => 'Sending emails using SES is disabled because the "wp_mail" function was already overridden by another plugin.',
                'type' => 'warning',
            ];
        }

        return $notices;
    });
} elseif ($ymir->isSesEnabled() && $ymir->isUsingVanityDomain()) {
    add_filter('ymir_admin_notices', function ($notices) {
        if ($notices instanceof Collection) {
            $notices[] = [
                'message' => 'Sending emails using SES is disabled because the site is using a vanity domain. To learn how to map a domain to your environment, check out <a href="https://docs.ymirapp.com/guides/domain-mapping.html">this guide</a>.',
                'type' => 'warning',
            ];
        }

        return $notices;
    });
} elseif ($ymir->isSesEnabled() && !$ymir->isUsingVanityDomain() && !function_exists('wp_mail')) {
    /**
     * Send email using the cloud provider email client.
     */
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        try {
            global $ymir;

            if (!$ymir instanceof Plugin) {
                throw new \RuntimeException('Ymir plugin isn\'t active');
            }

            $client = $ymir->getContainer()->offsetGet('email_client');
            $email = $ymir->getContainer()->offsetGet('email');

            if (!$client instanceof EmailClientInterface) {
                throw new \RuntimeException('Unable to get the email client');
            } elseif (!$email instanceof Email) {
                throw new \RuntimeException('Unable to create an email object');
            }

            $attributes = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));

            $email->to($attributes['to'] ?? $to);
            $email->subject($attributes['subject'] ?? $subject);
            $email->body($attributes['message'] ?? $message);
            $email->headers($attributes['headers'] ?? $headers);
            $email->attachments($attributes['attachments'] ?? $attachments);

            $client->sendEmail($email);

            return true;
        } catch (\Exception $exception) {
            $errorData = compact('to', 'subject', 'message', 'headers', 'attachments');

            if ($exception instanceof phpmailerException) {
                $errorData['phpmailer_exception_code'] = $exception->getCode();
            }

            do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $exception->getMessage(), $errorData));

            return false;
        }
    }
}
