<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeatureFlag;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['name' => 'two_factor_auth',    'notes' => '#1 — TOTP 2FA for user accounts'],
            ['name' => 'onboarding',          'notes' => '#3 — Post-registration onboarding wizard'],
            ['name' => 'data_export',         'notes' => '#5 — GDPR data export'],
            ['name' => 'post_analytics',      'notes' => '#6 — Post/story view counts and analytics'],
            ['name' => 'verified_badges',     'notes' => '#7 — Verified account badge system'],
            ['name' => 'polls',               'notes' => '#8 — Polls on posts'],
            ['name' => 'content_warnings',    'notes' => '#9 — Sensitive media blur overlay'],
            ['name' => 'skeleton_loaders',    'notes' => '#10 — Skeleton loading states'],
            ['name' => 'seo_meta_tags',       'notes' => '#11 — Open Graph meta tags'],
            ['name' => 'read_receipt_control','notes' => '#16 — User control over read receipts and online status'],
            ['name' => 'link_previews',       'notes' => '#17 — OG link preview cards in chat'],
            ['name' => 'keyboard_shortcuts',  'notes' => '#18 — Keyboard navigation shortcuts'],
            ['name' => 'accessibility',       'notes' => '#19 — ARIA labels and a11y improvements'],
            ['name' => 'error_monitoring',    'notes' => '#22 — Sentry error tracking'],
            ['name' => 'feature_flags',       'notes' => '#23 — This feature flag system itself'],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::firstOrCreate(
                ['name' => $flag['name']],
                ['enabled' => false, 'rollout_percentage' => 100, 'notes' => $flag['notes']]
            );
        }
    }
}
