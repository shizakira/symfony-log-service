<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LogIngestInput;
use App\Service\LogIngestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('api/logs', name: 'api_logs_')]
final class LogIngestionController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[Route('/ingest', name: 'ingest', methods: ['POST'], format: 'json')]
    public function ingest(
        #[MapRequestPayload(validationFailedStatusCode: 400)] LogIngestInput $input,
        LogIngestService $logIngestService,
    ): JsonResponse {
        return $this->json(
            $logIngestService->processIngest($input),
            Response::HTTP_ACCEPTED,
        );
    }
}
