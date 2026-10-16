<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        NotificationTemplate::create([
            'name' => 'product_price_drop',
            'channel' => 'email',
            'body' => '{{product_name}} is now {{discount}}% OFF! Hurry, limited time offer!',
        ]);

        NotificationTemplate::create([
            'name' => 'welcome',
            'channel' => 'email',
            'body' => 'Welcome to Our Company, {{name}}! Your account is ready. Get started at {{dashboard_url}}.',
        ]);

        NotificationTemplate::create([
            'name' => 'product_on_sale',
            'channel' => 'push',
            'body' => '{{product_name}} is now {{discount}}% OFF! Hurry, limited time offer!',
        ]);
    }
}
