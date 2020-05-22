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

namespace Ymir\Plugin\Tests\Unit\Email;

use PHPUnit\Framework\TestCase;
use Ymir\Plugin\Email\Email;
use Ymir\Plugin\Tests\Mock\EventManagerMockTrait;
use Ymir\Plugin\Tests\Mock\PHPMailerMockTrait;

/**
 * @covers \Ymir\Plugin\Email\Email
 */
class EmailTest extends TestCase
{
    use EventManagerMockTrait;
    use PHPMailerMockTrait;

    public function testAttachmentsWithArray()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addAttachment')
                  ->withConsecutive(
                      [$this->identicalTo('attachment1')],
                      [$this->identicalTo('attachment2')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->attachments(['attachment1', 'attachment2']);
    }

    public function testAttachmentsWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"attachments" argument must be an array or a string');

        $email = new Email($this->getEventManagerMock(), 'from_address', $this->getPHPMailerMock());

        $email->attachments(new \stdClass());
    }

    public function testAttachmentsWithString()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addAttachment')
                  ->withConsecutive(
                      [$this->identicalTo('attachment1')],
                      [$this->identicalTo('attachment2')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->attachments("attachment1\r\nattachment2");
    }

    public function testBccWithArray()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addBCC')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->bcc(['test@test.com', 'Foo <foo@bar.com>']);
    }

    public function testBccWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"addresses" argument must be an array or a string');

        $email = new Email($this->getEventManagerMock(), 'from_address', $this->getPHPMailerMock());

        $email->bcc(new \stdClass());
    }

    public function testBccWithString()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addBCC')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->bcc('test@test.com, Foo <foo@bar.com>');
    }

    public function testCharset()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('wp_mail_charset'), $this->identicalTo('UTF-8'))
                     ->willReturn('filtered_UTF-8');

        (new Email($eventManager, 'from_address', $phpmailer))->charset('UTF-8');

        $this->assertSame('filtered_UTF-8', $phpmailer->CharSet);
    }

    public function testConstructorRemovesValues()
    {
        $phpmailer = $this->getPHPMailerMock();

        $this->assertNotSame('', $phpmailer->CharSet);
        $this->assertNotSame('', $phpmailer->ContentType);
        $this->assertNotSame('', $phpmailer->From);

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $this->assertSame('', $phpmailer->CharSet);
        $this->assertSame('', $phpmailer->ContentType);
        $this->assertSame('', $phpmailer->From);
    }

    public function testContentTypeHtml()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->once())
                  ->method('isHTML')
                  ->with($this->identicalTo(true));

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('wp_mail_content_type'), $this->identicalTo('text/html'))
                     ->willReturn('text/html');

        (new Email($eventManager, 'from_address', $phpmailer))->contentType('text/html');

        $this->assertSame('text/html', $phpmailer->ContentType);
    }

    public function testContentTypeNotHtml()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->once())
                  ->method('isHTML')
                  ->with($this->identicalTo(false));

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('wp_mail_content_type'), $this->identicalTo('content_type'))
                     ->willReturn('filtered_content_type');

        (new Email($eventManager, 'from_address', $phpmailer))->contentType('content_type');

        $this->assertSame('filtered_content_type', $phpmailer->ContentType);
    }

    public function testFromWithDefaultNAme()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->exactly(2))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_mail_from'), $this->identicalTo('foo@bar.com')],
                         [$this->identicalTo('wp_mail_from_name'), $this->identicalTo('WordPress')]
                     )
                     ->willReturnOnConsecutiveCalls('filtered_foo@bar.com', 'filtered_WordPress');

        $phpmailer->expects($this->once())
                  ->method('setFrom')
                  ->with($this->identicalTo('filtered_foo@bar.com'), $this->identicalTo('filtered_WordPress'), $this->identicalTo(false));

        (new Email($eventManager, 'from_address', $phpmailer))->from('foo@bar.com');
    }

    public function testHeadersWithArray()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->exactly(3))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_mail_from'), $this->identicalTo('test@test.com')],
                         [$this->identicalTo('wp_mail_from_name'), $this->identicalTo('WordPress')],
                         [$this->identicalTo('wp_mail_content_type'), $this->identicalTo('content_type')]
                     )
                     ->willReturnOnConsecutiveCalls('filtered_test@test.com', 'filtered_WordPress', 'filtered_content_type');

        $phpmailer->expects($this->once())
                  ->method('addCC')
                  ->with($this->identicalTo('foo@bar.com'), $this->identicalTo('Foo'));

        $phpmailer->expects($this->once())
                  ->method('addCustomHeader')
                  ->with($this->identicalTo('header'), $this->identicalTo('Value'));

        $phpmailer->expects($this->once())
                  ->method('isHtml')
                  ->with($this->identicalTo(false));

        (new Email($eventManager, 'from_address', $phpmailer))->headers([
            'From: test@test.com',
            'Cc: Foo <foo@bar.com>',
            'Content-Type: content_type',
            'Header: Value',
            'X-Mailer: foo',
        ]);
    }

    public function testHeadersWithContentTypeAndCharset()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->exactly(2))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_mail_charset'), $this->identicalTo('foo_charset')],
                         [$this->identicalTo('wp_mail_content_type'), $this->identicalTo('content_type')]
                     )
                     ->willReturnOnConsecutiveCalls('filtered_foo_charset', 'filtered_content_type');

        $phpmailer->expects($this->once())
                  ->method('isHtml')
                  ->with($this->identicalTo(false));

        (new Email($eventManager, 'from_address', $phpmailer))->headers([
            'Content-Type: content_type;charset=foo_charset',
        ]);
    }

    public function testHeadersWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"headers" argument must be an array or a string');

        $email = new Email($this->getEventManagerMock(), 'from_address', $this->getPHPMailerMock());

        $email->headers(new \stdClass());
    }

    public function testHeadersWithMultipartContentType()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->once())
                     ->method('filter')
                     ->with($this->identicalTo('wp_mail_content_type'), $this->identicalTo('multipart/mixed'));

        $phpmailer->expects($this->once())
                  ->method('AddCustomHeader')
                  ->with($this->identicalTo("Content-Type: multipart/mixed;\n\t boundary=\"----=_Part_4892_25692638.1192452070893\""));

        $phpmailer->expects($this->once())
                   ->method('isHtml')
                   ->with($this->identicalTo(false));

        (new Email($eventManager, 'from_address', $phpmailer))->headers([
            'Content-Type: multipart/mixed; boundary="----=_Part_4892_25692638.1192452070893"',
        ]);
    }

    public function testHeadersWithString()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->exactly(3))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_mail_from'), $this->identicalTo('test@test.com')],
                         [$this->identicalTo('wp_mail_from_name'), $this->identicalTo('WordPress')],
                         [$this->identicalTo('wp_mail_content_type'), $this->identicalTo('content_type')]
                     )
                     ->willReturnOnConsecutiveCalls('filtered_test@test.com', 'filtered_WordPress', 'filtered_content_type');

        $phpmailer->expects($this->once())
                  ->method('addCC')
                  ->with($this->identicalTo('foo@bar.com'), $this->identicalTo('Foo'));

        $phpmailer->expects($this->once())
                  ->method('addCustomHeader')
                  ->with($this->identicalTo('header'), $this->identicalTo('Value'));

        $phpmailer->expects($this->once())
                  ->method('isHtml')
                  ->with($this->identicalTo(false));

        (new Email($eventManager, 'from_address', $phpmailer))->headers("From: test@test.com\r\nCc: Foo <foo@bar.com>\r\nContent-Type: content_type\r\nHeader: Value\r\nX-Mailer: foo");
    }

    public function testReplyToWithArray()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addReplyTo')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->replyTo(['test@test.com', 'Foo <foo@bar.com>']);
    }

    public function testReplyToWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"addresses" argument must be an array or a string');

        $email = new Email($this->getEventManagerMock(), 'from_address', $this->getPHPMailerMock());

        $email->replyTo(new \stdClass());
    }

    public function testReplyToWithString()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addReplyTo')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->replyTo('test@test.com, Foo <foo@bar.com>');
    }

    public function testSubject()
    {
        $phpmailer = $this->getPHPMailerMock();

        (new Email($this->getEventManagerMock(), 'from_address', $phpmailer))->subject('Foo subject');

        $this->assertSame('Foo subject', $phpmailer->Subject);
    }

    public function testToStringSetsDefaultValues()
    {
        $eventManager = $this->getEventManagerMock();
        $phpmailer = $this->getPHPMailerMock();

        $eventManager->expects($this->once())
                     ->method('execute')
                     ->with($this->identicalTo('phpmailer_init'), $this->identicalTo($phpmailer));

        $eventManager->expects($this->exactly(4))
                     ->method('filter')
                     ->withConsecutive(
                         [$this->identicalTo('wp_mail_charset'), $this->identicalTo('UTF-8')],
                         [$this->identicalTo('wp_mail_content_type'), $this->identicalTo('text/plain')],
                         [$this->identicalTo('wp_mail_from'), $this->identicalTo('from_address')],
                         [$this->identicalTo('wp_mail_from_name'), $this->identicalTo('WordPress')]
                     );

        $phpmailer->expects($this->once())
                  ->method('preSend');

        $phpmailer->expects($this->once())
                  ->method('getSentMIMEMessage')
                  ->willReturn('email');

        $this->assertSame('email', (new Email($eventManager, 'from_address', $phpmailer))->toString());
    }

    public function testToWithArray()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addAddress')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->to(['test@test.com', 'Foo <foo@bar.com>']);
    }

    public function testToWithInvalidArgument()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"addresses" argument must be an array or a string');

        $email = new Email($this->getEventManagerMock(), 'from_address', $this->getPHPMailerMock());

        $email->to(new \stdClass());
    }

    public function testToWithString()
    {
        $phpmailer = $this->getPHPMailerMock();

        $phpmailer->expects($this->exactly(2))
                  ->method('addAddress')
                  ->withConsecutive(
                      [$this->identicalTo('test@test.com'), $this->identicalTo('')],
                      [$this->identicalTo('foo@bar.com'), $this->identicalTo('Foo')]
                  );

        $email = new Email($this->getEventManagerMock(), 'from_address', $phpmailer);

        $email->to('test@test.com, Foo <foo@bar.com>');
    }
}
