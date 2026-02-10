<?php
/**
 * Real-Time Weather Forecast (Philippines)
 * Uses Open-Meteo (no API key) to fetch current conditions around Metro Manila.
 * Read-only, automated. On failure returns a safe JSON with a clear message.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed',
        'data'    => null
    ]);
    exit;
}

// Metro Manila approx. coordinates
$latitude  = 14.60;
$longitude = 120.98;

$url = 'https://api.open-meteo.com/v1/forecast'
    . '?latitude=' . $latitude
    . '&longitude=' . $longitude
    . '&current=temperature_2m,wind_speed_10m,precipitation'
    . '&hourly=precipitation_probability'
    . '&timezone=Asia%2FManila';

$ctx = stream_context_create([
    'http' => [
        'timeout'       => 8,
        'ignore_errors' => true,
    ],
    'ssl'  => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ],
]);

$data = [
    'temperature_c'     => null,
    'wind_speed'        => null,
    'rain_probability'  => null,
    'thunderstorm_risk' => null,
];

try {
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new Exception('Fetch failed');
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new Exception('Invalid JSON');
    }

    // Current conditions
    if (!empty($json['current'])) {
        $current = $json['current'];
        if (isset($current['temperature_2m'])) {
            $data['temperature_c'] = (float)$current['temperature_2m'];
        }
        if (isset($current['wind_speed_10m'])) {
            $data['wind_speed'] = (float)$current['wind_speed_10m'];
        }
    }

    // Rain probability & thunderstorm risk from next few hours
    $rainProb = null;
    if (!empty($json['hourly']['precipitation_probability'])
        && !empty($json['hourly']['time'])
    ) {
        $now       = time();
        $maxProb   = 0;
        $times     = $json['hourly']['time'];
        $probs     = $json['hourly']['precipitation_probability'];

        // Look at the next 6 hours
        for ($i = 0; $i < count($times); $i++) {
            $t = strtotime($times[$i] ?? '');
            if ($t === false) {
                continue;
            }
            if ($t >= $now && $t <= $now + 6 * 3600) {
                $p = (int)($probs[$i] ?? 0);
                if ($p > $maxProb) {
                    $maxProb = $p;
                }
            }
        }

        if ($maxProb > 0) {
            $rainProb = $maxProb;
        }
    }

    $data['rain_probability'] = $rainProb;

    // Simple thunderstorm risk based on rain probability
    if ($rainProb === null) {
        $data['thunderstorm_risk'] = null;
    } elseif ($rainProb >= 70) {
        $data['thunderstorm_risk'] = 'High';
    } elseif ($rainProb >= 40) {
        $data['thunderstorm_risk'] = 'Moderate';
    } else {
        $data['thunderstorm_risk'] = 'Low';
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Weather data loaded',
        'data'    => $data,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Weather data temporarily unavailable',
        'data'    => null,
    ]);
}

