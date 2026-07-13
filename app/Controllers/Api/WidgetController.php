<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Models\User;
use App\Requests\Widget\UpdateWidgetRequest;
use App\Resources\WidgetResource;
use App\Services\BotService;
use App\Services\WidgetService;

final class WidgetController
{
    public function __construct(
        private readonly WidgetService $widgets,
        private readonly BotService $bots,
    ) {
    }

    public function show(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $widget = $this->widgets->getForBot($bot->id);

        return JsonResponse::success(WidgetResource::make($widget), 'Widget configuration retrieved successfully.');
    }

    public function update(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);
        $data = UpdateWidgetRequest::validate($request);

        $widget = $this->widgets->update($bot->id, $data);

        return JsonResponse::success(WidgetResource::make($widget), 'Widget configuration updated successfully.');
    }

    public function embedScript(Request $request, string $botUuid): Response
    {
        $bot = $this->bots->getForUser($botUuid, $this->currentUser($request)->id);

        // The embeddable loader lives at the web root (public_html/widget.js)
        // and is served as a static file on the same origin as the API.
        $widgetUrl = rtrim((string) config('app.url'), '/') . '/widget.js';

        $snippet = <<<HTML
<script src="{$widgetUrl}" data-bot-id="{$bot->uuid}" async></script>
HTML;

        return JsonResponse::success(['snippet' => $snippet], 'Embed snippet generated successfully.');
    }

    private function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        return $user;
    }
}
