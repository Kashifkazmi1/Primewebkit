<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Requests\KnowledgeSource\AddQaKnowledgeSourceRequest;
use App\Requests\KnowledgeSource\AddTextKnowledgeSourceRequest;
use App\Requests\KnowledgeSource\AddWebsiteKnowledgeSourceRequest;
use App\Resources\KnowledgeSourceResource;
use App\Services\BotService;
use App\Services\KnowledgeSourceService;

final class KnowledgeSourceController
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'text/plain',
        'text/csv',
        'text/markdown',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_FILE_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

    public function __construct(
        private readonly KnowledgeSourceService $knowledgeSources,
        private readonly BotService $bots,
    ) {
    }

    public function index(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $sources = $this->knowledgeSources->forBot($bot->id);

        return JsonResponse::success(KnowledgeSourceResource::collection($sources), 'Knowledge sources retrieved successfully.');
    }

    public function addText(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $data = AddTextKnowledgeSourceRequest::validate($request);

        $source = $this->knowledgeSources->addTextSource($bot->id, $data['source_name'], $data['content']);

        return JsonResponse::created(KnowledgeSourceResource::make($source), 'Text knowledge source added successfully.');
    }

    public function addQa(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $data = AddQaKnowledgeSourceRequest::validate($request);

        $source = $this->knowledgeSources->addQaSource($bot->id, $data['question'], $data['answer']);

        return JsonResponse::created(KnowledgeSourceResource::make($source), 'Q&A knowledge source added successfully.');
    }

    public function addWebsite(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $data = AddWebsiteKnowledgeSourceRequest::validate($request);

        $source = $this->knowledgeSources->addWebsiteSource($bot->id, $data['start_url'], $data['max_pages'] ?? 20);

        return JsonResponse::created(
            KnowledgeSourceResource::make($source),
            'Website crawl has been queued and will be processed shortly.'
        );
    }

    public function addDocument(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        if (!$request->hasFile('file')) {
            throw new ValidationException(['file' => ['A file upload is required.']]);
        }

        $file = $request->file('file');
        $tmpPath = $file['tmp_name'];
        $originalName = $file['name'];
        $size = (int) $file['size'];

        if ($size > self::MAX_FILE_SIZE_BYTES) {
            throw new ValidationException(['file' => ['The file must not be larger than 20MB.']]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($tmpPath) ?: 'application/octet-stream';

        if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
            throw new ValidationException([
                'file' => ['Unsupported file type. Allowed: PDF, DOCX, TXT, MD, CSV.'],
            ]);
        }

        $source = $this->knowledgeSources->addDocumentSource($bot->id, [
            'tmp_path' => $tmpPath,
            'mime_type' => $detectedMime,
            'original_name' => $originalName,
        ]);

        return JsonResponse::created(KnowledgeSourceResource::make($source), 'Document uploaded and processed successfully.');
    }

    public function destroy(Request $request, string $botUuid, string $sourceUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $this->knowledgeSources->delete($sourceUuid, $bot->id);

        return JsonResponse::success(null, 'Knowledge source deleted successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
