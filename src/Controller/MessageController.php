<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\MessageStatus;
use App\Message\MessageDispatchService;
use App\Repository\MessageRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see MessageControllerTest
 * TODO: review both methods and also the `openapi.yaml` specification
 *       Add Comments for your Code-Review, so that the developer can understand why changes are needed.
 */
class MessageController extends AbstractController
{
    /**
     * Minimal modifications to the controller, explanations below
     * Assumed full auth wasn't required for the task
     * More extensive changes possible - rate limiting, request & response DTOs,
     * response transformers & normalizers, validators
     */

    /** logger added */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageDispatchService $messageDispatchService,
        private readonly MessageRepository $messagesRepository
    )
    {}

    #[Route('/messages', methods: ['GET'])]
    public function list(Request $request): Response {
        /** try catch block */
        try {
            /** Validation and sanitization of query parameters, pagination */
            $page = max(1, $request->query->getInt('page', 1));
            $limit = min(50, max(1, $request->query->getInt('limit', 10)));
            $status = (string) $request->query->get('status');

            /** Validate status if provided */
            if ($status && !MessageStatus::tryFrom($status)) {
                return $this->json(
                    ['error' => 'Invalid status value'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $messages = $this->messagesRepository->getPaginatedMessages(
                status: $status ? MessageStatus::from($status) : null,
                page: $page,
                limit: $limit
            );

            return $this->json($messages, Response::HTTP_OK);
        } catch (Exception $error) {
            /** Log the error */
            $this->logger->error('Failed to fetch messages', [
                'exception' => $error->getMessage(),
                'trace' => $error->getTraceAsString()
            ]);

            return $this->json(
                ['error' => 'Failed to fetch messages'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /** method changed to post, removed everything from the controller except handling of incoming and outgoing data */
    #[Route('/messages/send', methods: ['POST'])]
    public function send(Request $request): Response {
        try {
            $content = $request->toArray();

            /** Basic validation of content */
            if (!isset($content['text']) || strlen(trim($content['text'])) === 0) {
                return $this->json(
                    ['error' => 'Message content cannot be empty.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->messageDispatchService->handleMessage($content['text']);

            return $this->json(
                ['message' => 'Message sent successfully'],
                Response::HTTP_ACCEPTED,
            );
        } catch (Exception $error) {
            $this->logger->error('Failed to queue message', [
                'exception' => $error->getMessage(),
                'trace' => $error->getTraceAsString()
            ]);

            return $this->json(
                ['error' => 'Failed to process message'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}