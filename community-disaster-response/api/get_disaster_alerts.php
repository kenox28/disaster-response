<?php
/**
 * Real-Time Disaster Alert System (API-Based)
 * Fetches from GDACS (Global Disaster Alert and Coordination System) - free, no key required.
 * Read-only, automated. On API failure returns empty list so system continues normally.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data' => []
    ]);
    exit;
}

$alerts = [];
$lastUpdated = date('Y-m-d H:i:s');

try {
    $url = 'https://www.gdacs.org/gdacsapi/api/events/geteventlist/SEARCH';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        sendSafeResponse($alerts, $lastUpdated);
        exit;
    }

    $json = json_decode($raw, true);
    if (!is_array($json) || empty($json['features']) || !is_array($json['features'])) {
        sendSafeResponse($alerts, $lastUpdated);
        exit;
    }

    $severityOrder = ['red' => 3, 'orange' => 2, 'green' => 1];
    $typeNames = [
        'EQ' => 'Earthquake',
        'TC' => 'Tropical Cyclone',
        'FL' => 'Flood',
        'DR' => 'Drought',
        'WF' => 'Wildfire',
        'VO' => 'Volcano',
        'OT' => 'Other'
    ];

    $now = time();
    foreach ($json['features'] as $feature) {
        if (!is_array($feature)) continue;
        $p = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : [];
        $alertLevel = strtolower(trim((string)($p['alertlevel'] ?? 'green')));
        $eventType = $p['eventtype'] ?? 'OT';
        $eventName = trim((string)($p['eventname'] ?? ''));
        if ($eventName === '') $eventName = $typeNames[$eventType] ?? 'Disaster Event';
        $countryRaw = trim((string)($p['country'] ?? ''));
        $fromDate = isset($p['fromdate']) ? (string)$p['fromdate'] : '';
        $description = trim((string)($p['description'] ?? ''));
        if ($description === '') $description = $eventName . ($countryRaw ? ' - ' . $countryRaw : '');

        // Basic \"active/near-term\" filter: ignore events too far in past or future
        $ts = strtotime($fromDate);
        if ($ts !== false) {
            // keep events within [-14 days, +3 days] from now
            if ($ts < $now - 14 * 86400 || $ts > $now + 3 * 86400) {
                continue;
            }
        }

        // Locations list (for display + PH filtering)
        $locations = [];
        if ($countryRaw !== '') {
            $parts = preg_split('/[;,\/]+/', $countryRaw);
            if (is_array($parts)) {
                foreach ($parts as $c) {
                    $c = trim($c);
                    if ($c !== '') $locations[] = $c;
                }
            }
        }
        if (empty($locations) && $countryRaw !== '') $locations = [$countryRaw];

        // Alert code: keep official-looking hyphenated codes when possible
        $alertCode = '';
        $candidate = strtoupper(trim($eventName));
        $candidate = preg_replace('/\s+/', '-', $candidate);
        $candidate = preg_replace('/[^A-Z0-9\-]/', '', $candidate);
        if ($candidate !== '') {
            $alertCode = $candidate;
        }

        $typeLabel = $typeNames[$eventType] ?? $eventType;
        $nameType  = trim($typeLabel . ' ' . ($alertCode !== '' ? $alertCode : $eventName));

        $alerts[] = [
            'code'           => $alertCode,
            'type'           => $typeLabel,
            'title'          => $eventName,
            'name_type'      => $nameType,
            'description'    => $description,
            'severity'       => ucfirst($alertLevel),
            'severity_order' => $severityOrder[$alertLevel] ?? 0,
            'effective_date' => $fromDate,
            'locations'      => $locations,
            'location_text'  => implode(', ', $locations),
            'location'       => $countryRaw
        ];
    }

    // Restrict to Philippines only (country code PH, PHL, or name contains Philippines)
    $alerts = array_filter($alerts, function ($a) {
        $locText = strtoupper(trim($a['location_text'] ?? ($a['location'] ?? '')));
        if ($locText === 'PH' || $locText === 'PHL') return true;
        if (stripos($locText, 'PHILIPPINES') !== false) return true;
        return false;
    });
    $alerts = array_values($alerts);

    // Sort by severity (highest first), then by date
    usort($alerts, function ($a, $b) {
        $c = ($b['severity_order'] ?? 0) - ($a['severity_order'] ?? 0);
        if ($c !== 0) return $c;
        return strcmp($b['effective_date'] ?? '', $a['effective_date'] ?? '');
    });

    // Limit to recent alerts for display
    $alerts = array_slice($alerts, 0, 20);

} catch (Exception $e) {
    $alerts = [];
}

sendSafeResponse($alerts, $lastUpdated);

function sendSafeResponse(array $alerts, $lastUpdated) {
    echo json_encode([
        'status' => 'success',
        'message' => count($alerts) > 0 ? 'Disaster alerts loaded' : 'No active disaster alerts in the Philippines',
        'data' => $alerts,
        'last_updated' => $lastUpdated
    ]);
}
