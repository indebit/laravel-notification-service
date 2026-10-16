<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Validation\ValidationException;

class TemplateService
{
    /**
     * In-request cache keyed by name|channel to avoid repeated DB hits during batch creates.
     *
     * @var array<string, NotificationTemplate|null>
     */
    private array $templateCache = [];

    /**
     * Render a template for a channel, replacing {{variable}} placeholders.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws ValidationException when the template does not exist for the channel or a variable is missing
     */
    public function render(string $templateName, array $variables, NotificationChannel $channel): string
    {
        $template = $this->find($templateName, $channel);

        if (! $template instanceof NotificationTemplate) {
            throw ValidationException::withMessages([
                'template_name' => ["Template [{$templateName}] does not exist for the {$channel->value} channel."],
            ]);
        }

        preg_match_all('/\{\{(\w+)\}\}/', $template->body, $matches);
        $missing = array_values(array_diff(array_unique($matches[1]), array_keys($variables)));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'template_variables' => ['Missing template variables: '.implode(', ', $missing).'.'],
            ]);
        }

        return (string) preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            fn (array $match): string => (string) $variables[$match[1]],
            $template->body,
        );
    }

    private function find(string $templateName, NotificationChannel $channel): ?NotificationTemplate
    {
        $cacheKey = $templateName.'|'.$channel->value;

        if (! array_key_exists($cacheKey, $this->templateCache)) {
            $this->templateCache[$cacheKey] = NotificationTemplate::query()
                ->where('name', $templateName)
                ->where('channel', $channel->value)
                ->first();
        }

        return $this->templateCache[$cacheKey];
    }
}
