<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * php artisan sambla:release-plugin 2.3.0
 *
 * One-shot WordPress plugin release. Does the five steps that
 * separate "bumped version in source" from "a working update in
 * tenants' WP admin":
 *
 *   1. Bumps the version header + SAMBLA_VERSION constant in the
 *      plugin's main PHP file.
 *   2. Builds a versioned ZIP at public/downloads/sambla-woocommerce-{version}.zip.
 *   3. Updates the generic stable pointer
 *      public/downloads/sambla-woocommerce.zip (legacy URL).
 *   4. Updates SAMBLA_PLUGIN_VERSION in .env.
 *   5. Clears config cache so the change is live.
 *
 * What it does NOT do (kept explicit to avoid surprises):
 *   - git commit / push (do that yourself with a clear message).
 *   - changelog edits in PluginUpdateController::getChangelog() —
 *     write the changelog yourself; --changelog only prints a
 *     suggested block you can paste.
 *   - Cloudflare purge — the package URL is versioned, so a cold
 *     CDN serves the right ZIP on first hit anyway.
 */
class ReleasePlugin extends Command
{
    protected $signature = 'sambla:release-plugin
        {version : New semver (e.g. 2.3.0)}
        {--dry-run : Preview what would change, no writes}
        {--force : Allow downgrade or same-version rebuild}';

    protected $description = 'Package and publish a new WooCommerce plugin version (bump + zip + env + cache clear).';

    public function handle(): int
    {
        $version = (string) $this->argument('version');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->error("Version must look like X.Y.Z — got '{$version}'.");
            return self::FAILURE;
        }

        $pluginDir  = base_path('wordpress-plugin/sambla-woocommerce');
        $pluginFile = $pluginDir . '/sambla-woocommerce.php';
        if (!is_file($pluginFile)) {
            $this->error("Plugin source missing at {$pluginFile}");
            return self::FAILURE;
        }

        $source = file_get_contents($pluginFile);
        if (!preg_match('/^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$/m', $source, $m)) {
            $this->error('Could not read current version from plugin header.');
            return self::FAILURE;
        }
        $currentVersion = $m[1];

        $this->line('');
        $this->line("<info>Sambla plugin release</info>");
        $this->line("  current: {$currentVersion}");
        $this->line("  target:  {$version}");
        $this->line('');

        if (version_compare($version, $currentVersion, '<=') && !$force) {
            $this->error("Target {$version} is not newer than current {$currentVersion}. Use --force to override.");
            return self::FAILURE;
        }

        $versionedZip = base_path("public/downloads/sambla-woocommerce-{$version}.zip");
        $stableZip    = base_path("public/downloads/sambla-woocommerce.zip");
        $envFile      = base_path('.env');

        if ($dryRun) {
            $this->line('<comment>DRY RUN — no writes will happen.</comment>');
            $this->line('');
        }

        // Step 1 — bump version in source
        $this->line('<info>[1/5]</info> Bump plugin header + SAMBLA_VERSION constant');
        // Use ${1} / ${2} explicitly — `$1 . $version . $2` concatenates
        // to e.g. `$12.2.1$2`, and preg_replace parses `$12` as group 12
        // (non-existent → empty), wiping the ` * Version: ` prefix and
        // leaving just `.2.1` on that line. Bit us twice in one release.
        $newSource = preg_replace('/^(\s*\*\s*Version:\s*)\d+\.\d+\.\d+(\s*)$/m', '${1}' . $version . '${2}', $source);
        $newSource = preg_replace("/SAMBLA_VERSION',\s*'\d+\.\d+\.\d+'/", "SAMBLA_VERSION', '{$version}'", $newSource);
        if ($newSource === $source) {
            $this->error('Version bump regex did not match anything — check plugin header format.');
            return self::FAILURE;
        }
        if (!$dryRun) {
            file_put_contents($pluginFile, $newSource);
        }
        $this->line("     OK — {$pluginFile}");

        // Step 2 — build versioned zip
        $this->line('<info>[2/5]</info> Build versioned ZIP');
        if (!$dryRun) {
            if (is_file($versionedZip)) {
                unlink($versionedZip);
            }
            $parent = dirname($pluginDir);
            $cmd = 'cd ' . escapeshellarg($parent)
                . ' && zip -r ' . escapeshellarg($versionedZip)
                . ' sambla-woocommerce -x "*.DS_Store" "*.bak*" "*__MACOSX*" 2>&1';
            exec($cmd, $out, $rc);
            if ($rc !== 0) {
                $this->error('zip failed: ' . implode("\n", $out));
                return self::FAILURE;
            }
        }
        $this->line("     OK — {$versionedZip}");

        // Step 3 — stable pointer
        $this->line('<info>[3/5]</info> Update stable pointer ' . basename($stableZip));
        if (!$dryRun) {
            if (is_file($stableZip) && !is_writable($stableZip)) {
                // Common: stable zip owned by root from an earlier
                // deploy. Try a sudo cp, fall back with a clear hint.
                $cp = 'sudo -n cp ' . escapeshellarg($versionedZip) . ' ' . escapeshellarg($stableZip) . ' 2>&1';
                exec($cp, $out2, $rc2);
                if ($rc2 !== 0) {
                    $this->warn('     Skipped (permission denied). Run manually:');
                    $this->warn('       sudo cp ' . $versionedZip . ' ' . $stableZip);
                } else {
                    $this->line("     OK — {$stableZip} (via sudo)");
                }
            } else {
                copy($versionedZip, $stableZip);
                $this->line("     OK — {$stableZip}");
            }
        }

        // Step 4 — bump .env
        $this->line('<info>[4/5]</info> Bump SAMBLA_PLUGIN_VERSION in .env');
        if (is_file($envFile)) {
            $env = file_get_contents($envFile);
            if (preg_match('/^SAMBLA_PLUGIN_VERSION=.*$/m', $env)) {
                $newEnv = preg_replace('/^SAMBLA_PLUGIN_VERSION=.*$/m', "SAMBLA_PLUGIN_VERSION={$version}", $env);
            } else {
                $newEnv = rtrim($env, "\n") . "\nSAMBLA_PLUGIN_VERSION={$version}\n";
            }
            if (!$dryRun) {
                file_put_contents($envFile, $newEnv);
            }
            $this->line("     OK — {$envFile}");
        } else {
            $this->warn("     Skipped — {$envFile} not found");
        }

        // Step 5 — clear config cache so the new version is live
        $this->line('<info>[5/5]</info> Clear config cache');
        if (!$dryRun) {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
        }
        $this->line('     OK');

        $this->line('');
        $this->info("Release {$version} ready.");
        $this->line('');
        $this->line('<comment>Next steps (manual):</comment>');
        $this->line('  1. Add a changelog entry in');
        $this->line('       app/Http/Controllers/Api/V1/PluginUpdateController.php::getChangelog()');
        $this->line("     (prepend a <h4>{$version}</h4><ul><li>...</li></ul> block).");
        $this->line('  2. Commit:');
        $this->line("       git add wordpress-plugin/ public/downloads/sambla-woocommerce-{$version}.zip public/downloads/sambla-woocommerce.zip app/Http/Controllers/Api/V1/PluginUpdateController.php");
        $this->line("       git commit -m \"release: plugin {$version}\"");
        $this->line('       git push origin master');
        $this->line('  3. Verify in WP admin (force refresh):');
        $this->line('       Dashboard → Updates → Check Again');
        $this->line("       Plugins → 'View version {$version} details'");
        $this->line('');

        return self::SUCCESS;
    }
}
