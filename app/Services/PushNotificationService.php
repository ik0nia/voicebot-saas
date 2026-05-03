<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;

/**
 * Trimite Web Push notifications către toate dispozitivele unui user
 * (sau user-ilor unui tenant).
 *
 * Cleanup automat la 410 Gone (subscription expirată/revoked).
 */
class PushNotificationService
{
    public function sendToUser(int $userId, array $payload): int
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();
        return $this->sendToMany($subscriptions, $payload);
    }

    public function sendToTenantUsers(int $tenantId, array $payload, ?int $excludeUserId = null): int
    {
        $userIds = \App\Models\User::where('tenant_id', $tenantId)
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->pluck('id');

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();
        return $this->sendToMany($subscriptions, $payload);
    }

    /**
     * @param  iterable<PushSubscription>  $subscriptions
     * @param  array{title:string,body:string,url?:string,tag?:string,icon?:string}  $payload
     */
    private function sendToMany(iterable $subscriptions, array $payload): int
    {
        if (!config('services.vapid.public') || !config('services.vapid.private')) {
            Log::info('PushNotification skipped — VAPID not configured');
            return 0;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('services.vapid.subject', 'mailto:noreply@sambla.ro'),
                'publicKey' => config('services.vapid.public'),
                'privateKey' => config('services.vapid.private'),
            ],
        ];

        $webPush = new WebPush($auth);
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $expired = [];
        foreach ($subscriptions as $sub) {
            $webPush->queueNotification($sub->toWebPushSubscription(), $jsonPayload);
            $sent++;
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }
            // 404/410 = subscription expirată; cleanup
            $reason = $report->getReason();
            $statusCode = $report->getResponse()?->getStatusCode();
            if (in_array($statusCode, [404, 410], true)) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                $expired[] = $endpoint;
            } else {
                Log::warning('Push delivery failed', [
                    'endpoint' => substr($report->getRequest()->getUri()->__toString(), 0, 80),
                    'status' => $statusCode,
                    'reason' => $reason,
                ]);
            }
        }

        if (!empty($expired)) {
            PushSubscription::whereIn('endpoint', $expired)->delete();
        }

        return $sent;
    }
}
