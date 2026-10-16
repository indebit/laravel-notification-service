<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    /**
     * List templates
     *
     * Returns all notification templates.
     *
     * @group Templates
     */
    public function index(): AnonymousResourceCollection
    {
        return TemplateResource::collection(
            NotificationTemplate::query()->orderBy('name')->paginate(15),
        );
    }

    /**
     * Create template
     *
     * Creates a notification template for a specific channel.
     *
     * @group Templates
     *
     * @bodyParam name string required Template unique identifier. Example: order_shipped
     * @bodyParam channel string required Target channel. Example: sms
     * @bodyParam body string required Template body with placeholders. Example: Hi {{name}}, your order {{order_id}} has shipped!
     *
     * @response 201 {"data":{"id":"uuid","name":"order_shipped","channel":"sms","body":"Hi {{name}}, your order {{order_id}} has shipped!","created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:00.000000Z"}}
     */
    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $template = NotificationTemplate::query()->create($request->validated());

        return (new TemplateResource($template))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show template
     *
     * Returns a template by UUID.
     *
     * @group Templates
     *
     * @urlParam template string required Template UUID. Example: 019dd969-1008-7336-b2e4-00b27e14aa0a
     */
    public function show(NotificationTemplate $template): TemplateResource
    {
        return new TemplateResource($template);
    }
}
