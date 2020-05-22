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

/**
 * A client for sending email using the cloud provider email service.
 */
interface EmailClientInterface
{
    /**
     * Send the given email.
     */
    public function sendEmail(Email $email);
}
