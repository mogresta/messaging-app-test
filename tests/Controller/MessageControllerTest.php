<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\MessageController;
use App\Enum\MessageStatus;
use App\Message\MessageDispatchService;
use App\Repository\MessageRepository;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class MessageControllerTest extends TestCase
{
    use InteractsWithMessenger;

    private MessageController $controller;
    private MockObject&MessageRepository $repository;
    private MockObject&MessageDispatchService $dispatchService;
    private MockObject&LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MessageRepository::class);
        $this->dispatchService = $this->createMock(MessageDispatchService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new MessageController(
            $this->logger,
            $this->dispatchService,
            $this->repository
        );

        /**
         * Mocking the container to satisfy the AbstractController dependencies.
         * Necessary for test coverage of logging and exception catching
         */
        /** @var ContainerInterface $container*/
        $container = $this->createMock(ContainerInterface::class);

        $this->controller->setContainer($container);
    }

    public function testListMessagesWithoutParameters(): void
    {
        $expectedMessages = [
            'items' => [],
            'totalItems' => 0,
            'page' => 1,
            'limit' => 10,
            'totalPages' => 0
        ];

        $this->repository
            ->expects(self::once())
            ->method('getPaginatedMessages')
            ->with(null, 1, 10)
            ->willReturn($expectedMessages);

        $request = new Request();
        $response = $this->controller->list($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedMessages, json_decode((string) $response->getContent(), true));
    }


    /** @dataProvider listMessagesWithValidParametersDataProvider
     * @param array{ status: string, page: int, limit: int } $parameters
     */
    public function testListMessagesWithValidParameters(array $parameters): void
    {
        $limit = $parameters['limit'] <= 50 ? $parameters['limit'] : 50;
        $statusEnum = MessageStatus::from($parameters['status']);

        $expectedMessages = [
            'items' => [],
            'totalItems' => 0,
            'page' => $parameters['page'],
            'limit' => $limit,
            'totalPages' => 0
        ];

        $this->repository
            ->expects(self::once())
            ->method('getPaginatedMessages')
            ->with($statusEnum, $parameters['page'], $limit)
            ->willReturn($expectedMessages);

        $request = new Request($parameters);
        $response = $this->controller->list($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedMessages, json_decode((string) $response->getContent(), true));
    }

    /** @return array{array{array{ status: string, page: int, limit: int }}} */
    public function listMessagesWithValidParametersDataProvider(): array
    {
        return [
            [['status' => 'draft', 'page' => 1, 'limit' => 10]],
            [['status' => 'sent', 'page' => 1, 'limit' => 20]],
            [['status' => 'read', 'page' => 1, 'limit' => 60]],
        ];
    }


    public function testListMessagesWithInvalidStatus(): void
    {
        $parameters = ['status' => 'wrong status', 'page' => 1, 'limit' => 10];

        $request = new Request($parameters);
        $response = $this->controller->list($request);

        $this->repository
            ->expects(self::never())
            ->method('getPaginatedMessages');

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testRepositoryThrowsException(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getPaginatedMessages')
            ->willThrowException(new Exception());

        $request = new Request();
        $response = $this->controller->list($request);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testSendMessage(): void
    {
        $this->dispatchService->expects(self::once())
            ->method('handleMessage')
            ->with('Test message');

        $expectedResponse = ['message' => 'Message sent successfully'];

        $content = ['text' => 'Test message'];
        $request = new Request([], [], [], [], [], [], (string) json_encode($content));

        $response = $this->controller->send($request);

        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals($expectedResponse, json_decode((string) $response->getContent(), true));
    }

    public function testMessageHasNoContent(): void
    {
        $content = ['text' => ''];
        $request = new Request([], [], [], [], [], [], (string) json_encode($content));

        $response = $this->controller->send($request);

        $expectedResponse = ['error' => 'Message content cannot be empty.'];

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expectedResponse, json_decode((string) $response->getContent(), true));
    }

    public function testSendMessageDispatcherThrowsException(): void
    {
        $exception = new Exception('Error message');

        $this->dispatchService->expects(self::once())
            ->method('handleMessage')
            ->with('text')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to queue message', [
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

        $expectedResponse = ['error' => 'Failed to process message'];

        $content = ['text' => 'text'];
        $request = new Request([], [], [], [], [], [], (string) json_encode($content));

        $response = $this->controller->send($request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals($expectedResponse, json_decode((string) $response->getContent(), true));
    }
}