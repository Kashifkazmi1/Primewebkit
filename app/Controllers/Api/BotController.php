<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Bot\CreateBotRequest;
use App\Requests\Bot\UpdateBotRequest;
use App\Resources\BotResource;
use App\Services\AI\EmbeddingService;
use App\Services\BotService;

final class BotController
{
    public function __construct(
        private readonly BotService $bots,
        private readonly EmbeddingService $embeddings,
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->currentUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $result = $this->bots->paginateForUser($user->id, $page, $perPage);

        return JsonResponse::success(BotResource::collection($result['data']), 'Bots retrieved successfully.', 200, [
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->currentUser($request);
        $data = CreateBotRequest::validate($request);

        $bot = $this->bots->create($user->id, $data);

        return JsonResponse::created(BotResource::make($bot), 'Bot created successfully.');
    }

    public function show(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $bot = $this->bots->getForUser($uuid, $user->id);

        return JsonResponse::success(BotResource::make($bot), 'Bot retrieved successfully.');
    }

    public function update(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $data = UpdateBotRequest::validate($request);

        $bot = $this->bots->update($uuid, $user->id, $data);

        return JsonResponse::success(BotResource::make($bot), 'Bot updated successfully.');
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $this->bots->delete($uuid, $user->id);

        return JsonResponse::success(null, 'Bot deleted successfully.');
    }

    /**
     * Re-generates embeddings for every knowledge-base chunk this bot
     * has. Needed if the embedding model/provider changes — existing
     * vectors are not comparable to queries embedded with a different
     * model.
     */
    public function reembed(Request $request, string $uuid): Response
    {
        $user = $this->currentUser($request);
        $bot = $this->bots->getForUser($uuid, $user->id);

        $providerName = (string) config('ai.default_provider', 'gemini');
        $model = (string) config('gemini.embedding_model');

        $result = $this->embeddings->reembedForBot($bot->id, $providerName, $model);

        return JsonResponse::success(
            $result,
            "Re-embedded {$result['embedded']} chunk(s)" . ($result['failed'] > 0 ? ", {$result['failed']} failed." : '.')
        );
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
