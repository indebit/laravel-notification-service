<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notifications' => ['required', 'array', 'min:1', 'max:1000'],
            'notifications.*.recipient' => ['required', 'string', 'max:100'],
            'notifications.*.channel' => ['required', 'string', Rule::enum(NotificationChannel::class)],
            'notifications.*.content' => ['nullable', 'string', 'required_without:notifications.*.template_name', $this->contentLengthRule()],
            'notifications.*.priority' => ['sometimes', 'string', Rule::enum(NotificationPriority::class)],
            'notifications.*.idempotency_key' => ['sometimes', 'string', 'max:100'],
            'notifications.*.scheduled_at' => ['sometimes', 'date', 'after_or_equal:now'],
            'notifications.*.template_name' => ['sometimes', 'string', Rule::exists('notification_templates', 'name')],
            'notifications.*.template_variables' => ['required_with:notifications.*.template_name', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('notifications', []);
        $items = is_array($raw) ? $raw : [];

        $notifications = collect($items)
            ->map(function (array $notification): array {
                if (! array_key_exists('priority', $notification)) {
                    $notification['priority'] = NotificationPriority::Normal->value;
                }

                return $notification;
            })
            ->values()
            ->all();

        $this->merge(['notifications' => $notifications]);
    }

    protected function contentLengthRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $segments = explode('.', $attribute);
            $index = $segments[1] ?? null;

            if ($index === null) {
                return;
            }

            $channel = $this->input("notifications.{$index}.channel");

            $maxLength = is_string($channel) ? NotificationChannel::tryFrom($channel)?->maxContentLength() : null;

            if ($maxLength !== null && is_string($value) && mb_strlen($value) > $maxLength) {
                $fail("The {$attribute} field may not be greater than {$maxLength} characters for the selected channel.");
            }
        };
    }
}
