<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNotificationRequest extends FormRequest
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
            'recipient' => ['required', 'string', 'max:100'],
            'channel' => ['required', 'string', Rule::enum(NotificationChannel::class)],
            'content' => ['nullable', 'string', 'required_without:template_name', $this->contentLengthRule('channel')],
            'priority' => ['sometimes', 'string', Rule::enum(NotificationPriority::class)],
            'idempotency_key' => ['sometimes', 'string', 'max:100'],
            'scheduled_at' => ['sometimes', 'date', 'after_or_equal:now'],
            'template_name' => ['sometimes', 'string', Rule::exists('notification_templates', 'name')],
            'template_variables' => ['sometimes', 'array', 'required_with:template_name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('priority')) {
            $this->merge(['priority' => NotificationPriority::Normal->value]);
        }
    }

    protected function contentLengthRule(string $channelAttribute): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($channelAttribute): void {
            $channel = $this->input($channelAttribute);

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

    // Check if template_variables are present when template_name is present
    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('template_name') && ! $this->filled('template_variables')) {
                    $validator->errors()->add('template_variables', 'The template_variables field is required when template_name is present.');
                }
            },
        ];
    }
}
