<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Mailing\Adapter;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\EshopCommunity\Internal\Transition\Adapter\Email\SymfonyMailerAdapter;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email as SymfonyEmail;

class SymfonyMailerAdapterTest extends TestCase
{
    public function testConvertBasicEmail(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'subject' => 'Test Subject',
            'body' => '<p>Test Body</p>',
            'altBody' => 'Test Body',
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $this->assertEquals('Test Subject', $symfonyEmail->getSubject());
        $this->assertEquals('<p>Test Body</p>', $symfonyEmail->getHtmlBody());
        $this->assertEquals('Test Body', $symfonyEmail->getTextBody());
    }

    public function testConvertWithMultipleRecipients(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'recipients' => [
                ['test@example.com', 'Test User'],
                ['second@example.com', 'Second User'],
            ],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $to = $symfonyEmail->getTo();
        $this->assertCount(2, $to);
        $this->assertEquals('test@example.com', $to[0]->getAddress());
        $this->assertEquals('second@example.com', $to[1]->getAddress());
    }

    public function testConvertWithFromName(): void
    {
        $legacyEmail = $this->createBasicEmail();

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $from = $symfonyEmail->getFrom();
        $this->assertCount(1, $from);
        $this->assertEquals('sender@example.com', $from[0]->getAddress());
        $this->assertEquals('Sender Name', $from[0]->getName());
    }

    public function testConvertWithReplyTo(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'replyTo' => [['reply@example.com', 'Reply User']],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $replyTo = $symfonyEmail->getReplyTo();
        $this->assertCount(1, $replyTo);
        $this->assertEquals('reply@example.com', $replyTo[0]->getAddress());
        $this->assertEquals('Reply User', $replyTo[0]->getName());
    }

    public function testConvertWithCcAndBcc(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'cc' => [['cc@example.com', 'CC User']],
            'bcc' => [['bcc@example.com', 'BCC User']],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $cc = $symfonyEmail->getCc();
        $bcc = $symfonyEmail->getBcc();

        $this->assertCount(1, $cc);
        $this->assertEquals('cc@example.com', $cc[0]->getAddress());
        $this->assertEquals('CC User', $cc[0]->getName());

        $this->assertCount(1, $bcc);
        $this->assertEquals('bcc@example.com', $bcc[0]->getAddress());
        $this->assertEquals('BCC User', $bcc[0]->getName());
    }

    public function testConvertPlainTextEmail(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'contentType' => PHPMailer::CONTENT_TYPE_PLAINTEXT,
            'body' => 'Plain text body',
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $this->assertNull($symfonyEmail->getHtmlBody());
        $this->assertEquals('Plain text body', $symfonyEmail->getTextBody());
    }

    public function testConvertHtmlEmailWithAltBody(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'body' => '<p>HTML body</p>',
            'altBody' => 'Plain text alternative',
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $this->assertEquals('<p>HTML body</p>', $symfonyEmail->getHtmlBody());
        $this->assertEquals('Plain text alternative', $symfonyEmail->getTextBody());
    }

    public function testConvertWithStringAttachment(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'attachments' => [
                ['string content', 'file.txt', 'file.txt', 'base64', 'text/plain', true, 'attachment', null],
            ],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $attachments = $symfonyEmail->getAttachments();
        $this->assertCount(1, $attachments);
    }

    public function testConvertWithCustomHeaders(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'customHeaders' => [['X-Custom-Header', 'CustomValue']],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $headers = $symfonyEmail->getHeaders();

        $this->assertTrue($headers->has('X-Custom-Header'));
        $this->assertEquals('CustomValue', $headers->get('X-Custom-Header')->getBodyAsString());
    }

    public function testConvertWithPriority(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'priority' => 1,
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $this->assertEquals(SymfonyEmail::PRIORITY_HIGHEST, $symfonyEmail->getPriority());
    }

    public function testConvertWithLowPriority(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'priority' => 5,
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $this->assertEquals(SymfonyEmail::PRIORITY_LOWEST, $symfonyEmail->getPriority());
    }

    public function testConvertWithSender(): void
    {
        $legacyEmail = $this->createBasicEmail([
            'sender' => 'bounce@example.com',
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $returnPath = $symfonyEmail->getReturnPath();
        $this->assertNotNull($returnPath);
        $this->assertEquals('bounce@example.com', $returnPath->getAddress());
    }

    public function testCidMappingNormalizesCidWithoutAtSymbol(): void
    {
        $cid = 'abc123def456';
        $body = '<p>Image: <img src="cid:' . $cid . '"></p>';

        $legacyEmail = $this->createBasicEmail([
            'subject' => 'Test Subject',
            'body' => $body,
            'attachments' => [
                ['image data', 'image.png', 'image.png', 'base64', 'image/png', true, 'inline', $cid],
            ],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $attachments = $symfonyEmail->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals($cid . '@generated', $attachments[0]->getContentId());
        $this->assertStringContainsString('cid:' . $cid . '@generated', $symfonyEmail->getHtmlBody());
    }

    public function testCidMappingPreservesCidWithAtSymbol(): void
    {
        $cid = 'abc123@example.com';
        $body = '<p>Image: <img src="cid:' . $cid . '"></p>';

        $legacyEmail = $this->createBasicEmail([
            'subject' => 'Test Subject',
            'body' => $body,
            'attachments' => [
                ['image data', 'image.png', 'image.png', 'base64', 'image/png', true, 'inline', $cid],
            ],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $attachments = $symfonyEmail->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals($cid, $attachments[0]->getContentId());
        $this->assertStringContainsString('cid:' . $cid, $symfonyEmail->getHtmlBody());
    }

    public function testCidMappingWithMultipleInlineImages(): void
    {
        $cid1 = 'image1abc';
        $cid2 = 'image2@domain.com';
        $body = '<p><img src="cid:' . $cid1 . '"><img src="cid:' . $cid2 . '"></p>';

        $legacyEmail = $this->createBasicEmail([
            'body' => $body,
            'attachments' => [
                ['image1 data', 'image1.png', 'image1.png', 'base64', 'image/png', true, 'inline', $cid1],
                ['image2 data', 'image2.png', 'image2.png', 'base64', 'image/png', true, 'inline', $cid2],
            ],
        ]);

        $adapter = new SymfonyMailerAdapter();
        $symfonyEmail = $adapter->convertToSymfonyEmail($legacyEmail);

        $htmlBody = $symfonyEmail->getHtmlBody();

        $this->assertStringContainsString('cid:' . $cid1 . '@generated', $htmlBody);
        $this->assertStringContainsString('cid:' . $cid2, $htmlBody);
        $this->assertStringNotContainsString('cid:' . $cid2 . '@generated', $htmlBody);
    }

    /**
     * @param array{
     *     recipients?: list<array{0: string, 1?: string}>,
     *     from?: string,
     *     fromName?: string,
     *     subject?: string,
     *     body?: string,
     *     altBody?: string,
     *     charset?: string,
     *     contentType?: string,
     *     replyTo?: list<array{0: string, 1?: string}>,
     *     cc?: list<array{0: string, 1?: string}>,
     *     bcc?: list<array{0: string, 1?: string}>,
     *     customHeaders?: list<array{0: string, 1: string}>,
     *     attachments?: list<array<int, mixed>>,
     *     priority?: int,
     *     sender?: string
     * } $config
     */
    private function createBasicEmail(array $config = []): Email
    {
        $config = array_merge([
            'recipients' => [['test@example.com', 'Test User']],
            'from' => 'sender@example.com',
            'fromName' => 'Sender Name',
            'subject' => 'Test',
            'body' => 'Body',
            'altBody' => '',
            'charset' => 'UTF-8',
            'contentType' => PHPMailer::CONTENT_TYPE_TEXT_HTML,
            'replyTo' => [],
            'cc' => [],
            'bcc' => [],
            'customHeaders' => [],
            'attachments' => [],
            'priority' => null,
            'sender' => null,
        ], $config);

        $legacyEmail = $this->createStub(Email::class);
        $legacyEmail->method('getRecipient')->willReturn($config['recipients']);
        $legacyEmail->method('getFrom')->willReturn($config['from']);
        $legacyEmail->method('getFromName')->willReturn($config['fromName']);
        $legacyEmail->method('getSubject')->willReturn($config['subject']);
        $legacyEmail->method('getBody')->willReturn($config['body']);
        $legacyEmail->method('getAltBody')->willReturn($config['altBody']);
        $legacyEmail->method('getCharset')->willReturn($config['charset']);
        $legacyEmail->method('getReplyTo')->willReturn($config['replyTo']);
        $legacyEmail->method('getCc')->willReturn($config['cc']);
        $legacyEmail->method('getBcc')->willReturn($config['bcc']);
        $legacyEmail->method('getCustomHeaders')->willReturn($config['customHeaders']);
        $legacyEmail->method('getAttachments')->willReturn($config['attachments']);
        $legacyEmail->ContentType = $config['contentType'];

        if ($config['priority'] !== null) {
            $legacyEmail->Priority = $config['priority'];
        }

        if ($config['sender'] !== null) {
            $legacyEmail->Sender = $config['sender'];
        }

        return $legacyEmail;
    }
}
