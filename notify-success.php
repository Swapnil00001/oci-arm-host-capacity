<?php
// Enhanced notification script with success alert and workflow stop reminder

require_once __DIR__ . '/vendor/autoload.php';

$apiKey = getenv('TELEGRAM_BOT_API_KEY');
$userId = getenv('TELEGRAM_USER_ID');
$instanceData = getenv('INSTANCE_DATA');

if (empty($apiKey) || empty($userId) || empty($instanceData)) {
    echo "Missing required data for notification\n";
    exit(0);
}

$data = json_decode($instanceData, true);

$message = "🎉 *SUCCESS! Oracle Instance Created!* 🎉\n\n";
$message .= "✅ Your Ampere A1 instance is now running!\n\n";
$message .= "📋 *Instance Details:*\n";
$message .= "• Name: `{$data['displayName']}`\n";
$message .= "• ID: `{$data['id']}`\n";
$message .= "• Shape: {$data['shape']}\n";
$message .= "• State: {$data['lifecycleState']}\n";
$message .= "• Region: {$data['region']}\n\n";
$message .= "⚠️ *IMPORTANT: Stop the GitHub Actions workflow!*\n\n";
$message .= "To prevent creating duplicate instances:\n";
$message .= "1. Go to your GitHub repository\n";
$message .= "2. Navigate to Actions tab\n";
$message .= "3. Click on 'Get Oracle Instance' workflow\n";
$message .= "4. Click '...' menu → Disable workflow\n\n";
$message .= "Or update OCI_MAX_INSTANCES secret to match your current count.\n\n";
$message .= "🔗 View in Oracle Console:\n";
$message .= "https://cloud.oracle.com/compute/instances";

// Send to Telegram
$telegramUrl = "https://api.telegram.org/bot{$apiKey}/sendMessage";
$postData = [
    'chat_id' => $userId,
    'text' => $message,
    'parse_mode' => 'Markdown',
    'disable_web_page_preview' => true
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $telegramUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Success notification sent to Telegram!\n";
} else {
    echo "⚠️ Failed to send Telegram notification. HTTP Code: {$httpCode}\n";
    echo "Response: {$result}\n";
}
