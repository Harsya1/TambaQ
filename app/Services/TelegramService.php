<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * TelegramService
 * 
 * Service class untuk menangani semua operasi Telegram Bot API.
 * Termasuk pengiriman pesan, notifikasi alert, dan manajemen webhook.
 * 
 * @author TambaQ Team
 * @version 1.0.0
 */
class TelegramService
{
    /**
     * Base URL untuk Telegram Bot API
     */
    protected string $baseUrl;
    
    /**
     * Token Bot Telegram
     */
    protected string $botToken;
    
    /**
     * Default Chat ID untuk notifikasi
     */
    protected string $defaultChatId;
    
    /**
     * Secret untuk validasi webhook
     */
    protected ?string $webhookSecret;

    /**
     * Constructor - Inisialisasi konfigurasi Telegram
     */
    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? '';
        $this->defaultChatId = config('services.telegram.chat_id') ?? '';
        $this->webhookSecret = config('services.telegram.webhook_secret');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Kirim pesan teks ke chat/grup tertentu
     * 
     * @param string $message Pesan yang akan dikirim (support HTML formatting)
     * @param string|null $chatId Chat ID tujuan (optional, default dari .env)
     * @param array $options Opsi tambahan (parse_mode, reply_markup, dll)
     * @return array Response dari Telegram API
     */
    public function sendMessage(string $message, ?string $chatId = null, array $options = []): array
    {
        $chatId = $chatId ?? $this->defaultChatId;
        
        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options);

            $response = Http::timeout(10)->post("{$this->baseUrl}/sendMessage", $payload);

            if ($response->successful()) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $chatId,
                    'message_preview' => substr($message, 0, 100)
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Failed to send Telegram message', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'error' => $response->json()['description'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Telegram API Exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Kirim notifikasi alert bahaya ke grup teknisi
     * Format pesan profesional tanpa emoji untuk enterprise-grade appearance
     * 
     * @param float $ph Nilai pH air
     * @param float $tds Nilai TDS (ppm)
     * @param float $turbidity Nilai kekeruhan (NTU)
     * @param string $status Status kualitas air (Critical/Poor/Fair/Good/Excellent)
     * @param float $score Skor kualitas air (0-100)
     * @param string|null $chatId Chat ID tujuan (optional)
     * @return array Response dari Telegram API
     */
    public function sendAlert(
        float $ph, 
        float $tds,
        float $turbidity, 
        string $status,
        float $score,
        ?string $chatId = null
    ): array {
        $timestamp = $this->formatTimestamp();
        $reportId = strtoupper(substr(md5($timestamp . $score), 0, 8));
        
        // Determine parameter status flags
        $phFlag = ($ph < 6.5 || $ph > 8.5) ? '[ABNORMAL]' : '[OK]';
        $tdsFlag = ($tds < 300 || $tds > 800) ? '[ABNORMAL]' : '[OK]';
        $turbidityFlag = ($turbidity > 45) ? '[ABNORMAL]' : '[OK]';

        // Professional format message
        $message = "<b>[SYSTEM ALERT - TAMBAQ WATER QUALITY]</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>REPORT ID:</b> #{$reportId}\n";
        $message .= "<b>TIMESTAMP:</b> {$timestamp}\n";
        $message .= "<b>STATUS:</b> {$status}\n";
        $message .= "<b>SCORE:</b> {$score}/100\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>SENSOR READINGS</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>pH Level:</b> {$ph} {$phFlag}\n";
        $message .= "  - Normal Range: 6.5 - 8.5\n\n";
        $message .= "<b>TDS:</b> {$tds} ppm {$tdsFlag}\n";
        $message .= "  - Normal Range: 300 - 800 ppm\n\n";
        $message .= "<b>Turbidity:</b> {$turbidity} NTU {$turbidityFlag}\n";
        $message .= "  - Normal Range: 20 - 45 NTU\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>ACTION REQUIRED:</b> Please check pond conditions immediately.\n";
        $message .= "<b>DASHBOARD:</b> https://tambaq.temandev.com/dashboard\n";
        $message .= "----------------------------------------";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim notifikasi alert cepat (simplified version)
     * Format profesional untuk enterprise-grade appearance
     * 
     * @param string $alertType Tipe alert (pH, TDS, Turbidity, dll)
     * @param float $currentValue Nilai saat ini
     * @param float $normalMin Batas minimum normal
     * @param float $normalMax Batas maksimum normal
     * @param string|null $chatId Chat ID tujuan
     * @return array Response dari Telegram API
     */
    public function sendQuickAlert(
        string $alertType,
        float $currentValue,
        float $normalMin,
        float $normalMax,
        ?string $chatId = null
    ): array {
        $isAboveMax = $currentValue > $normalMax;
        $statusText = $isAboveMax ? 'ABOVE THRESHOLD' : 'BELOW THRESHOLD';
        $timestamp = $this->formatTimestamp();
        $reportId = strtoupper(substr(md5($timestamp . $currentValue), 0, 8));
        
        $message = "<b>[QUICK ALERT - TAMBAQ MONITORING]</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>REPORT ID:</b> #{$reportId}\n";
        $message .= "<b>TIMESTAMP:</b> {$timestamp}\n";
        $message .= "<b>PARAMETER:</b> {$alertType}\n";
        $message .= "<b>STATUS:</b> {$statusText}\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>READING DETAILS</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>Current Value:</b> {$currentValue}\n";
        $message .= "<b>Normal Range:</b> {$normalMin} - {$normalMax}\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>ACTION REQUIRED:</b> Immediate inspection recommended.\n";
        $message .= "----------------------------------------";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim notifikasi gabungan untuk pH, TDS, dan Turbidity
     * Format profesional tanpa emoji untuk enterprise-grade appearance
     * 
     * @param float $ph Nilai pH
     * @param float $tds Nilai TDS (ppm)
     * @param float $turbidity Nilai kekeruhan (NTU)
     * @param array $alerts Array berisi parameter yang bermasalah
     * @param string|null $chatId Chat ID tujuan
     * @return array Response dari Telegram API
     */
    public function sendCombinedAlert(
        float $ph,
        float $tds,
        float $turbidity,
        array $alerts,
        ?string $chatId = null
    ): array {
        $timestamp = $this->formatTimestamp();
        $alertCount = count($alerts);
        $reportId = strtoupper(substr(md5($timestamp . $alertCount), 0, 8));
        
        // Determine parameter status flags
        $phFlag = isset($alerts['ph']) ? '[ABNORMAL]' : '[OK]';
        $tdsFlag = isset($alerts['tds']) ? '[ABNORMAL]' : '[OK]';
        $turbFlag = isset($alerts['turbidity']) ? '[ABNORMAL]' : '[OK]';
        
        $message = "<b>[COMBINED ALERT - TAMBAQ WATER QUALITY]</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>REPORT ID:</b> #{$reportId}\n";
        $message .= "<b>TIMESTAMP:</b> {$timestamp}\n";
        $message .= "<b>ABNORMAL PARAMETERS:</b> {$alertCount}\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>SENSOR READINGS</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>pH Level:</b> {$ph} {$phFlag}\n";
        $message .= "  - Normal Range: 6.5 - 8.5\n";
        if (isset($alerts['ph'])) {
            $message .= "  - Status: {$alerts['ph']['status']}\n";
        }
        $message .= "\n";
        $message .= "<b>TDS:</b> {$tds} ppm {$tdsFlag}\n";
        $message .= "  - Normal Range: 300 - 800 ppm\n";
        if (isset($alerts['tds'])) {
            $message .= "  - Status: {$alerts['tds']['status']}\n";
        }
        $message .= "\n";
        $message .= "<b>Turbidity:</b> {$turbidity} NTU {$turbFlag}\n";
        $message .= "  - Normal Range: 20 - 45 NTU\n";
        if (isset($alerts['turbidity'])) {
            $message .= "  - Status: {$alerts['turbidity']['status']}\n";
        }
        $message .= "\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>ACTION REQUIRED:</b> Immediate inspection recommended.\n";
        $message .= "----------------------------------------";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim laporan harian kualitas air
     * Format profesional tanpa emoji untuk enterprise-grade appearance
     * 
     * @param array $dailyStats Statistik harian (avg, min, max, count)
     * @param string|null $chatId Chat ID tujuan
     * @return array Response dari Telegram API
     */
    public function sendDailyReport(array $dailyStats, ?string $chatId = null): array
    {
        $date = now()->format('d M Y');
        $reportId = strtoupper(substr(md5($date), 0, 8));
        
        $message = "<b>[DAILY REPORT - TAMBAQ WATER QUALITY]</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>REPORT ID:</b> #{$reportId}\n";
        $message .= "<b>DATE:</b> {$date}\n";
        $message .= "<b>TOTAL READINGS:</b> {$dailyStats['total_readings']}\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>DAILY SUMMARY</b>\n";
        $message .= "----------------------------------------\n\n";
        $message .= "<b>pH Level</b>\n";
        $message .= "  - Average: {$dailyStats['ph_avg']}\n";
        $message .= "  - Min: {$dailyStats['ph_min']} | Max: {$dailyStats['ph_max']}\n\n";
        $message .= "<b>TDS</b>\n";
        $message .= "  - Average: {$dailyStats['tds_avg']} ppm\n";
        $message .= "  - Min: {$dailyStats['tds_min']} | Max: {$dailyStats['tds_max']}\n\n";
        $message .= "<b>Turbidity</b>\n";
        $message .= "  - Average: {$dailyStats['turbidity_avg']} NTU\n";
        $message .= "  - Min: {$dailyStats['turbidity_min']} | Max: {$dailyStats['turbidity_max']}\n\n";
        $message .= "<b>Water Quality Score</b>\n";
        $message .= "  - Average: {$dailyStats['score_avg']}/100\n";
        $message .= "  - Min: {$dailyStats['score_min']} | Max: {$dailyStats['score_max']}\n\n";
        $message .= "----------------------------------------\n";
        $message .= "<b>DASHBOARD:</b> https://tambaq.temandev.com/history\n";
        $message .= "----------------------------------------";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Set webhook URL untuk menerima update dari Telegram
     * 
     * @param string $webhookUrl URL endpoint webhook
     * @return array Response dari Telegram API
     */
    public function setWebhook(string $webhookUrl): array
    {
        try {
            $payload = [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
            ];

            // Tambahkan secret token jika ada
            if ($this->webhookSecret) {
                $payload['secret_token'] = $this->webhookSecret;
            }

            $response = Http::timeout(10)->post("{$this->baseUrl}/setWebhook", $payload);

            if ($response->successful()) {
                Log::info('Telegram webhook set successfully', ['url' => $webhookUrl]);
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['description'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Hapus webhook yang sudah di-set
     * 
     * @return array Response dari Telegram API
     */
    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/deleteWebhook", [
                'drop_pending_updates' => true
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Dapatkan info webhook yang sedang aktif
     * 
     * @return array Response dari Telegram API
     */
    public function getWebhookInfo(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getWebhookInfo");

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validasi secret token dari webhook request
     * 
     * @param string|null $secretToken Token dari header request
     * @return bool
     */
    public function validateWebhookSecret(?string $secretToken): bool
    {
        if (empty($this->webhookSecret)) {
            return true; // Jika tidak ada secret, skip validasi
        }

        return $secretToken === $this->webhookSecret;
    }

    /**
     * Cek apakah bot sudah dikonfigurasi dengan benar
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->botToken) && 
               $this->botToken !== 'your_telegram_bot_token_here';
    }

    /**
     * Dapatkan info bot
     * 
     * @return array Response dari Telegram API
     */
    public function getMe(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/getMe");

            return [
                'success' => $response->successful(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format timestamp untuk pesan
     * 
     * @return string Formatted timestamp
     */
    protected function formatTimestamp(): string
    {
        return now()->setTimezone('Asia/Jakarta')->format('d M Y H:i:s') . ' WIB';
    }

    /**
     * Rate limiter untuk mencegah spam
     * Maksimal 1 pesan per menit untuk alert yang sama
     * 
     * @param string $alertKey Unique key untuk jenis alert
     * @return bool True jika boleh kirim, false jika harus wait
     */
    public function canSendAlert(string $alertKey): bool
    {
        $cacheKey = "telegram_alert_{$alertKey}";
        
        if (Cache::has($cacheKey)) {
            return false;
        }

        Cache::put($cacheKey, true, now()->addMinutes(1));
        return true;
    }
}
