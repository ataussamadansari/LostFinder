<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingService
{
    protected const CACHE_KEY = 'system_settings_cache_all';

    /**
     * Default application configuration matrix.
     */
    protected array $defaultSettings = [
        // General Group
        'general.app_name' => [
            'group' => 'general',
            'value' => 'LostFinder',
            'type' => 'string',
            'label' => 'Application Name',
            'description' => 'The brand name displayed across passenger and driver interfaces.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'general.support_email' => [
            'group' => 'general',
            'value' => 'support@lostfinder.com',
            'type' => 'string',
            'label' => 'Support Email',
            'description' => 'Official customer care contact email.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'general.support_phone' => [
            'group' => 'general',
            'value' => '+918000000000',
            'type' => 'string',
            'label' => 'Support Helpline',
            'description' => '24/7 tourist helpline number.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'general.journey_ttl_hours' => [
            'group' => 'general',
            'value' => 72,
            'type' => 'integer',
            'label' => 'Journey TTL (Hours)',
            'description' => 'Duration in hours before an uncompleted journey automatically expires.',
            'is_public' => false,
            'is_encrypted' => false,
        ],
        'general.maintenance_mode' => [
            'group' => 'general',
            'value' => false,
            'type' => 'boolean',
            'label' => 'Maintenance Mode',
            'description' => 'Temporarily suspend non-essential public endpoints for planned maintenance.',
            'is_public' => true,
            'is_encrypted' => false,
        ],

        // Managed Feature Toggles
        'features.enable_realtime_chat' => [
            'group' => 'features',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Realtime WebSockets Chat',
            'description' => 'Enables in-app live chat between connected passengers and drivers via Reverb.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'features.enable_push_notifications' => [
            'group' => 'features',
            'value' => true,
            'type' => 'boolean',
            'label' => 'FCM Push Notifications',
            'description' => 'Enables background device push alerts for new messages and ticket updates.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'features.enable_driver_onboarding' => [
            'group' => 'features',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Driver Applications',
            'description' => 'Allows new drivers to register and submit KYC documents for verification.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'features.enable_guest_scan' => [
            'group' => 'features',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Public QR Scan Previews',
            'description' => 'Allows scanning vehicle stickers without requiring prior account login.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'features.enable_dispute_escalations' => [
            'group' => 'features',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Ticket Escalations',
            'description' => 'Permits passengers and drivers to escalate disputed claims to support staff.',
            'is_public' => false,
            'is_encrypted' => false,
        ],

        // Third-Party Integrations
        'third_party.fcm_enabled' => [
            'group' => 'third_party',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Firebase Cloud Messaging (FCM)',
            'description' => 'Whether FCM push service integration is active.',
            'is_public' => false,
            'is_encrypted' => false,
        ],
        'third_party.fcm_server_key' => [
            'group' => 'third_party',
            'value' => 'configured_in_env',
            'type' => 'encrypted',
            'label' => 'FCM Server / Service Key',
            'description' => 'Encrypted credentials for Firebase Cloud Messaging.',
            'is_public' => false,
            'is_encrypted' => true,
        ],
        'third_party.sms_provider' => [
            'group' => 'third_party',
            'value' => 'log',
            'type' => 'string',
            'label' => 'SMS Gateway Provider',
            'description' => 'Active SMS delivery service provider (log, twilio, msg91).',
            'is_public' => false,
            'is_encrypted' => false,
        ],
        'third_party.maps_enabled' => [
            'group' => 'third_party',
            'value' => true,
            'type' => 'boolean',
            'label' => 'Google Maps Platform',
            'description' => 'Enables vehicle geolocation resolution and map previews.',
            'is_public' => true,
            'is_encrypted' => false,
        ],
        'third_party.storage_driver' => [
            'group' => 'third_party',
            'value' => 'local',
            'type' => 'string',
            'label' => 'Storage Driver',
            'description' => 'Primary media storage disk (local, s3, r2).',
            'is_public' => false,
            'is_encrypted' => false,
        ],

        // Security & Rate Limiting Settings
        'security.otp_expiry_minutes' => [
            'group' => 'security',
            'value' => 5,
            'type' => 'integer',
            'label' => 'OTP TTL (Minutes)',
            'description' => 'Validity window for phone login verification codes.',
            'is_public' => false,
            'is_encrypted' => false,
        ],
        'security.otp_max_attempts' => [
            'group' => 'security',
            'value' => 5,
            'type' => 'integer',
            'label' => 'Max OTP Attempts',
            'description' => 'Maximum allowed incorrect OTP attempts before lockout.',
            'is_public' => false,
            'is_encrypted' => false,
        ],
        'security.qr_rate_limit_per_minute' => [
            'group' => 'security',
            'value' => 30,
            'type' => 'integer',
            'label' => 'QR Scan Rate Limit',
            'description' => 'Maximum public scan resolution requests per minute per IP.',
            'is_public' => false,
            'is_encrypted' => false,
        ],
    ];

    /**
     * Ensure database has default settings seeded.
     */
    public function seedDefaultsIfNeeded(): void
    {
        foreach ($this->defaultSettings as $key => $meta) {
            $existing = SystemSetting::where('key', $key)->first();
            if (!$existing) {
                $setting = new SystemSetting([
                    'group' => $meta['group'],
                    'key' => $key,
                    'type' => $meta['type'],
                    'label' => $meta['label'],
                    'description' => $meta['description'] ?? null,
                    'is_public' => $meta['is_public'],
                    'is_encrypted' => $meta['is_encrypted'],
                ]);
                $setting->setFormattedValue($meta['value']);
                $setting->save();
            }
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAllCached();

        if (array_key_exists($key, $all)) {
            return $all[$key]['value'];
        }

        // Check fallback definition
        if (isset($this->defaultSettings[$key])) {
            return $this->defaultSettings[$key]['value'];
        }

        return $default;
    }

    /**
     * Set a setting value by key.
     */
    public function set(string $key, mixed $value): SystemSetting
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            if (!isset($this->defaultSettings[$key])) {
                throw new \DomainException("Unknown setting key '{$key}'.");
            }

            $meta = $this->defaultSettings[$key];
            $setting = new SystemSetting([
                'group' => $meta['group'],
                'key' => $key,
                'type' => $meta['type'],
                'label' => $meta['label'],
                'description' => $meta['description'] ?? null,
                'is_public' => $meta['is_public'],
                'is_encrypted' => $meta['is_encrypted'],
            ]);
        }

        $setting->setFormattedValue($value);
        $setting->save();

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    /**
     * Retrieve public settings safe for client consumption.
     */
    public function getPublicSettings(): array
    {
        $this->seedDefaultsIfNeeded();

        $all = $this->getAllCached();
        $public = [];

        foreach ($all as $key => $item) {
            if ($item['is_public']) {
                $public[$key] = $item['value'];
            }
        }

        return $public;
    }

    /**
     * Retrieve all settings grouped for administration.
     */
    public function getAllSettings(): array
    {
        $this->seedDefaultsIfNeeded();

        $all = $this->getAllCached();
        $grouped = [];

        foreach ($all as $key => $item) {
            $group = $item['group'];
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            // Mask encrypted values for security in inspection views
            $displayValue = $item['is_encrypted'] ? '••••••••' : $item['value'];

            $grouped[$group][] = [
                'key' => $key,
                'label' => $item['label'],
                'description' => $item['description'],
                'type' => $item['type'],
                'value' => $displayValue,
                'is_public' => $item['is_public'],
                'is_encrypted' => $item['is_encrypted'],
            ];
        }

        return $grouped;
    }

    /**
     * Batch update multiple settings by staff.
     */
    public function updateBatch(array $updates, User $staff): array
    {
        $this->seedDefaultsIfNeeded();

        return DB::transaction(function () use ($updates, $staff) {
            $changed = [];

            foreach ($updates as $key => $value) {
                $setting = SystemSetting::where('key', $key)->first();
                if (!$setting) {
                    continue;
                }

                $oldValue = $setting->is_encrypted ? '[Encrypted]' : $setting->getParsedValue();
                $setting->setFormattedValue($value);
                $setting->save();

                $newValue = $setting->is_encrypted ? '[Encrypted]' : $setting->getParsedValue();

                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                AuditLog::create([
                    'user_id' => $staff->id,
                    'action' => 'setting.updated',
                    'auditable_type' => SystemSetting::class,
                    'auditable_id' => $setting->id,
                    'old_values' => ['value' => $oldValue],
                    'new_values' => ['value' => $newValue],
                    'created_at' => now(),
                ]);
            }

            Cache::forget(self::CACHE_KEY);

            return $changed;
        });
    }

    /**
     * Get system permissions and role capability matrix.
     */
    public function getPermissionsMatrix(): array
    {
        $permissionService = app(PermissionService::class);

        $roles = AdminRole::all()->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'description' => $r->description,
        ]);

        $categorizedPermissions = [
            'Driver Management' => [
                'drivers.view' => 'View driver profiles and verification queues',
                'drivers.verify' => 'Approve driver applications',
                'drivers.reject' => 'Reject driver applications with reason',
                'drivers.suspend' => 'Suspend verified driver accounts',
            ],
            'Vehicle Management' => [
                'vehicles.view' => 'View vehicle profiles and RC documents',
                'vehicles.verify' => 'Approve vehicle registration permits',
                'vehicles.reject' => 'Reject vehicle registration permits',
                'vehicles.suspend' => 'Block or suspend vehicles from service',
            ],
            'QR Sticker System' => [
                'qr.view' => 'View vehicle QR codes and scan telemetry logs',
                'qr.generate' => 'Generate high-entropy QR tokens',
                'qr.activate' => 'Activate QR codes for verified vehicles',
                'qr.disable' => 'Temporarily deactivate QR codes',
                'qr.revoke' => 'Permanently revoke QR codes',
                'qr.download' => 'Export and download SVG/PNG print stickers',
            ],
            'KYC & Document Verification' => [
                'documents.view' => 'Stream authenticated private KYC documents',
                'documents.verify' => 'Approve submitted documents',
                'documents.reject' => 'Reject submitted documents with reason',
            ],
            'Lost Item Tickets' => [
                'lost_items.view' => 'View lost item incident tickets and status timelines',
                'lost_items.manage' => 'Mediate and update ticket details',
                'lost_items.resolve' => 'Administrative force-closure and dispute resolution',
            ],
            'Incident & Fraud Reports' => [
                'reports.view' => 'Inspect user reports and evidence photos',
                'reports.resolve' => 'Investigate, resolve, or dismiss incident reports',
            ],
            'Governance & Settings' => [
                'audit_logs.view' => 'Search and query forensic staff audit logs',
                'settings.view' => 'View platform configuration and feature flags',
                'settings.update' => 'Modify platform settings and feature toggles',
            ],
        ];

        return [
            'roles' => $roles,
            'categories' => $categorizedPermissions,
        ];
    }

    /**
     * Internal cached fetch of all settings.
     */
    protected function getAllCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = SystemSetting::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = [
                    'group' => $setting->group,
                    'key' => $setting->key,
                    'label' => $setting->label,
                    'description' => $setting->description,
                    'type' => $setting->type,
                    'value' => $setting->getParsedValue(),
                    'is_public' => (bool) $setting->is_public,
                    'is_encrypted' => (bool) $setting->is_encrypted,
                ];
            }

            return $result;
        });
    }
}
