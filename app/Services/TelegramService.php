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
     * Format pesan sudah disesuaikan dengan emoji dan formatting HTML
     * Mengirim data pH, Turbidity, dan Salinitas dalam satu pesan
     * 
     * @param float $ph Nilai pH air
     * @param float $turbidity Nilai kekeruhan (NTU)
     * @param float $salinity Nilai salinitas (ppt)
     * @param string $status Status kualitas air (Critical/Poor/Fair/Good/Excellent)
     * @param float $score Skor kualitas air (0-100)
     * @param string|null $chatId Chat ID tujuan (optional)
     * @return array Response dari Telegram API
     */
    public function sendAlert(
        float $ph, 
        float $turbidity, 
        float $salinity,
        string $status,
        float $score,
        ?string $chatId = null
    ): array {
        // Tentukan emoji berdasarkan status
        $statusEmoji = match($status) {
            'Critical' => '🚨',
            'Poor' => '⚠️',
            'Fair' => '📊',
            'Good' => '✅',
            'Excellent' => '🌟',
            default => '📋'
        };

        // Tentukan emoji untuk setiap parameter (merah jika di luar range normal)
        $phEmoji = ($ph < 6.5 || $ph > 8.5) ? '🔴' : '🟢';
        $turbidityEmoji = ($turbidity > 45) ? '🔴' : '🟢';
        $salinityEmoji = ($salinity < 10 || $salinity > 25) ? '🔴' : '🟢';

        // Format pesan dengan HTML
        $timestamp = $this->formatTimestamp();
        $message = "{$statusEmoji} <b>ALERT KUALITAS AIR TAMBAK</b> {$statusEmoji}\n\n";
        $message .= "📍 <b>Status:</b> {$status}\n";
        $message .= "📈 <b>Skor:</b> {$score}/100\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 <b>DATA SENSOR REALTIME</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "{$phEmoji} <b>pH Air:</b> {$ph}\n";
        $message .= "   └ Normal: 6.5 - 8.5\n\n";
        $message .= "{$turbidityEmoji} <b>Kekeruhan:</b> {$turbidity} NTU\n";
        $message .= "   └ Normal: < 45 NTU\n\n";
        $message .= "{$salinityEmoji} <b>Salinitas:</b> {$salinity} ppt\n";
        $message .= "   └ Normal: 10 - 25 ppt\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⏰ <b>Waktu:</b> {$timestamp}\n";
        $message .= "🔗 <b>Dashboard:</b> <a href=\"https://tambaq.temandev.com/dashboard\">Buka Dashboard</a>";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim notifikasi alert cepat (simplified version)
     * Untuk kasus urgent yang butuh respon cepat
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
        $statusText = $isAboveMax ? 'TERLALU TINGGI' : 'TERLALU RENDAH';
        $timestamp = $this->formatTimestamp();
        
        $message = "🚨 <b>PERINGATAN {$alertType}!</b>\n\n";
        $message .= "⚠️ Nilai {$alertType}: <b>{$currentValue}</b>\n";
        $message .= "📊 Status: <code>{$statusText}</code>\n";
        $message .= "✅ Range Normal: {$normalMin} - {$normalMax}\n\n";
        $message .= "⏰ {$timestamp}\n\n";
        $message .= "Segera periksa kondisi tambak!";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim notifikasi gabungan untuk pH, Turbidity, dan Salinitas
     * Satu pesan untuk semua parameter yang bermasalah
     * 
     * @param float $ph Nilai pH
     * @param float $turbidity Nilai kekeruhan (NTU)
     * @param float $salinity Nilai salinitas (ppt)
     * @param array $alerts Array berisi parameter yang bermasalah
     * @param string|null $chatId Chat ID tujuan
     * @return array Response dari Telegram API
     */
    public function sendCombinedAlert(
        float $ph,
        float $turbidity,
        float $salinity,
        array $alerts,
        ?string $chatId = null
    ): array {
        $timestamp = $this->formatTimestamp();
        $alertCount = count($alerts);
        
        $message = "🚨 <b>PERINGATAN KUALITAS AIR!</b> 🚨\n\n";
        $message .= "⚠️ Terdeteksi <b>{$alertCount} parameter</b> di luar batas normal\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 <b>DATA SENSOR</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // pH
        $phEmoji = isset($alerts['ph']) ? '🔴' : '🟢';
        $phStatus = isset($alerts['ph']) ? " ⚠️ <code>{$alerts['ph']['status']}</code>" : '';
        $message .= "{$phEmoji} <b>pH Air:</b> {$ph}{$phStatus}\n";
        $message .= "   └ Normal: 6.5 - 8.5\n\n";
        
        // Turbidity
        $turbEmoji = isset($alerts['turbidity']) ? '🔴' : '🟢';
        $turbStatus = isset($alerts['turbidity']) ? " ⚠️ <code>{$alerts['turbidity']['status']}</code>" : '';
        $message .= "{$turbEmoji} <b>Kekeruhan:</b> {$turbidity} NTU{$turbStatus}\n";
        $message .= "   └ Normal: 20 - 45 NTU\n\n";
        
        // Salinitas
        $salEmoji = isset($alerts['salinity']) ? '🔴' : '🟢';
        $salStatus = isset($alerts['salinity']) ? " ⚠️ <code>{$alerts['salinity']['status']}</code>" : '';
        $message .= "{$salEmoji} <b>Salinitas:</b> {$salinity} ppt{$salStatus}\n";
        $message .= "   └ Normal: 10 - 25 ppt\n\n";
        
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "⏰ <b>Waktu:</b> {$timestamp}\n\n";
        $message .= "🔔 Segera periksa kondisi tambak!";

        return $this->sendMessage($message, $chatId);
    }

    /**
     * Kirim laporan harian kualitas air
     * Menampilkan data pH, Turbidity, dan Salinitas
     * 
     * @param array $dailyStats Statistik harian (avg, min, max, count)
     * @param string|null $chatId Chat ID tujuan
     * @return array Response dari Telegram API
     */
    public function sendDailyReport(array $dailyStats, ?string $chatId = null): array
    {
        $date = now()->format('d M Y');
        
        $message = "📊 <b>LAPORAN HARIAN TAMBAK</b>\n";
        $message .= "📅 {$date}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📈 <b>RINGKASAN HARI INI</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🔹 <b>pH Air</b>\n";
        $message .= "   Rata-rata: {$dailyStats['ph_avg']}\n";
        $message .= "   Min: {$dailyStats['ph_min']} | Max: {$dailyStats['ph_max']}\n\n";
        $message .= "🔹 <b>Kekeruhan</b>\n";
        $message .= "   Rata-rata: {$dailyStats['turbidity_avg']} NTU\n";
        $message .= "   Min: {$dailyStats['turbidity_min']} | Max: {$dailyStats['turbidity_max']}\n\n";
        $message .= "🔹 <b>Salinitas</b>\n";
        $message .= "   Rata-rata: {$dailyStats['salinity_avg']} ppt\n";
        $message .= "   Min: {$dailyStats['salinity_min']} | Max: {$dailyStats['salinity_max']}\n\n";
        $message .= "🔹 <b>Skor Kualitas</b>\n";
        $message .= "   Rata-rata: {$dailyStats['score_avg']}/100\n";
        $message .= "   Min: {$dailyStats['score_min']} | Max: {$dailyStats['score_max']}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📊 Total Pengukuran: {$dailyStats['total_readings']}x\n";
        $message .= "🔗 Detail: <a href=\"https://tambaq.com/history\">Lihat Riwayat</a>";

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
