<?php

namespace App\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailNotification
{
    private $mailer;
    private $enabled;

    public function __construct()
    {
        $this->enabled = $_ENV['ENABLE_EMAIL_NOTIFICATIONS'] ?? true;

        // Initialize PHPMailer
        $this->mailer = new PHPMailer(true);
        
        try {
            // SMTP Configuration - Support multiple providers
            $this->mailer->isSMTP();
            
            $provider = $_ENV['MAIL_PROVIDER'] ?? 'mailtrap'; // Default to Mailtrap
            $this->configureByProvider($provider);
            
            $this->mailer->CharSet = 'UTF-8';

            // Default sender
            $this->mailer->setFrom(
                $_ENV['MAIL_FROM'] ?? 'noreply@siapkak.local',
                $_ENV['MAIL_FROM_NAME'] ?? 'SIAPKAK'
            );
        } catch (Exception $e) {
            error_log('PHPMailer initialization error: ' . $e->getMessage());
        }
    }

    /**
     * Configure SMTP based on email provider
     */
    private function configureByProvider($provider)
    {
        switch (strtolower($provider)) {
            case 'mailtrap':
                $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'send.api.mailtrap.io';
                $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 587;
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? 'api';
                $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                break;
                
            case 'gmail':
                $this->mailer->Host = 'smtp.gmail.com';
                $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 587;
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
                $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
                // Use SSL for port 465, STARTTLS for port 587
                $port = $_ENV['MAIL_PORT'] ?? 587;
                $this->mailer->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                break;
                
            case 'smtp':
            default:
                $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'localhost';
                $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 587;
                $this->mailer->SMTPAuth = !empty($_ENV['MAIL_USERNAME']);
                $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
                $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
                $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
                break;
        }
    }

    /**
     * Send notification email untuk high AQI
     */
    public function sendAQIAlert($userEmail, $userName, $stationName, $aqiIndex, $status)
    {
        if (!$this->enabled || !$userEmail) {
            return false;
        }

        try {
            $this->mailer->addAddress($userEmail, $userName);

            // Email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Alert: Kualitas Udara $stationName - AQI $aqiIndex";
            $this->mailer->Body = $this->getAQIAlertEmailTemplate($userName, $stationName, $aqiIndex, $status);
            $this->mailer->AltBody = "Kualitas udara di $stationName saat ini adalah $status (AQI: $aqiIndex)";

            // Send
            $result = $this->mailer->send();

            // Clear recipients untuk email berikutnya
            $this->mailer->clearAddresses();

            return $result;
        } catch (Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send general notification email
     */
    public function sendNotification($userEmail, $userName, $title, $message)
    {
        if (!$this->enabled || !$userEmail) {
            return false;
        }

        try {
            $this->mailer->addAddress($userEmail, $userName);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $title;
            $this->mailer->Body = $this->getNotificationEmailTemplate($userName, $title, $message);
            $this->mailer->AltBody = $message;

            $result = $this->mailer->send();
            $this->mailer->clearAddresses();

            return $result;
        } catch (Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email untuk new user
     */
    public function sendWelcomeEmail($userEmail, $userName)
    {
        if (!$this->enabled || !$userEmail) {
            return false;
        }

        try {
            $this->mailer->addAddress($userEmail, $userName);

            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Selamat Datang di SIAPKAK';
            $this->mailer->Body = $this->getWelcomeEmailTemplate($userName);
            $this->mailer->AltBody = 'Selamat datang di SIAPKAK - Sistem Monitoring Polusi Udara Kampus';

            $result = $this->mailer->send();
            $this->mailer->clearAddresses();

            return $result;
        } catch (Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get AQI Alert email template
     */
    private function getAQIAlertEmailTemplate($userName, $stationName, $aqiIndex, $status)
    {
        $aqiColor = $this->getAQIColor($status);
        $logoUrl = 'https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png';
        $dangerIcon = 'https://res.cloudinary.com/drgwsncdn/image/upload/v1764506715/danger_gq5izw.png';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost/siapkak';
        
        // Get recommendations based on AQI
        $recommendations = $this->getAQIRecommendations($aqiIndex);
        $healthImpact = $this->getHealthImpact($aqiIndex);
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        margin: 0; 
                        padding: 0; 
                        background-color: #f3f4f6;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: white;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
                        color: white; 
                        padding: 30px 20px;
                        text-align: center;
                    }
                    .logo {
                        width: 80px;
                        height: 80px;
                        margin-bottom: 15px;
                        background: white;
                        border-radius: 50%;
                        padding: 10px;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 28px;
                        font-weight: 700;
                    }
                    .header p {
                        margin: 5px 0 0 0;
                        opacity: 0.9;
                        font-size: 14px;
                    }
                    .content { 
                        padding: 30px 25px;
                    }
                    .greeting {
                        font-size: 16px;
                        color: #374151;
                        margin-bottom: 20px;
                    }
                    .alert-box { 
                        background: $aqiColor; 
                        color: white; 
                        padding: 25px; 
                        border-radius: 8px; 
                        margin: 25px 0;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                        position: relative;
                    }
                    .danger-icon {
                        width: 60px;
                        height: 60px;
                        margin: 0 auto 15px;
                        display: block;
                    }
                    .alert-box h2 {
                        margin: 0 0 15px 0;
                        font-size: 24px;
                    }
                    .alert-detail {
                        display: flex;
                        justify-content: space-between;
                        margin: 10px 0;
                        padding: 10px 0;
                        border-bottom: 1px solid rgba(255,255,255,0.2);
                    }
                    .alert-detail:last-child {
                        border-bottom: none;
                    }
                    .alert-detail strong {
                        font-weight: 600;
                    }
                    .info-section {
                        background: #f9fafb;
                        padding: 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        border-left: 4px solid #3b82f6;
                    }
                    .info-section h3 {
                        margin: 0 0 10px 0;
                        color: #1f2937;
                        font-size: 18px;
                    }
                    .info-section p {
                        margin: 5px 0;
                        color: #4b5563;
                        line-height: 1.6;
                    }
                    .recommendations {
                        background: #fef3c7;
                        padding: 20px;
                        border-radius: 8px;
                        margin: 20px 0;
                        border-left: 4px solid #f59e0b;
                    }
                    .recommendations h3 {
                        margin: 0 0 10px 0;
                        color: #92400e;
                        font-size: 18px;
                    }
                    .recommendations ul {
                        margin: 10px 0;
                        padding-left: 20px;
                        color: #78350f;
                    }
                    .recommendations li {
                        margin: 8px 0;
                        line-height: 1.5;
                    }
                    .cta-button {
                        display: inline-block;
                        background: white;
                        color: #2563eb;
                        padding: 14px 30px;
                        text-decoration: none;
                        border-radius: 6px;
                        font-weight: 600;
                        margin: 20px 0;
                        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
                        border: 2px solid #2563eb;
                    }
                    .cta-button:hover {
                        background: #f0f9ff;
                    }
                    .footer { 
                        font-size: 13px; 
                        color: #6b7280; 
                        text-align: center;
                        padding: 20px 25px;
                        background: #f9fafb;
                        border-top: 1px solid #e5e7eb;
                    }
                    .footer p {
                        margin: 5px 0;
                    }
                    .footer a {
                        color: #2563eb;
                        text-decoration: none;
                    }
                    @media only screen and (max-width: 600px) {
                        .container { margin: 0; border-radius: 0; }
                        .content { padding: 20px 15px; }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='$logoUrl' alt='SIAPKAK Logo' class='logo' />
                        <h1>SIAPKAK</h1>
                        <p>Sistem Informasi Air Pollution Kampus Area Karawang</p>
                    </div>
                    <div class='content'>
                        <p class='greeting'>Halo <strong>$userName</strong>,</p>
                        <p>Kami mendeteksi kualitas udara yang memerlukan perhatian Anda:</p>
                        
                        <div class='alert-box'>
                            <img src='$dangerIcon' alt='Danger' class='danger-icon' />
                            <h2>⚠️ Peringatan Kualitas Udara</h2>
                            <div class='alert-detail'>
                                <span><strong>Lokasi Stasiun:</strong></span>
                                <span>$stationName</span>
                            </div>
                            <div class='alert-detail'>
                                <span><strong>Status Kualitas Udara:</strong></span>
                                <span>$status</span>
                            </div>
                            <div class='alert-detail'>
                                <span><strong>Indeks AQI:</strong></span>
                                <span style='font-size: 24px; font-weight: bold;'>$aqiIndex</span>
                            </div>
                            <div class='alert-detail'>
                                <span><strong>Waktu Deteksi:</strong></span>
                                <span>" . date('d M Y, H:i') . " WIB</span>
                            </div>
                        </div>

                        <div class='info-section'>
                            <h3>📊 Dampak Kesehatan</h3>
                            <p>$healthImpact</p>
                        </div>

                        <div class='recommendations'>
                            <h3>💡 Rekomendasi Tindakan</h3>
                            <ul>
                                $recommendations
                            </ul>
                        </div>

                        <p style='text-align: center;'>
                            <a href='$appUrl/public/dashboard.php' class='cta-button'>
                                📱 Buka Dashboard SIAPKAK
                            </a>
                        </p>

                        <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>
                            Pantau terus kualitas udara di area Anda dan tetap jaga kesehatan!
                        </p>

                        <div class='footer'>
                            <p>Email ini dikirim secara otomatis oleh sistem SIAPKAK. Jangan membalas email ini.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Get notification email template
     */
    private function getNotificationEmailTemplate($userName, $title, $message)
    {
        $logoUrl = 'https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost/siapkak';
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        margin: 0; 
                        padding: 0; 
                        background-color: #f3f4f6;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: white;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
                        color: white; 
                        padding: 30px 20px;
                        text-align: center;
                    }
                    .logo {
                        width: 60px;
                        height: 60px;
                        margin-bottom: 10px;
                        background: white;
                        border-radius: 50%;
                        padding: 8px;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                        font-weight: 700;
                    }
                    .content { 
                        padding: 30px 25px;
                    }
                    .message-box {
                        background: #f0f9ff;
                        padding: 20px;
                        border-radius: 8px;
                        border-left: 4px solid #3b82f6;
                        margin: 20px 0;
                    }
                    .message-box h2 {
                        margin: 0 0 15px 0;
                        color: #1e40af;
                        font-size: 20px;
                    }
                    .message-box p {
                        margin: 0;
                        color: #1f2937;
                        line-height: 1.6;
                    }
                    .cta-button {
                        display: inline-block;
                        background: white;
                        color: #2563eb;
                        padding: 12px 25px;
                        text-decoration: none;
                        border-radius: 6px;
                        font-weight: 600;
                        margin: 20px 0;
                        border: 2px solid #2563eb;
                    }
                    .footer { 
                        font-size: 13px; 
                        color: #6b7280; 
                        text-align: center;
                        padding: 20px 25px;
                        background: #f9fafb;
                        border-top: 1px solid #e5e7eb;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='$logoUrl' alt='SIAPKAK Logo' class='logo' />
                        <h1>SIAPKAK Notification</h1>
                    </div>
                    <div class='content'>
                        <p>Halo <strong>$userName</strong>,</p>
                        
                        <div class='message-box'>
                            <h2>📢 $title</h2>
                            <p>$message</p>
                        </div>
                        
                        <div style='background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                            <h3 style='margin: 0 0 15px 0; color: #374151; font-size: 16px;'>ℹ️ Informasi Sistem</h3>
                            <table style='width: 100%; font-size: 14px; color: #6b7280;'>
                                <tr>
                                    <td style='padding: 8px 0;'><strong>Waktu Notifikasi:</strong></td>
                                    <td style='padding: 8px 0; text-align: right;'>" . date('d F Y, H:i') . " WIB</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0;'><strong>Sistem:</strong></td>
                                    <td style='padding: 8px 0; text-align: right;'>SIAPKAK Monitoring</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0;'><strong>Status:</strong></td>
                                    <td style='padding: 8px 0; text-align: right;'>✅ Aktif</td>
                                </tr>
                            </table>
                        </div>
                        
                        <p style='text-align: center;'>
                            <a href='$appUrl/public/dashboard.php' class='cta-button'>
                                📱 Buka Dashboard SIAPKAK
                            </a>
                        </p>

                        <div class='footer'>
                            <p>Email ini dikirim secara otomatis oleh sistem SIAPKAK. Jangan membalas email ini.</p>
                            <p style='margin-top: 10px;'>Sistem Informasi Air Pollution Kampus Area Karawang</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Get welcome email template
     */
    private function getWelcomeEmailTemplate($userName, $userEmail)
    {
        $logoUrl = 'https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost/siapkak';
        
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        margin: 0; 
                        padding: 0; 
                        background-color: #f3f4f6;
                    }
                    .container { 
                        max-width: 600px; 
                        margin: 20px auto; 
                        background: white;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .header { 
                        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); 
                        color: white; 
                        padding: 40px 20px;
                        text-align: center;
                    }
                    .logo {
                        width: 100px;
                        height: 100px;
                        margin-bottom: 15px;
                        background: white;
                        border-radius: 50%;
                        padding: 15px;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 32px;
                        font-weight: 700;
                    }
                    .header p {
                        margin: 10px 0 0 0;
                        opacity: 0.95;
                        font-size: 16px;
                    }
                    .content { 
                        padding: 35px 30px;
                    }
                    .welcome-message {
                        font-size: 18px;
                        color: #1f2937;
                        margin-bottom: 25px;
                        line-height: 1.6;
                    }
                    .features-title {
                        color: #1f2937;
                        font-size: 20px;
                        margin: 30px 0 15px 0;
                        font-weight: 600;
                    }
                    .features { 
                        list-style: none; 
                        padding: 0;
                        margin: 0;
                    }
                    .features li { 
                        padding: 15px;
                        margin: 10px 0;
                        background: #f9fafb;
                        border-radius: 8px;
                        border-left: 4px solid #3b82f6;
                        color: #374151;
                        display: flex;
                        align-items: center;
                    }
                    .features li::before {
                        content: '✓';
                        display: inline-block;
                        width: 24px;
                        height: 24px;
                        background: #3b82f6;
                        color: white;
                        border-radius: 50%;
                        text-align: center;
                        line-height: 24px;
                        margin-right: 12px;
                        font-weight: bold;
                        flex-shrink: 0;
                    }
                    .cta-section {
                        text-align: center;
                        margin: 35px 0;
                        padding: 25px;
                        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
                        border-radius: 8px;
                    }
                    .cta-button {
                        display: inline-block;
                        background: white;
                        color: #2563eb;
                        padding: 15px 35px;
                        text-decoration: none;
                        border-radius: 6px;
                        font-weight: 600;
                        font-size: 16px;
                        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
                        border: 2px solid #2563eb;
                    }
                    .footer { 
                        font-size: 13px; 
                        color: #6b7280; 
                        text-align: center;
                        padding: 20px 25px;
                        background: #f9fafb;
                        border-top: 1px solid #e5e7eb;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='$logoUrl' alt='SIAPKAK Logo' class='logo' />
                        <h1>🎉 Selamat Datang!</h1>
                        <p>Sistem Informasi Air Pollution Kampus Area Karawang</p>
                    </div>
                    <div class='content'>
                        <p class='welcome-message'>
                            Halo <strong>$userName</strong>,<br><br>
                            Terima kasih telah bergabung dengan <strong>SIAPKAK</strong>! Kami sangat senang Anda menjadi bagian dari komunitas yang peduli dengan kualitas udara.
                        </p>
                        
                        <h3 class='features-title'>🚀 Fitur yang Tersedia untuk Anda:</h3>
                        <ul class='features'>
                            <li>Monitor kualitas udara secara real-time dari berbagai stasiun</li>
                            <li>Notifikasi otomatis saat kualitas udara tidak sehat</li>
                            <li>Dashboard analytics dengan grafik dan visualisasi interaktif</li>
                            <li>Laporan lengkap kualitas udara dalam format CSV, Excel, dan PDF</li>
                            <li>Integrasi data dari AQICN API untuk akurasi maksimal</li>
                            <li>Peta interaktif dengan heatmap kualitas udara</li>
                        </ul>

                        <div class='cta-section'>
                            <p style='margin: 0 0 15px 0; color: #1f2937; font-weight: 600;'>
                                Mulai pantau kualitas udara sekarang!
                            </p>
                            <a href='$appUrl/public/dashboard.php' class='cta-button'>
                                🌍 Buka Dashboard
                            </a>
                        </div>

                        <p style='color: #6b7280; font-size: 14px; line-height: 1.6;'>
                            <strong>Tips:</strong> Aktifkan notifikasi push di halaman Settings untuk mendapatkan peringatan langsung saat kualitas udara memburuk.
                        </p>

                        <div class='footer'>
                            <p>Email ini dikirim secara otomatis oleh sistem SIAPKAK. Jangan membalas email ini.</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Get AQI color based on status
     */
    private function getAQIColor($status)
    {
        $colors = [
            'Baik' => '#10b981',
            'Sedang' => '#3b82f6',
            'Tidak Sehat (Sensitif)' => '#f59e0b',
            'Tidak Sehat' => '#ef4444',
            'Sangat Tidak Sehat' => '#9333ea',
            'Berbahaya' => '#7c2d12'
        ];

        return $colors[$status] ?? '#6b7280';
    }

    /**
     * Get health impact description based on AQI
     */
    private function getHealthImpact($aqiIndex)
    {
        if ($aqiIndex <= 50) {
            return 'Kualitas udara baik. Tidak ada dampak kesehatan yang signifikan.';
        } elseif ($aqiIndex <= 100) {
            return 'Kualitas udara dapat diterima. Kelompok sensitif mungkin mengalami dampak ringan.';
        } elseif ($aqiIndex <= 150) {
            return 'Kelompok sensitif (anak-anak, lansia, penderita asma) dapat mengalami iritasi pernapasan. Kurangi aktivitas outdoor yang berat.';
        } elseif ($aqiIndex <= 200) {
            return 'Setiap orang dapat mengalami dampak kesehatan. Kelompok sensitif dapat mengalami efek serius. Hindari aktivitas outdoor yang lama.';
        } elseif ($aqiIndex <= 300) {
            return 'Peringatan kesehatan! Setiap orang dapat mengalami dampak kesehatan yang lebih serius. Hindari semua aktivitas outdoor.';
        } else {
            return 'BAHAYA! Kondisi darurat kesehatan. Seluruh populasi berisiko tinggi. Tetap di dalam ruangan dan gunakan air purifier jika tersedia.';
        }
    }

    /**
     * Get recommendations based on AQI
     */
    private function getAQIRecommendations($aqiIndex)
    {
        if ($aqiIndex <= 50) {
            return '<li>Nikmati aktivitas outdoor Anda</li><li>Kondisi udara ideal untuk olahraga</li>';
        } elseif ($aqiIndex <= 100) {
            return '<li>Aktivitas outdoor masih aman untuk umumnya</li><li>Kelompok sensitif sebaiknya batasi aktivitas berat</li>';
        } elseif ($aqiIndex <= 150) {
            return '<li>Kurangi aktivitas outdoor yang berat dan lama</li><li>Kelompok sensitif sebaiknya tetap di dalam ruangan</li><li>Gunakan masker jika harus keluar</li>';
        } elseif ($aqiIndex <= 200) {
            return '<li>Hindari aktivitas outdoor yang berat</li><li>Tutup jendela dan pintu</li><li>Gunakan masker N95 jika harus keluar</li><li>Kelompok sensitif harus tetap di dalam ruangan</li>';
        } elseif ($aqiIndex <= 300) {
            return '<li>Hindari SEMUA aktivitas outdoor</li><li>Tetap di dalam ruangan dengan jendela tertutup</li><li>Gunakan air purifier jika tersedia</li><li>Gunakan masker N95/N99 jika terpaksa keluar</li><li>Konsultasi dengan dokter jika mengalami gejala pernapasan</li>';
        } else {
            return '<li>TETAP DI DALAM RUANGAN - Jangan keluar kecuali darurat</li><li>Tutup semua jendela dan pintu rapat-rapat</li><li>Gunakan air purifier dengan filter HEPA</li><li>Siapkan masker N99 dan gunakan jika terpaksa keluar</li><li>Segera hubungi layanan kesehatan jika mengalami kesulitan bernapas</li><li>Hindari penggunaan AC yang mengambil udara dari luar</li>';
        }
    }

    /**
     * Test email configuration
     */
    public function testConnection()
    {
        try {
            $this->mailer->smtpConnect();
            return true;
        } catch (Exception $e) {
            error_log('SMTP test failed: ' . $e->getMessage());
            return false;
        }
    }
}
