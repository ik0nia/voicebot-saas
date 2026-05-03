<?php

namespace App\Models\Traits;

use App\Models\AuditLog;

/**
 * Trait Auditable — atașează audit pe creating/updating/deleting.
 *
 * Toate evenimentele se scriu în tabela `audit_log` cu:
 *   - tenant_id  (auto din BelongsToTenant)
 *   - user_id    (auto din auth())
 *   - action     "<subjectKey>.<verb>"  (ex. "bot.created")
 *   - changes    diff doar pe câmpurile care s-au schimbat
 *   - ip / user_agent / route  (auto din request curent)
 *
 * Modelele care folosesc acest trait pot suprascrie:
 *   - public function auditSubjectKey(): string  — default = snake-case class
 *   - public function auditExcludedAttributes(): array — câmpuri ignorate
 */
trait Auditable
{
    /**
     * Boot the trait — register Eloquent observers.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->writeAudit('created', $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = [];
            foreach ($model->getChanges() as $key => $newValue) {
                if (in_array($key, $model->resolveAuditExcludedAttributes(), true)) {
                    continue;
                }
                $original = $model->getOriginal($key);
                $changes[$key] = [$original, $newValue];
            }
            if (empty($changes)) {
                // Nothing meaningful changed (only excluded fields).
                return;
            }
            $model->writeAudit('updated', $changes);
        });

        static::deleted(function ($model) {
            $model->writeAudit('deleted', null);
        });
    }

    /**
     * Override pentru un slug custom în action (ex. "agent" în loc de "bot").
     */
    public function auditSubjectKey(): string
    {
        $class = class_basename($this);
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $class));
    }

    /**
     * Override per model pentru câmpuri pe care nu vrem să le surprindem
     * (ex. timestamps, hash-uri, counters interne).
     */
    public function auditExcludedAttributes(): array
    {
        return [];
    }

    protected function resolveAuditExcludedAttributes(): array
    {
        return array_merge(
            ['updated_at', 'created_at', 'remember_token', 'password'],
            $this->auditExcludedAttributes()
        );
    }

    /**
     * Scrie efectiv intrarea în audit_log. Robust la erori — un fail
     * de audit nu trebuie să arunce excepție pe creating/updating;
     * loghează și continuă.
     */
    protected function writeAudit(string $verb, ?array $payload): void
    {
        try {
            $request = app('request');
            AuditLog::create([
                'tenant_id'      => $this->tenant_id ?? (auth()->user()->tenant_id ?? null),
                'user_id'        => auth()->id(),
                'action'         => $this->auditSubjectKey() . '.' . $verb,
                'auditable_type' => static::class,
                'auditable_id'   => $this->getKey(),
                'changes'        => $payload ? $this->sanitizeChanges($payload) : null,
                'ip'             => $request?->ip(),
                'user_agent'     => substr((string) $request?->userAgent(), 0, 500),
                'route'          => $request?->route()?->getName(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Audit write failed', [
                'model' => static::class,
                'id' => $this->getKey() ?? null,
                'verb' => $verb,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trunchiază valorile lungi (ex. blob-uri JSON > 1KB) ca să nu
     * umflăm tabela cu istoric inutil.
     */
    protected function sanitizeChanges(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (is_array($v) && count($v) === 2) {
                // [old, new] format from updates
                $out[$k] = [$this->truncateValue($v[0]), $this->truncateValue($v[1])];
            } else {
                $out[$k] = $this->truncateValue($v);
            }
        }
        return $out;
    }

    protected function truncateValue($v)
    {
        if (is_string($v) && strlen($v) > 500) {
            return substr($v, 0, 500) . '… [+' . (strlen($v) - 500) . ' chars]';
        }
        if (is_array($v) || is_object($v)) {
            $json = json_encode($v);
            if (strlen($json) > 500) {
                return substr($json, 0, 500) . '… [truncated]';
            }
        }
        return $v;
    }
}
