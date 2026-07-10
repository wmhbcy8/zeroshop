<?php
declare(strict_types=1);

$baseUrl = rtrim((string)($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$token = login($baseUrl);
$failed = false;
$rows = [];
$originalSettings = null;
$customerId = 0;
$originalPlan = null;
$createdPlanId = 0;

try {
    $me = api_request($baseUrl, 'GET', '/api/auth/me', $token);
    $userInfo = is_array($me['user'] ?? null) ? $me['user'] : $me;
    $role = (string)($userInfo['role'] ?? '');
    $rows[] = [
        'name' => 'platform admin login',
        'ok' => in_array($role, ['admin', 'platform_admin', 'super_admin'], true),
        'message' => 'role=' . ($role ?: '-'),
    ];

    $overview = api_request($baseUrl, 'GET', '/api/platform/overview', $token);
    $rows[] = [
        'name' => 'platform overview response',
        'ok' => array_key_exists('customers', $overview) && array_key_exists('sites', $overview),
        'message' => 'customers=' . (string)($overview['customers'] ?? '-') . ', sites=' . (string)($overview['sites'] ?? '-'),
    ];

    $originalSettings = api_request($baseUrl, 'GET', '/api/platform/system-settings', $token);
    $settingsTest = $originalSettings;
    $marker = 'verify-platform-' . date('YmdHis');
    $settingsTest['platform']['support_phone'] = $marker;
    $savedSettings = api_request($baseUrl, 'PUT', '/api/platform/system-settings', $token, $settingsTest);
    $rows[] = [
        'name' => 'platform settings save',
        'ok' => (string)($savedSettings['platform']['support_phone'] ?? '') === $marker,
        'message' => $marker,
    ];

    $plans = api_request($baseUrl, 'GET', '/api/platform/plans', $token);
    $planItems = is_array($plans['items'] ?? null) ? $plans['items'] : [];
    $planKeys = array_map(static fn($item): string => (string)($item['plan_key'] ?? ''), $planItems);
    $starterPlan = find_plan($planItems, 'starter');
    $rows[] = [
        'name' => 'platform plan list',
        'ok' => in_array('starter', $planKeys, true) && in_array('growth', $planKeys, true) && in_array('enterprise', $planKeys, true),
        'message' => 'plans=' . implode(',', array_filter($planKeys)),
    ];
    $rows[] = [
        'name' => 'platform plan usage stats',
        'ok' => $starterPlan !== null && array_key_exists('customer_count', $starterPlan) && array_key_exists('site_count', $starterPlan),
        'message' => 'starter_customers=' . (string)($starterPlan['customer_count'] ?? '-'),
    ];

    $planKey = 'verify_plan_' . strtolower(substr(bin2hex(random_bytes(3)), 0, 6));
    $createdPlan = api_request($baseUrl, 'POST', '/api/platform/plans', $token, [
        'plan_key' => $planKey,
        'name' => 'Verify Plan',
        'description' => 'temporary verifier plan',
        'max_sites' => 3,
        'ai_quota' => 33,
        'storage_quota_mb' => 333,
        'monthly_price' => 9.9,
        'currency' => 'CNY',
        'sort_order' => 999,
        'status' => 'active',
    ]);
    $createdPlanId = (int)($createdPlan['id'] ?? 0);
    $rows[] = [
        'name' => 'platform plan create',
        'ok' => $createdPlanId > 0 && (string)($createdPlan['plan_key'] ?? '') === $planKey,
        'message' => $planKey,
    ];

    $updatedPlan = api_request($baseUrl, 'PUT', '/api/platform/plans/' . $createdPlanId, $token, array_replace($createdPlan, [
        'name' => 'Verify Plan Updated',
        'max_sites' => 5,
        'status' => 'disabled',
    ]));
    $rows[] = [
        'name' => 'platform plan update',
        'ok' => (int)($updatedPlan['max_sites'] ?? 0) === 5 && (string)($updatedPlan['status'] ?? '') === 'disabled',
        'message' => (string)($updatedPlan['name'] ?? ''),
    ];

    api_request($baseUrl, 'DELETE', '/api/platform/plans/' . $createdPlanId, $token);
    $createdPlanId = 0;
    $afterDeletePlans = api_request($baseUrl, 'GET', '/api/platform/plans', $token);
    $afterDeleteKeys = array_map(static fn($item): string => (string)($item['plan_key'] ?? ''), $afterDeletePlans['items'] ?? []);
    $rows[] = [
        'name' => 'platform plan delete',
        'ok' => !in_array($planKey, $afterDeleteKeys, true),
        'message' => $planKey,
    ];

    $customers = api_request($baseUrl, 'GET', '/api/platform/customers?page_size=20', $token);
    $customerItems = is_array($customers['items'] ?? null) ? $customers['items'] : [];
    $customerId = (int)($customerItems[0]['id'] ?? 0);
    $rows[] = [
        'name' => 'platform customer list',
        'ok' => $customerId > 0,
        'message' => 'first_customer=' . ($customerId ?: '-'),
    ];
    if ($customerId <= 0) {
        throw new RuntimeException('No platform customer found for quota verification.');
    }

    $quota = api_request($baseUrl, 'GET', '/api/platform/customers/' . $customerId . '/quota', $token);
    $originalPlan = customer_plan_from_quota($quota);
    $beforeAiQuota = (int)($originalPlan['ai_quota'] ?? 0);
    $adjusted = api_request($baseUrl, 'POST', '/api/platform/customers/' . $customerId . '/plan-adjust', $token, [
        'action' => 'add_ai_quota',
        'units' => 3,
        'note' => 'verification add quota',
    ]);
    $afterAiQuota = (int)($adjusted['usage']['ai_quota'] ?? 0);
    $latestLogAction = (string)($adjusted['logs'][0]['action'] ?? '');
    $rows[] = [
        'name' => 'customer quota adjust',
        'ok' => $afterAiQuota === $beforeAiQuota + 3 && $latestLogAction === 'add_ai_quota',
        'message' => 'ai_quota=' . $beforeAiQuota . '->' . $afterAiQuota,
    ];

    $restored = api_request($baseUrl, 'POST', '/api/platform/customers/' . $customerId . '/plan-adjust', $token, $originalPlan + [
        'action' => 'update_plan',
        'note' => 'verification restore quota',
    ]);
    $restoredPlan = customer_plan_from_quota($restored);
    $rows[] = [
        'name' => 'customer quota restore',
        'ok' => same_plan($originalPlan, $restoredPlan),
        'message' => 'plan=' . (string)($restoredPlan['plan_key'] ?? '-'),
    ];

    $sites = api_request($baseUrl, 'GET', '/api/platform/sites', $token);
    $rows[] = [
        'name' => 'platform sites response',
        'ok' => is_array($sites['items'] ?? null) && count($sites['items']) > 0,
        'message' => 'sites=' . count($sites['items'] ?? []),
    ];

    $templates = api_request($baseUrl, 'GET', '/api/platform/templates', $token);
    $rows[] = [
        'name' => 'platform templates response',
        'ok' => is_array($templates['items'] ?? null) && count($templates['items']) > 0,
        'message' => 'templates=' . count($templates['items'] ?? []),
    ];
} catch (Throwable $error) {
    $rows[] = [
        'name' => 'platform admin verifier',
        'ok' => false,
        'message' => $error->getMessage(),
    ];
    $failed = true;
} finally {
    if ($createdPlanId > 0) {
        try {
            api_request($baseUrl, 'DELETE', '/api/platform/plans/' . $createdPlanId, $token);
        } catch (Throwable) {
        }
    }
    if (is_array($originalPlan) && $customerId > 0) {
        try {
            api_request($baseUrl, 'POST', '/api/platform/customers/' . $customerId . '/plan-adjust', $token, $originalPlan + [
                'action' => 'update_plan',
                'note' => 'verification final restore',
            ]);
        } catch (Throwable $restoreError) {
            $rows[] = [
                'name' => 'customer quota final restore',
                'ok' => false,
                'message' => $restoreError->getMessage(),
            ];
            $failed = true;
        }
    }
    if (is_array($originalSettings)) {
        try {
            api_request($baseUrl, 'PUT', '/api/platform/system-settings', $token, $originalSettings);
        } catch (Throwable $restoreError) {
            $rows[] = [
                'name' => 'platform settings restore',
                'ok' => false,
                'message' => $restoreError->getMessage(),
            ];
            $failed = true;
        }
    }
}

foreach ($rows as $row) {
    $failed = $failed || empty($row['ok']);
    echo sprintf(
        "%s\t%s\t%s\n",
        !empty($row['ok']) ? 'PASS' : 'FAIL',
        $row['name'],
        (string)($row['message'] ?? '')
    );
}

exit($failed ? 1 : 0);

function customer_plan_from_quota(array $quota): array
{
    $usage = is_array($quota['usage'] ?? null) ? $quota['usage'] : [];
    return [
        'plan_key' => (string)($usage['plan_key'] ?? 'starter'),
        'max_sites' => max(1, (int)($usage['sites_limit'] ?? 1)),
        'ai_quota' => max(0, (int)($usage['ai_quota'] ?? 0)),
        'storage_quota_mb' => max(0, (int)($usage['storage_quota_mb'] ?? 0)),
        'expires_at' => trim((string)($usage['expires_at'] ?? '')) ?: null,
        'status' => in_array(($usage['status'] ?? 'active'), ['active', 'disabled', 'expired'], true) ? (string)$usage['status'] : 'active',
    ];
}

function same_plan(array $expected, array $actual): bool
{
    foreach (['plan_key', 'max_sites', 'ai_quota', 'storage_quota_mb', 'expires_at', 'status'] as $key) {
        if (($expected[$key] ?? null) != ($actual[$key] ?? null)) {
            return false;
        }
    }
    return true;
}

function find_plan(array $items, string $planKey): ?array
{
    foreach ($items as $item) {
        if ((string)($item['plan_key'] ?? '') === $planKey) {
            return is_array($item) ? $item : null;
        }
    }
    return null;
}

function login(string $baseUrl): string
{
    $data = api_request($baseUrl, 'POST', '/api/auth/login', '', [
        'username' => 'admin',
        'password' => 'admin123456',
    ]);
    $token = (string)($data['token'] ?? '');
    if ($token === '') {
        throw new RuntimeException('Login did not return token.');
    }
    return $token;
}

function api_request(string $baseUrl, string $method, string $path, string $token = '', ?array $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP curl extension is required.');
    }
    $ch = curl_init($baseUrl . $path);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Site-Id: 10001',
    ];
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 120,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('Empty API response: ' . ($error ?: 'HTTP ' . $status));
    }
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON API response: ' . mb_substr($raw, 0, 200, 'UTF-8'));
    }
    if ($status >= 400 || empty($payload['success'])) {
        throw new RuntimeException((string)($payload['message'] ?? ('HTTP ' . $status)));
    }
    return is_array($payload['data'] ?? null) ? $payload['data'] : [];
}
