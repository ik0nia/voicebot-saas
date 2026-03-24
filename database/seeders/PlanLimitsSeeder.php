<?php

namespace Database\Seeders;

use App\Models\PlanLimit;
use Illuminate\Database\Seeder;

class PlanLimitsSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // ┌──────────────────────────────────────────────────────────────┐
            // │  FREE - testare, demonstrare, evaluare platformă            │
            // └──────────────────────────────────────────────────────────────┘
            [
                'slug' => 'free',
                'name' => 'Free',
                'price_monthly' => 0,
                'limits' => [
                    'max_bots' => 1,
                    'max_sites' => 1,
                    'max_knowledge_kb' => 50,
                    'max_agents' => 5,
                    'max_agent_runs_per_month' => 10,
                    'max_tokens_per_month' => 100_000,
                    'max_scan_pages_per_month' => 20,
                    'max_connectors' => 0,
                ],
                'features' => [],
                'allowed_agents' => [
                    'product-specialist',
                    'faq-generator',
                    'policy-writer',
                    'response-templates',
                    'greeting-closing',
                ],
                'allowed_file_formats' => ['text', 'txt', 'url'],
                'max_upload_size_kb' => 2_048,          // 2 MB
                'sort_order' => 1,
            ],

            // ┌──────────────────────────────────────────────────────────────┐
            // │  STARTER - freelanceri, micro-business  (29 EUR/lună)       │
            // └──────────────────────────────────────────────────────────────┘
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price_monthly' => 29.00,
                'limits' => [
                    'max_bots' => 3,
                    'max_sites' => 3,
                    'max_knowledge_kb' => 200,
                    'max_agents' => 12,
                    'max_agent_runs_per_month' => 50,
                    'max_tokens_per_month' => 500_000,
                    'max_scan_pages_per_month' => 100,
                    'max_connectors' => 2,
                ],
                'features' => [
                    'custom_prompts' => true,
                    'website_scanner' => true,
                ],
                'allowed_agents' => [
                    'product-specialist',
                    'faq-generator',
                    'policy-writer',
                    'response-templates',
                    'greeting-closing',
                    'onboarding-guide',
                    'sales-script',
                    'tech-docs',
                    'tone-analyzer',
                    'gap-analyzer',
                    'accuracy-checker',
                    'readability-optimizer',
                ],
                'allowed_file_formats' => ['text', 'txt', 'url', 'pdf', 'csv'],
                'max_upload_size_kb' => 10_240,         // 10 MB
                'sort_order' => 2,
            ],

            // ┌──────────────────────────────────────────────────────────────┐
            // │  PRO - agenții, echipe mici-medii  (79 EUR/lună)            │
            // └──────────────────────────────────────────────────────────────┘
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_monthly' => 79.00,
                'limits' => [
                    'max_bots' => 10,
                    'max_sites' => 10,
                    'max_knowledge_kb' => 1_000,
                    'max_agents' => 20,
                    'max_agent_runs_per_month' => 200,
                    'max_tokens_per_month' => 2_000_000,
                    'max_scan_pages_per_month' => 500,
                    'max_connectors' => 10,
                ],
                'features' => [
                    'custom_prompts' => true,
                    'website_scanner' => true,
                    'export_knowledge' => true,
                    'priority_processing' => true,
                ],
                'allowed_agents' => null,               // null = toți agenții disponibili
                'allowed_file_formats' => ['text', 'txt', 'url', 'pdf', 'csv', 'docx'],
                'max_upload_size_kb' => 25_600,         // 25 MB
                'sort_order' => 3,
            ],

            // ┌──────────────────────────────────────────────────────────────┐
            // │  ENTERPRISE - corporații, volume mari  (199 EUR/lună)        │
            // └──────────────────────────────────────────────────────────────┘
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly' => 199.00,
                'limits' => [
                    'max_bots' => 50,
                    'max_sites' => 50,
                    'max_knowledge_kb' => 5_000,
                    'max_agents' => 20,
                    'max_agent_runs_per_month' => 1_000,
                    'max_tokens_per_month' => 10_000_000,
                    'max_scan_pages_per_month' => 2_000,
                    'max_connectors' => 50,
                ],
                'features' => [
                    'custom_prompts' => true,
                    'website_scanner' => true,
                    'export_knowledge' => true,
                    'priority_processing' => true,
                    'dedicated_support' => true,
                    'api_access' => true,
                    'white_label' => true,
                ],
                'allowed_agents' => null,               // null = toți agenții disponibili
                'allowed_file_formats' => ['text', 'txt', 'url', 'pdf', 'csv', 'docx'],
                'max_upload_size_kb' => 51_200,         // 50 MB
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            PlanLimit::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
