<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use RuntimeException;

class TemplateService
{
    /**
     * In-request cache keyed by name|channel to avoid repeated DB hits during batch creates.
     *
     * @var array<string, NotificationTemplate|null>
     */
    private array $templateCache = [];

    /**
     * @param  array<string, mixed>  $variables
     */
    public function render(string $templateName, array $variables, NotificationChannel $channel): string
    {
        $cacheKey = $templateName.'|'.$channel->value;
        if (array_key_exists($cacheKey, $this->templateCache)) {
            $template = $this->templateCache[$cacheKey];
        } else {
            $template = NotificationTemplate::query()
                ->where('name', $templateName)
                ->where('channel', $channel->value)
                ->first();
            $this->templateCache[$cacheKey] = $template;
        }

        if (! $template instanceof NotificationTemplate) {
            throw new RuntimeException("Template [{$templateName}] not found for channel [{$channel->value}].");
        }

        $body = $template->body;
        preg_match_all('/\{\{(\w+)\}\}/', $body, $matches);
        $requiredVariables = array_unique($matches[1]);

        foreach ($requiredVariables as $requiredVariable) {
            if (! array_key_exists($requiredVariable, $variables)) {
                throw new RuntimeException("Missing required template variable [{$requiredVariable}].");
            }
        }

        return (string) preg_replace_callback('/\{\{(\w+)\}\}/', function (array $match) use ($variables): string {
            $key = $match[1];

            return (string) ($variables[$key] ?? '');
        }, $body);
    }
}
