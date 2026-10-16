<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'notifications.*.template_variables' => ['sometimes', 'array'],
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

            $maxLength = match ($channel) {
                NotificationChannel::Sms->value => 160,
                NotificationChannel::Email->value => 10000,
                NotificationChannel::Push->value => 256,
                default => null,
            };

            if ($maxLength !== null && is_string($value) && mb_strlen($value) > $maxLength) {
                $fail("The {$attribute} field may not be greater than {$maxLength} characters for the selected channel.");
            }
        };
    }

    // Check if both content and template_name are not present when template_variables are present
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->input('notifications', []) as $index => $notification) {
                    $hasContent = isset($notification['content']) && is_string($notification['content']) && $notification['content'] !== '';
                    $hasTemplate = isset($notification['template_name']) && is_string($notification['template_name']) && $notification['template_name'] !== '';
                    $hasVariables = isset($notification['template_variables']) && is_array($notification['template_variables']);

                    if (! $hasContent && ! $hasTemplate) {
                        $validator->errors()->add("notifications.{$index}.content", 'The content field is required when template_name is not present.');
                    }

                    if ($hasTemplate && ! $hasVariables) {
                        $validator->errors()->add("notifications.{$index}.template_variables", 'The template_variables field is required when template_name is present.');
                    }
                }
            },
        ];
    }
}
