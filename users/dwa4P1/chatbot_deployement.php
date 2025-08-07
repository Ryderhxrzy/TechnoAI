<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

/* =============================================
   ENVIRONMENT CONFIGURATION
   ============================================= */
function getApiKey() {
    // Priority 1: Render Environment Variables
    $apiKey = getenv('GEMINI_API_KEY');
    if (!empty($apiKey)) {
        return $apiKey;
    }

    // Priority 2: Local .env file (for development)
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $env = parse_ini_file($envFile);
        return $env['GEMINI_API_KEY'] ?? '';
    }

    return '';
}

$api_key = getApiKey();

if (empty($api_key)) {
    echo json_encode(['error' => 'API key not configured']);
    exit;
}

/* =============================================
   REQUEST VALIDATION
   ============================================= */
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['message'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$user_message = trim($input['message']);
if (empty($user_message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

/* =============================================
   GEMINI API REQUEST
   ============================================= */
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$api_key";

$data = [
    "contents" => [
        [
            "parts" => [
                ['text' => $user_message]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

/* =============================================
   RESPONSE HANDLING
   ============================================= */
if ($response === false) {
    error_log("cURL Error: " . $curl_error);
    echo json_encode(['error' => 'Service unavailable. Please try again later.']);
    exit;
}

if ($http_code !== 200) {
    error_log("API Error $http_code: " . $response);
    echo json_encode(['error' => 'API service error']);
    exit;
}

$response_data = json_decode($response, true);

if (!isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    error_log("Unexpected API Response: " . $response);
    echo json_encode(['error' => 'Unexpected response format']);
    exit;
}

$ai_response = trim($response_data['candidates'][0]['content']['parts'][0]['text']);
echo json_encode(['response' => $ai_response]);
?>