<?php

namespace Protean\DeVault\Tests;

use Protean\DeVault\Client as DeVaultClient;
use Protean\DeVault\Config;
use Protean\DeVault\Exceptions;
use Protean\DeVault\Responses\DeVaultdResponse;
use Protean\DeVault\Responses\Response;
use GuzzleHttp\Client as GuzzleHttp;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class ClientTest extends TestCase
{
    /**
     * Set-up test environment.
     *
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        $this->devaultd = new DeVaultClient();
    }

    /**
     * Test client getter and setter.
     *
     * @return void
     */
    public function testClientSetterGetter() : void
    {
        $devaultd = new DeVaultClient('http://old_client.org');
        $this->assertInstanceOf(DeVaultClient::class, $devaultd);

        $base_uri = $devaultd->getClient()->getConfig('base_uri');
        $this->assertEquals($base_uri->getHost(), 'old_client.org');

        $oldClient = $devaultd->getClient();
        $this->assertInstanceOf(GuzzleHttp::class, $oldClient);

        $newClient = new GuzzleHttp(['base_uri' => 'http://new_client.org']);
        $devaultd->setClient($newClient);

        $base_uri = $devaultd->getClient()->getConfig('base_uri');
        $this->assertEquals($base_uri->getHost(), 'new_client.org');
    }

    /**
     * Test preserve method name case config option.
     *
     * @return void
     */
    public function testPreserveCaseOption() : void
    {
        $devaultd = new DeVaultClient(['preserve_case' => true]);
        $devaultd->setClient($this->mockGuzzle([$this->getBlockResponse()]));
        $devaultd->getBlockHeader();

        $request = $this->getHistoryRequestBody();

        $this->assertEquals($this->makeRequestBody(
            'getBlockHeader',
            $request['id']
        ), $request);
    }

    /**
     * Test client config getter.
     *
     * @return void
     */
    public function testGetConfig() : void
    {
        $this->assertInstanceOf(Config::class, $this->devaultd->getConfig());
    }

    /**
     * Test simple request.
     *
     * @return void
     */
    public function testRequest() : void
    {
        $response = $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBlockResponse()]))
            ->request(
                'getblockheader',
                '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
            );

        $request = $this->getHistoryRequestBody();

        $this->assertEquals($this->makeRequestBody(
            'getblockheader',
            $request['id'],
            '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
        ), $request);
        $this->assertEquals(self::$getBlockResponse, $response->get());
    }

    /**
     * Test multiwallet request.
     *
     * @return void
     */
    public function testMultiWalletRequest() : void
    {
        $wallet = 'testwallet.dat';

        $response = $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBalanceResponse()]))
            ->wallet($wallet)
            ->request('getbalance');

        $this->assertEquals(self::$balanceResponse, $response->get());
        $this->assertEquals(
            $this->getHistoryRequestUri()->getPath(),
            "/wallet/$wallet"
        );
    }

    /**
     * Test async multiwallet request.
     *
     * @return void
     */
    public function testMultiWalletAsyncRequest() : void
    {
        $wallet = 'testwallet2.dat';

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBalanceResponse()]))
            ->wallet($wallet)
            ->requestAsync('getbalance', []);

        $this->devaultd->wait();

        $this->assertEquals(
            $this->getHistoryRequestUri()->getPath(),
            "/wallet/$wallet"
        );
    }

    /**
     * Test async request.
     *
     * @return void
     */
    public function testAsyncRequest() : void
    {
        $onFulfilled = $this->mockCallable([
            $this->callback(function (DeVaultdResponse $response) {
                return $response->get() == self::$getBlockResponse;
            }),
        ]);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBlockResponse()]))
            ->requestAsync(
                'getblockheader',
                '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f',
                function ($response) use ($onFulfilled) {
                    $onFulfilled($response);
                }
            );

        $this->devaultd->wait();

        $request = $this->getHistoryRequestBody();
        $this->assertEquals($this->makeRequestBody(
            'getblockheader',
            $request['id'],
            '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
        ), $request);
    }

    /**
     * Test magic request.
     *
     * @return void
     */
    public function testMagic() : void
    {
        $response = $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBlockResponse()]))
            ->getBlockHeader(
                '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
            );

        $request = $this->getHistoryRequestBody();
        $this->assertEquals($this->makeRequestBody(
            'getblockheader',
            $request['id'],
            '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
        ), $request);
    }

    /**
     * Test magic request.
     *
     * @return void
     */
    public function testAsyncMagic() : void
    {
        $onFulfilled = $this->mockCallable([
            $this->callback(function (DeVaultdResponse $response) {
                return $response->get() == self::$getBlockResponse;
            }),
        ]);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->getBlockResponse()]))
            ->getBlockHeaderAsync(
                '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f',
                function ($response) use ($onFulfilled) {
                    $onFulfilled($response);
                }
            );

        $this->devaultd->wait();

        $request = $this->getHistoryRequestBody();
        $this->assertEquals($this->makeRequestBody(
            'getblockheader',
            $request['id'],
            '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
        ), $request);
    }

    /**
     * Test devaultd exception.
     *
     * @return void
     */
    public function testDeVaultdException() : void
    {
        $this->expectException(Exceptions\BadRemoteCallException::class);
        $this->expectExceptionMessage(self::$rawTransactionError['message']);
        $this->expectExceptionCode(self::$rawTransactionError['code']);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->rawTransactionError(200)]))
            ->getRawTransaction(
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
            );
    }

    /**
     * Test request exception with error code.
     *
     * @return void
     */
    public function testRequestExceptionWithServerErrorCode() : void
    {
        $this->expectException(Exceptions\BadRemoteCallException::class);
        $this->expectExceptionMessage(self::$rawTransactionError['message']);
        $this->expectExceptionCode(self::$rawTransactionError['code']);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->rawTransactionError(200)]))
            ->getRawTransaction(
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
            );
    }

    /**
     * Test request exception with empty response body.
     *
     * @return void
     */
    public function testRequestExceptionWithEmptyResponseBody() : void
    {
        $this->expectException(Exceptions\ConnectionException::class);
        $this->expectExceptionMessage($this->error500());
        $this->expectExceptionCode(500);

        $this->devaultd
            ->setClient($this->mockGuzzle([new GuzzleResponse(500)]))
            ->getRawTransaction(
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
            );
    }

    /**
     * Test async request exception with empty response body.
     *
     * @return void
     */
    public function testAsyncRequestExceptionWithEmptyResponseBody() : void
    {
        $rejected = $this->mockCallable([
            $this->callback(function (Exceptions\ClientException $exception) {
                return $exception->getMessage() == $this->error500() &&
                    $exception->getCode() == 500;
            }),
        ]);

        $this->devaultd
            ->setClient($this->mockGuzzle([new GuzzleResponse(500)]))
            ->requestAsync(
                'getrawtransaction',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                null,
                function ($exception) use ($rejected) {
                    $rejected($exception);
                }
            );

        $this->devaultd->wait();
    }

    /**
     * Test request exception with response.
     *
     * @return void
     */
    public function testRequestExceptionWithResponseBody() : void
    {
        $this->expectException(Exceptions\BadRemoteCallException::class);
        $this->expectExceptionMessage(self::$rawTransactionError['message']);
        $this->expectExceptionCode(self::$rawTransactionError['code']);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->requestExceptionWithResponse()]))
            ->getRawTransaction(
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
            );
    }

    /**
     * Test async request exception with response.
     *
     * @return void
     */
    public function testAsyncRequestExceptionWithResponseBody() : void
    {
        $onRejected = $this->mockCallable([
            $this->callback(function (Exceptions\BadRemoteCallException $exception) {
                return $exception->getMessage() == self::$rawTransactionError['message'] &&
                    $exception->getCode() == self::$rawTransactionError['code'];
            }),
        ]);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->requestExceptionWithResponse()]))
            ->requestAsync(
                'getrawtransaction',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                null,
                function ($exception) use ($onRejected) {
                    $onRejected($exception);
                }
            );

        $this->devaultd->wait();
    }

    /**
     * Test request exception with no response.
     *
     * @return void
     */
    public function testRequestExceptionWithNoResponseBody() : void
    {
        $this->expectException(Exceptions\ClientException::class);
        $this->expectExceptionMessage('test');
        $this->expectExceptionCode(0);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->requestExceptionWithoutResponse()]))
            ->getRawTransaction(
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
            );
    }

    /**
     * Test async request exception with no response.
     *
     * @return void
     */
    public function testAsyncRequestExceptionWithNoResponseBody() : void
    {
        $rejected = $this->mockCallable([
            $this->callback(function (Exceptions\ClientException $exception) {
                return $exception->getMessage() == 'test' &&
                    $exception->getCode() == 0;
            }),
        ]);

        $this->devaultd
            ->setClient($this->mockGuzzle([$this->requestExceptionWithoutResponse()]))
            ->requestAsync(
                'getrawtransaction',
                '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',
                null,
                function ($exception) use ($rejected) {
                    $rejected($exception);
                }
            );

        $this->devaultd->wait();
    }

    /**
     * Test setting different response handler class.
     *
     * @return void
     */
    public function testSetResponseHandler() : void
    {
        $fake = new FakeClient();

        $guzzle = $this->mockGuzzle([
            $this->getBlockResponse(),
        ], $fake->getClient()->getConfig('handler'));

        $response = $fake
            ->setClient($guzzle)
            ->request(
                'getblockheader',
                '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f'
            );

        $this->assertInstanceOf(FakeResponse::class, $response);
    }
}

class FakeClient extends DeVaultClient
{
    /**
     * Gets response handler class name.
     *
     * @return string
     */
    protected function getResponseHandler() : string
    {
        return 'Protean\\DeVault\\Tests\\FakeResponse';
    }
}

class FakeResponse extends Response
{
    //
}
