<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\LogService;

class LogController
{
    #[Route('/check', methods: ['GET'])]
    public function index(Request $request, LogService $logsService): JsonResponse
    {
        $params = $request->query->all();
        $count = $logsService->getLogs($params);

        return new JsonResponse(['counter' => $count]);
    }
}
