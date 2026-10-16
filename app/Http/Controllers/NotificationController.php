<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Events\NotificationStatusChanged;
use App\Http\Requests\BatchNotificationRequest;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(public NotificationService $notificationService) {}

    /**
     * Trigger a WebSocket test broadcast
     *
     * Debug-only helper endpoint that broadcasts a sample `NotificationStatusChanged`
     * event for the latest notification. Use this to verify Reverb/Echo subscriptions
     * without waiting for a real provider callback.
     *
     * @group Websocket Testing
     *
     * @response 200 {"message":"Broadcast sent","notification":{"id":"uuid","status":"pending"}}
     * @response 404 {"message":"No notifications found"}
     */
    public function testBroadcast(): JsonResponse
    {
        $notification = Notification::query()->latest()->first();
        if ($notification) {
            broadcast(new NotificationStatusChanged(
                $notification,
                NotificationStatus::Pending,
                NotificationStatus::Delivered
            ));

            return response()->json([
                'message' => 'Broadcast sent',
                'notification' => $notification,
            ], Response::HTTP_OK);
        }

        return response()->json(['message' => 'No notifications found'], Response::HTTP_NOT_FOUND)->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    /**
     * Create a notification
     *
     * Creates a new notification and queues it for delivery. If an idempotency_key
     * is provided and matches an existing notification, the existing one is returned.
     *
     * @group Notifications
     *
     * @bodyParam recipient string required The notification recipient (phone, email, or device token). Example: +40745123456
     * @bodyParam channel string required The delivery channel. Example: sms
     * @bodyParam content string required The message content. Example: Your order has shipped!
     * @bodyParam priority string The priority level. Defaults to normal. Example: high
     *
     * @response 201 scenario="Notification created" {"data":{"id":"uuid","batch_id":null,"channel":"sms","recipient":"+40745123456","content":"Your order has shipped!","priority":"high","status":"pending","idempotency_key":"order-123-shipped","processing_started_at":null,"delivered_at":null,"failed_at":null,"attempt_count":0,"last_error":null,"external_message_id":null,"created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:00.000000Z"}}
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->create($request->validated());

        return (new NotificationResource($notification))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Create a batch of notifications
     *
     * Creates up to 1000 notifications in a single request, assigns a shared batch ID,
     * and queues each notification based on priority.
     *
     * @group Notifications
     *
     * @bodyParam notifications array required Notification payloads (min 1, max 1000 items).
     * @bodyParam notifications[].recipient string required Recipient for each notification. Example: user@example.com
     * @bodyParam notifications[].channel string required Delivery channel for each notification. Example: email
     * @bodyParam notifications[].content string required Message content. Example: Flash sale starts now!
     * @bodyParam notifications[].priority string Optional priority. Defaults to normal. Example: low
     *
     * @response 201 scenario="Batch created" {"batch_id":"uuid","notifications":[{"id":"uuid","batch_id":"uuid","channel":"email","recipient":"user@example.com","content":"Flash sale starts now!","priority":"normal","status":"pending","idempotency_key":null,"processing_started_at":null,"delivered_at":null,"failed_at":null,"attempt_count":0,"last_error":null,"external_message_id":null,"created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:00.000000Z"}]}
     */
    public function storeBatch(BatchNotificationRequest $request): JsonResponse
    {
        $result = $this->notificationService->createBatch($request->validated()['notifications']);

        return response()->json([
            'batch_id' => $result['batch_id'],
            'notifications' => new NotificationCollection($result['notifications']),
        ], Response::HTTP_CREATED);
    }

    /**
     * Get a notification by ID
     *
     * Returns the notification details and delivery logs (if present).
     *
     * @group Notifications
     *
     * @response 200 scenario="Notification found" {"data":{"id":"uuid","batch_id":null,"channel":"sms","recipient":"+40745123456","content":"Your order has shipped!","priority":"high","status":"delivered","idempotency_key":"order-123-shipped","processing_started_at":"2026-04-29T10:00:01.000000Z","delivered_at":"2026-04-29T10:00:02.000000Z","failed_at":null,"attempt_count":1,"last_error":null,"external_message_id":"provider-msg-123","created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:02.000000Z","logs":[{"id":1,"notification_id":"uuid","attempt_number":1,"status":"accepted","response_body":{"status":"accepted"},"error_message":null,"latency_ms":120,"created_at":"2026-04-29T10:00:02.000000Z"}]}}
     */
    public function show(Notification $notification): NotificationResource
    {
        $notification->load('logs');

        return new NotificationResource($notification);
    }

    /**
     * Get notifications by batch ID
     *
     * Returns paginated notifications belonging to the same batch.
     *
     * @group Notifications
     *
     * @urlParam batchId string required Batch UUID. Example: d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe
     *
     * @response 200 {"data":[{"id":"uuid","batch_id":"uuid","channel":"email","recipient":"user@example.com","content":"Batch message","priority":"normal","status":"pending","idempotency_key":null,"processing_started_at":null,"delivered_at":null,"failed_at":null,"attempt_count":0,"last_error":null,"external_message_id":null,"created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:00.000000Z"}],"links":{"first":"http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe?page=1","last":"http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"path":"http://localhost:8000/api/notifications/batch/d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe","per_page":15,"to":1,"total":1}}
     */
    public function showBatch(string $batchId): NotificationCollection
    {
        $notifications = Notification::query()
            ->byBatchId($batchId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return new NotificationCollection($notifications);
    }

    /**
     * Cancel a notification
     *
     * Cancels a notification if and only if the current status is pending.
     *
     * @group Notifications
     *
     * @response 200 {"data":{"id":"uuid","status":"cancelled","updated_at":"2026-04-29T10:15:00.000000Z"}}
     * @response 422 {"message":"The given data was invalid.","errors":{"notification":["Only pending notifications can be cancelled."]}}
     */
    public function cancel(Notification $notification): NotificationResource
    {
        return new NotificationResource(
            $this->notificationService->cancel($notification),
        );
    }

    /**
     * List notifications
     *
     * Returns paginated notifications with optional filtering by status, channel,
     * date range, and batch ID.
     *
     * @group Notifications
     *
     * @queryParam status string Filter by notification status. Example: pending
     * @queryParam channel string Filter by channel. Example: sms
     * @queryParam from string ISO 8601 start date. Example: 2026-04-29T00:00:00+00:00
     * @queryParam to string ISO 8601 end date. Example: 2026-04-29T23:59:59+00:00
     * @queryParam per_page integer Page size (1-100). Example: 15
     * @queryParam batch_id string Filter by batch UUID. Example: d2c68f90-76d8-4c8e-9ad7-0f8b4a4cb9fe
     *
     * @response 200 {"data":[{"id":"uuid","channel":"sms","recipient":"+40745123456","content":"Your order has shipped!","priority":"high","status":"pending","created_at":"2026-04-29T10:00:00.000000Z","updated_at":"2026-04-29T10:00:00.000000Z"}],"links":{"first":"http://localhost:8000/api/notifications?page=1","last":"http://localhost:8000/api/notifications?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"path":"http://localhost:8000/api/notifications","per_page":15,"to":1,"total":1}}
     */
    public function index(ListNotificationsRequest $request): NotificationCollection
    {
        $paginator = $this->notificationService->list(
            $request->validated(),
            (int) $request->validated('per_page', 15),
        );

        return new NotificationCollection($paginator);
    }
}
