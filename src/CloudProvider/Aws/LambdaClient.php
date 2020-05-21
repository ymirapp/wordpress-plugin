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

use Ymir\Plugin\Console;
use Ymir\Plugin\Console\ConsoleClientInterface;

/**
 * The client for AWS Lambda API.
 */
class LambdaClient extends AbstractClient implements ConsoleClientInterface
{
    /**
     * The name of the lambda function that the client calls.
     *
     * @var string
     */
    private $functionName;

    /**
     * {@inheritdoc}
     */
    public function __construct(\WP_Http $transport, string $functionName, string $key, string $secret, string $region = 'us-east-1')
    {
        parent::__construct($transport, $key, $secret, $region);

        $this->functionName = $functionName;
    }

    /**
     * {@inheritdoc}
     */
    public function createAttachmentMetadata($attachment, bool $async = false)
    {
        $this->runWpCliCommand(sprintf('%s %s', Console\CreateAttachmentMetadataCommand::getName(), $this->getAttachmentId($attachment)), $async);
    }

    /**
     * {@inheritdoc}
     */
    public function createCroppedAttachmentImage($attachment, int $width, int $height, int $x, int $y, string $context = '', int $imageWidth = 0, int $imageHeight = 0): int
    {
        $options = [
            'width' => $width,
            'height' => $height,
            'x' => $x,
            'y' => $y,
        ];

        if (!empty($context) && 'site-icon' !== $context) {
            $options['context'] = $context;
        }
        if (!empty($imageHeight)) {
            $options['image_height'] = $imageHeight;
        }
        if (!empty($imageWidth)) {
            $options['image_width'] = $imageWidth;
        }

        $matches = [];
        $message = $this->runWpCliCommand(sprintf('%s %s %s', 'site-icon' !== $context ? Console\CreateCroppedImageCommand::getName() : Console\CreateSiteIconCommand::getName(), $this->getAttachmentId($attachment), $this->buildOptions($options)));
        preg_match('/with ID ([0-9]+)/', $message, $matches);

        if (empty($matches[1])) {
            throw new \RuntimeException('Unable to parse the ID of the cropped attachment image');
        }

        return (int) $matches[1];
    }

    /**
     * {@inheritdoc}
     */
    public function editAttachmentImage($attachment, string $changes, string $apply = 'all')
    {
        $this->runWpCliCommand(sprintf('%s %s \'%s\' %s', Console\EditAttachmentImageCommand::getName(), $this->getAttachmentId($attachment), $changes, $this->buildOptions([
            'apply' => $apply,
        ])));
    }

    /**
     * {@inheritdoc}
     */
    public function resizeAttachmentImage($attachment, int $width, int $height)
    {
        $this->runWpCliCommand(sprintf('%s %s %s', Console\ResizeAttachmentImageCommand::getName(), $this->getAttachmentId($attachment), $this->buildOptions([
            'width' => $width,
            'height' => $height,
        ])));
    }

    /**
     * {@inheritdoc}
     */
    protected function getService(): string
    {
        return 'lambda';
    }

    /**
     * Build the options string from the given array of command options.
     */
    private function buildOptions(array $options): string
    {
        $optionsString = '';

        foreach ($options as $option => $value) {
            $optionsString .= sprintf(' --%s=%s', $option, $value);
        }

        return trim($optionsString);
    }

    /**
     * Get the attachment ID from the given attachment.
     */
    private function getAttachmentId($attachment): int
    {
        if ($attachment instanceof \WP_Post) {
            $attachment = $attachment->ID;
        } elseif (!is_numeric($attachment)) {
            throw new \InvalidArgumentException('The given "attachment" needs to be either numeric or a "WP_Post" object');
        }

        return (int) $attachment;
    }

    /**
     * Invoke the lambda function with the given payload.
     */
    private function invoke(array $payload, bool $async = false): array
    {
        return $this->request('post', "/2015-03-31/functions/{$this->functionName}/invocations?Qualifier=deployed", json_encode($payload), [
            'content-type' => 'application/json',
            'x-amz-invocation-type' => $async ? 'Event' : 'RequestResponse',
        ]);
    }

    /**
     * Run the given WP-CLI command.
     */
    private function runWpCliCommand(string $command, bool $async = false): string
    {
        $response = $this->invoke([
            'php' => "bin/wp $command",
        ], $async);

        if (!isset($response['body'])) {
            throw new \RuntimeException('Lambda did not return a response body');
        }

        $response = json_decode($response['body']);

        if (!$response instanceof \stdClass) {
            throw new \RuntimeException('Unable to parse the Lambda response body');
        }

        if (!empty($response->output) && 0 === strpos($response->output, 'Success:')) {
            return str_replace('Success: ', '', $response->output);
        }

        $matches = [];
        $message = sprintf('Lambda was unable to run the "%s" WP-CLI command', $command);

        if (!empty($response->errorMessage)) {
            preg_match('/^Error: (.*)$/m', $response->errorMessage, $matches);
        }
        if (!empty($matches[1])) {
            $message = $matches[1];
        }

        throw new \RuntimeException($message);
    }
}
