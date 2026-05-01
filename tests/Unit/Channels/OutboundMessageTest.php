<?php

declare(strict_types=1);

namespace Tests\Unit\Channels;

use App\Models\Channel;
use App\Services\Channels\Messages\ButtonsMessage;
use App\Services\Channels\Messages\ListMessage;
use App\Services\Channels\Messages\MediaMessage;
use App\Services\Channels\Messages\TemplateMessage;
use App\Services\Channels\Messages\TextMessage;
use Tests\TestCase;

/**
 * Lock down provider-specific payload shapes + Meta-side limits enforced
 * at construction time. These are pure unit tests — no DB, no HTTP — so
 * they run anywhere and catch payload drift fast.
 */
class OutboundMessageTest extends TestCase
{
    private function fakeChannel(string $type): Channel
    {
        $channel = new Channel();
        $channel->type = $type;
        $channel->external_id = '111111';
        return $channel;
    }

    public function test_text_message_whatsapp_shape(): void
    {
        $payload = (new TextMessage('Salut Maria'))
            ->toMetaPayload($this->fakeChannel(Channel::TYPE_WHATSAPP), '40700111222');

        $this->assertSame('whatsapp', $payload['messaging_product']);
        $this->assertSame('40700111222', $payload['to']);
        $this->assertSame('text', $payload['type']);
        $this->assertSame('Salut Maria', $payload['text']['body']);
    }

    public function test_text_message_facebook_shape(): void
    {
        $payload = (new TextMessage('Hi from FB'))
            ->toMetaPayload($this->fakeChannel(Channel::TYPE_FACEBOOK_MESSENGER), 'psid-123');

        $this->assertSame('psid-123', $payload['recipient']['id']);
        $this->assertSame('Hi from FB', $payload['message']['text']);
    }

    public function test_text_message_truncates_long_body_for_instagram(): void
    {
        $long = str_repeat('a', 1500); // > 1000 IG limit, < 4096 ctor limit
        $payload = (new TextMessage($long))
            ->toMetaPayload($this->fakeChannel(Channel::TYPE_INSTAGRAM_DM), 'sender-1');

        $this->assertSame(1000, mb_strlen($payload['message']['text']));
        $this->assertStringEndsWith('…', $payload['message']['text']);
    }

    public function test_text_message_rejects_empty_body(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TextMessage('   ');
    }

    public function test_text_message_rejects_over_4096(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TextMessage(str_repeat('a', 4097));
    }

    public function test_buttons_message_whatsapp_shape(): void
    {
        $msg = new ButtonsMessage('Confirmi programarea?', [
            ['id' => 'yes', 'title' => 'Da'],
            ['id' => 'no', 'title' => 'Nu'],
        ]);

        $payload = $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_WHATSAPP), '40700111222');

        $this->assertSame('interactive', $payload['type']);
        $this->assertSame('button', $payload['interactive']['type']);
        $this->assertSame('Confirmi programarea?', $payload['interactive']['body']['text']);
        $this->assertCount(2, $payload['interactive']['action']['buttons']);
        $this->assertSame('yes', $payload['interactive']['action']['buttons'][0]['reply']['id']);
        $this->assertSame('Da', $payload['interactive']['action']['buttons'][0]['reply']['title']);
    }

    public function test_buttons_message_rejects_more_than_3(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ButtonsMessage('Pick', [
            ['id' => 'a', 'title' => 'A'],
            ['id' => 'b', 'title' => 'B'],
            ['id' => 'c', 'title' => 'C'],
            ['id' => 'd', 'title' => 'D'],
        ]);
    }

    public function test_buttons_message_rejects_duplicate_ids(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ButtonsMessage('Pick', [
            ['id' => 'a', 'title' => 'A'],
            ['id' => 'a', 'title' => 'A2'],
        ]);
    }

    public function test_buttons_message_rejects_title_over_20_chars(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ButtonsMessage('Pick', [
            ['id' => 'a', 'title' => str_repeat('x', 21)],
        ]);
    }

    public function test_buttons_message_throws_on_instagram(): void
    {
        $msg = new ButtonsMessage('Pick', [['id' => 'a', 'title' => 'A']]);
        $this->expectException(\InvalidArgumentException::class);
        $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_INSTAGRAM_DM), 'sender-1');
    }

    public function test_buttons_message_facebook_shape(): void
    {
        $msg = new ButtonsMessage('Pick', [
            ['id' => 'yes', 'title' => 'Da'],
            ['id' => 'no', 'title' => 'Nu'],
        ]);

        $payload = $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_FACEBOOK_MESSENGER), 'psid-1');

        $this->assertSame('button', $payload['message']['attachment']['payload']['template_type']);
        $this->assertSame('postback', $payload['message']['attachment']['payload']['buttons'][0]['type']);
        $this->assertSame('yes', $payload['message']['attachment']['payload']['buttons'][0]['payload']);
    }

    public function test_list_message_whatsapp_shape(): void
    {
        $msg = new ListMessage(
            body: 'Alege un serviciu',
            buttonText: 'Vezi servicii',
            sections: [
                [
                    'title' => 'Servicii populare',
                    'rows' => [
                        ['id' => 'tuns', 'title' => 'Tuns', 'description' => '30 min, 50 lei'],
                        ['id' => 'vopsit', 'title' => 'Vopsit', 'description' => '90 min, 200 lei'],
                    ],
                ],
            ],
            header: 'Salonul tău',
            footer: 'Programează acum',
        );

        $payload = $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_WHATSAPP), '40700111222');

        $this->assertSame('list', $payload['interactive']['type']);
        $this->assertSame('Salonul tău', $payload['interactive']['header']['text']);
        $this->assertSame('Programează acum', $payload['interactive']['footer']['text']);
        $this->assertSame('Vezi servicii', $payload['interactive']['action']['button']);
        $this->assertCount(2, $payload['interactive']['action']['sections'][0]['rows']);
    }

    public function test_list_message_rejects_more_than_10_rows_total(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ListMessage('body', 'btn', [
            [
                'title' => 'Section',
                'rows' => array_map(
                    fn ($i) => ['id' => "r{$i}", 'title' => "Row {$i}"],
                    range(1, 11),
                ),
            ],
        ]);
    }

    public function test_list_message_rejects_duplicate_row_ids_across_sections(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ListMessage('body', 'btn', [
            ['title' => 'A', 'rows' => [['id' => 'dup', 'title' => 'X']]],
            ['title' => 'B', 'rows' => [['id' => 'dup', 'title' => 'Y']]],
        ]);
    }

    public function test_list_message_throws_on_facebook(): void
    {
        $msg = new ListMessage('body', 'btn', [
            ['title' => 'A', 'rows' => [['id' => 'r1', 'title' => 'X']]],
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_FACEBOOK_MESSENGER), 'psid');
    }

    public function test_media_message_image_with_caption(): void
    {
        $msg = new MediaMessage(
            type: 'image',
            url: 'https://cdn.sambla.ro/photo.jpg',
            caption: 'Foto salonul nostru',
        );

        $payload = $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_WHATSAPP), '40700111222');

        $this->assertSame('image', $payload['type']);
        $this->assertSame('https://cdn.sambla.ro/photo.jpg', $payload['image']['link']);
        $this->assertSame('Foto salonul nostru', $payload['image']['caption']);
    }

    public function test_media_message_rejects_non_https(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaMessage('image', 'http://insecure.example/x.jpg');
    }

    public function test_media_message_document_requires_filename(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaMessage('document', 'https://x/file.pdf');
    }

    public function test_media_message_facebook_shape(): void
    {
        $payload = (new MediaMessage('image', 'https://x/p.jpg'))
            ->toMetaPayload($this->fakeChannel(Channel::TYPE_FACEBOOK_MESSENGER), 'psid');

        $this->assertSame('image', $payload['message']['attachment']['type']);
        $this->assertSame('https://x/p.jpg', $payload['message']['attachment']['payload']['url']);
    }

    public function test_template_message_whatsapp_shape(): void
    {
        $msg = new TemplateMessage(
            name: 'booking_reminder',
            language: 'ro',
            bodyParams: ['Maria', '14 mai 14:00'],
        );

        $payload = $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_WHATSAPP), '40700111222');

        $this->assertSame('template', $payload['type']);
        $this->assertSame('booking_reminder', $payload['template']['name']);
        $this->assertSame('ro', $payload['template']['language']['code']);
        $this->assertSame('Maria', $payload['template']['components'][0]['parameters'][0]['text']);
        $this->assertSame('14 mai 14:00', $payload['template']['components'][0]['parameters'][1]['text']);
    }

    public function test_template_message_rejects_uppercase_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TemplateMessage(name: 'BookingReminder');
    }

    public function test_template_message_throws_on_non_whatsapp(): void
    {
        $msg = new TemplateMessage(name: 'hello');
        $this->expectException(\InvalidArgumentException::class);
        $msg->toMetaPayload($this->fakeChannel(Channel::TYPE_FACEBOOK_MESSENGER), 'psid');
    }
}
