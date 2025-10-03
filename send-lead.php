<?php
/**
 * Landing Page Lead Handler
 * Отправляет заявки с B2B landing page в Telegram
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Telegram Bot Configuration
$botToken = '8078652054:AAFwIATYEuRe-x4v9bP9AHwgn69Cua3nk3o'; // Staging bot
$adminChatId = '111748497'; // Your admin user ID

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['name', 'email', 'company', 'segment', 'description'];
$missing = [];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: ' . implode(', ', $missing)
    ]);
    exit;
}

// Extract data
$name = htmlspecialchars($data['name']);
$email = htmlspecialchars($data['email']);
$company = htmlspecialchars($data['company']);
$phone = htmlspecialchars($data['phone'] ?? 'Не указан');
$segment = htmlspecialchars($data['segment']);
$description = htmlspecialchars($data['description']);

// Map segments to Russian
$segmentMap = [
    'publishing' => 'Издательство',
    'university' => 'ВУЗ / Научный институт',
    'author' => 'Независимый автор',
    'other' => 'Другое'
];
$segmentRu = $segmentMap[$segment] ?? $segment;

// Create Telegram message
$message = "🔔 <b>НОВАЯ ЗАЯВКА С B2B LANDING</b>\n\n";
$message .= "👤 <b>Имя:</b> {$name}\n";
$message .= "📧 <b>Email:</b> {$email}\n";
$message .= "🏢 <b>Компания:</b> {$company}\n";
$message .= "📱 <b>Телефон:</b> {$phone}\n";
$message .= "🎯 <b>Сегмент:</b> {$segmentRu}\n\n";
$message .= "📝 <b>Описание задачи:</b>\n{$description}\n\n";
$message .= "⏰ " . date('Y-m-d H:i:s') . " МСК";

// Send to Telegram
$telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
$postData = [
    'chat_id' => $adminChatId,
    'text' => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // Success
    echo json_encode([
        'success' => true,
        'message' => 'Заявка отправлена успешно'
    ]);
} else {
    // Telegram API error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send to Telegram',
        'details' => $response
    ]);
}
