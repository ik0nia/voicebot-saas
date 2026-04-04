<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index definitions: [name, table, SQL]
     */
    private function indexDefinitions(): array
    {
        return [
            // 1. messages: (conversation_id, created_at)
            [
                'messages_conversation_id_created_at_index',
                'messages',
                'CREATE INDEX IF NOT EXISTS messages_conversation_id_created_at_index ON messages (conversation_id, created_at)',
            ],
            // 2. messages: (conversation_id, direction)
            [
                'messages_conversation_id_direction_index',
                'messages',
                'CREATE INDEX IF NOT EXISTS messages_conversation_id_direction_index ON messages (conversation_id, direction)',
            ],
            // 3. chat_events: partial index on (product_id, event_name, occurred_at)
            [
                'chat_events_product_event_occurred_partial_index',
                'chat_events',
                "CREATE INDEX IF NOT EXISTS chat_events_product_event_occurred_partial_index ON chat_events (product_id, event_name, occurred_at) WHERE event_name IN ('product_click', 'add_to_cart_success')",
            ],
            // 4. chat_events: GIN index on properties jsonb
            [
                'chat_events_properties_gin_index',
                'chat_events',
                'CREATE INDEX IF NOT EXISTS chat_events_properties_gin_index ON chat_events USING GIN (properties jsonb_ops)',
            ],
            // 5. leads: (conversation_id)
            [
                'leads_conversation_id_index',
                'leads',
                'CREATE INDEX IF NOT EXISTS leads_conversation_id_index ON leads (conversation_id)',
            ],
            // 6. leads: (tenant_id, status, created_at)
            [
                'leads_tenant_id_status_created_at_index',
                'leads',
                'CREATE INDEX IF NOT EXISTS leads_tenant_id_status_created_at_index ON leads (tenant_id, status, created_at)',
            ],
            // 7. conversations: (channel_id, external_conversation_id)
            [
                'conversations_channel_id_external_conversation_id_index',
                'conversations',
                'CREATE INDEX IF NOT EXISTS conversations_channel_id_external_conversation_id_index ON conversations (channel_id, external_conversation_id)',
            ],
            // 8. bot_knowledge: (bot_id, status, title)
            [
                'bot_knowledge_bot_id_status_title_index',
                'bot_knowledge',
                'CREATE INDEX IF NOT EXISTS bot_knowledge_bot_id_status_title_index ON bot_knowledge (bot_id, status, title)',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexDefinitions() as [$name, $table, $sql]) {
            if (Schema::hasTable($table)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        $drops = [
            'messages' => [
                'messages_conversation_id_created_at_index',
                'messages_conversation_id_direction_index',
            ],
            'chat_events' => [
                'chat_events_product_event_occurred_partial_index',
                'chat_events_properties_gin_index',
            ],
            'leads' => [
                'leads_conversation_id_index',
                'leads_tenant_id_status_created_at_index',
            ],
            'conversations' => [
                'conversations_channel_id_external_conversation_id_index',
            ],
            'bot_knowledge' => [
                'bot_knowledge_bot_id_status_title_index',
            ],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                foreach ($indexes as $index) {
                    DB::statement("DROP INDEX IF EXISTS {$index}");
                }
            }
        }
    }
};
