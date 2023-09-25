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

namespace Ymir\Plugin\Tests\Unit\CloudProvider\Aws;

use Ymir\Plugin\CloudProvider\Aws\DynamoDbClient;
use Ymir\Plugin\Tests\Mock\FunctionMockTrait;
use Ymir\Plugin\Tests\Mock\HttpClientMockTrait;
use Ymir\Plugin\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Plugin\CloudProvider\Aws\DynamoDbClient
 */
class DynamoDbClientTest extends TestCase
{
    use FunctionMockTrait;
    use HttpClientMockTrait;

    public function testBatchGetItem()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://dynamodb.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/x-amz-json-1.0',
                         'host' => 'dynamodb.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'd47700a4e517dfa91f5b94491bc7e68932197d004680405e9257bf7eab8a77c8',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-target' => 'DynamoDB_20120810.BatchGetItem',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/dynamodb/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-target,Signature=cb5918b6f2922c01ce782191d23391328f82690b0bbd3dc7dfb49273bedfebf8',
                         'content-length' => 82,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"RequestItems":{"table":{"ConsistentRead":false,"Keys":[{"key":{"S":"forum"}}]}}}',
                ])
             )
             ->willReturn([
                 'body' => '{"Responses": {"Forum": [{"Name":{"S":"Amazon DynamoDB"}, "Threads":{"N":"5"}, "Messages":{"N":"19"}, "Views":{"N":"35"}}]}}',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(DynamoDbClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $response = (new DynamoDbClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->batchGetItem([
            'RequestItems' => [
                'table' => [
                    'ConsistentRead' => false,
                    'Keys' => [
                        ['key' => ['S' => 'forum']],
                    ],
                ],
            ],
        ]);

        $this->assertSame([
            'Responses' => [
                'Forum' => [
                    [
                        'Name' => ['S' => 'Amazon DynamoDB'],
                        'Threads' => ['N' => '5'],
                        'Messages' => ['N' => '19'],
                        'Views' => ['N' => '35'],
                    ],
                ],
            ],
        ], $response);
    }

    public function testDeleteItem()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://dynamodb.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/x-amz-json-1.0',
                         'host' => 'dynamodb.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => 'f75f88323bf44389f3a6dd83fb622ccf77df4831a8c4ede662d7f8b632235966',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-target' => 'DynamoDB_20120810.DeleteItem',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/dynamodb/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-target,Signature=e56889be45e56745b0c45c2193cce1b05c16303777db9a62f29e1da111324e30',
                         'content-length' => 47,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"TableName":"table","Key":{"key":{"S":"key"}}}',
                 ])
             )
             ->willReturn([
                 'body' => '{"Responses": {"Forum": [{"Name":{"S":"Amazon DynamoDB"}, "Threads":{"N":"5"}, "Messages":{"N":"19"}, "Views":{"N":"35"}}]}}',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(DynamoDbClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new DynamoDbClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->deleteItem([
            'TableName' => 'table',
            'Key' => [
                'key' => ['S' => 'key'],
            ],
        ]);
    }

    public function testGetItem()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://dynamodb.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/x-amz-json-1.0',
                         'host' => 'dynamodb.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '8202bb4a7f3ba37635ecba019388cf544cd0058474725d40f1d51198ab7f2e2a',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-target' => 'DynamoDB_20120810.GetItem',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/dynamodb/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-target,Signature=0f331677fc0ede47d85e8a1f2bf69582f0d67860adb769bcdc5ae228ac500833',
                         'content-length' => 65,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"table":{"ConsistentRead":false,"Keys":[{"key":{"S":"forum"}}]}}',
                 ])
             )
             ->willReturn([
                 'body' => '{"Item": { "Tags": { "SS": ["Update","Multiple Items","HelpMe"]}, "LastPostDateTime": {"S": "201303190436"}, "Message": {"S": "I want to update multiple items in a single call. What\'s the best way to do that?"}}}',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(DynamoDbClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
                ->withConsecutive(
                    [$this->identicalTo('Ymd\THis\Z')],
                    [$this->identicalTo('Ymd')],
                    [$this->identicalTo('Ymd\THis\Z')],
                    [$this->identicalTo('Ymd')],
                    [$this->identicalTo('Ymd')]
                )
                ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        $response = (new DynamoDbClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->getItem([
            'table' => [
                'ConsistentRead' => false,
                'Keys' => [
                    ['key' => ['S' => 'forum']],
                ],
            ],
        ]);

        $this->assertSame([
            'Item' => [
                'Tags' => ['SS' => ['Update', 'Multiple Items', 'HelpMe']],
                'LastPostDateTime' => ['S' => '201303190436'],
                'Message' => ['S' => 'I want to update multiple items in a single call. What\'s the best way to do that?'],
            ],
        ], $response);
    }

    public function testPutItem()
    {
        $http = $this->getHttpClientMock();
        $http->expects($this->once())
             ->method('request')
             ->with(
                 $this->identicalTo('https://dynamodb.us-east-1.amazonaws.com/'),
                 $this->identicalTo([
                     'headers' => [
                         'content-type' => 'application/x-amz-json-1.0',
                         'host' => 'dynamodb.us-east-1.amazonaws.com',
                         'x-amz-content-sha256' => '1cc5dbaad2b0b43e5bc819fddaa6761f95f62ab142866d33a87b58fe2733267b',
                         'x-amz-date' => '20200515T181004Z',
                         'x-amz-target' => 'DynamoDB_20120810.PutItem',
                         'authorization' => 'AWS4-HMAC-SHA256 Credential=aws-key/20200515/us-east-1/dynamodb/aws4_request,SignedHeaders=content-type;host;x-amz-content-sha256;x-amz-date;x-amz-target,Signature=4dd86e117c2735b9ff9c6e3287123180bedf03a489204f86d6bda84586a5a98c',
                         'content-length' => 69,
                     ],
                     'method' => 'POST',
                     'timeout' => 300,
                     'body' => '{"TableName":"table","Key":{"key":{"S":"key"},"value":{"S":"value"}}}',
                 ])
             )
             ->willReturn([
                 'body' => '{"Responses": {"Forum": [{"Name":{"S":"Amazon DynamoDB"}, "Threads":{"N":"5"}, "Messages":{"N":"19"}, "Views":{"N":"35"}}]}}',
                 'response' => ['code' => 200],
             ]);

        $gmdate = $this->getFunctionMock($this->getNamespace(DynamoDbClient::class), 'gmdate');
        $gmdate->expects($this->exactly(5))
               ->withConsecutive(
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd\THis\Z')],
                   [$this->identicalTo('Ymd')],
                   [$this->identicalTo('Ymd')]
               )
               ->willReturnOnConsecutiveCalls('20200515T181004Z', '20200515', '20200515T181004Z', '20200515', '20200515');

        (new DynamoDbClient($http, 'aws-key', 'us-east-1', 'aws-secret'))->putItem([
            'TableName' => 'table',
            'Key' => [
                'key' => ['S' => 'key'],
                'value' => ['S' => 'value'],
            ],
        ]);
    }
}
