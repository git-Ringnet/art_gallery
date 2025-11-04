<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DatabaseEncryptionService
{
    /**
     * Mã hóa file SQL
     * 
     * @param string $inputFile - File SQL gốc
     * @param string $outputFile - File đã mã hóa (.encrypted)
     * @return string
     */
    public static function encrypt($inputFile, $outputFile = null)
    {
        if (!file_exists($inputFile)) {
            throw new \Exception("File không tồn tại: {$inputFile}");
        }

        // Tạo tên file output nếu chưa có
        if (!$outputFile) {
            $outputFile = $inputFile . '.encrypted';
        }

        // Lấy encryption key từ .env
        $key = config('app.key');
        if (!$key) {
            throw new \Exception('APP_KEY chưa được set trong .env');
        }

        // Đọc file SQL
        $data = file_get_contents($inputFile);
        
        // Mã hóa bằng AES-256-CBC
        $iv = random_bytes(16); // IV 16 bytes cho AES-256-CBC
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            base64_decode(substr($key, 7)), // Bỏ "base64:" prefix
            0,
            $iv
        );

        if ($encrypted === false) {
            throw new \Exception('Lỗi khi mã hóa file');
        }

        // Lưu IV + encrypted data
        // Format: IV (16 bytes) + encrypted data
        $result = $iv . base64_decode($encrypted);
        
        if (file_put_contents($outputFile, $result) === false) {
            throw new \Exception('Không thể ghi file đã mã hóa');
        }

        Log::info("File đã được mã hóa: {$outputFile}");
        
        return $outputFile;
    }

    /**
     * Giải mã file SQL
     * 
     * @param string $inputFile - File đã mã hóa
     * @param string $outputFile - File SQL gốc
     * @return string
     */
    public static function decrypt($inputFile, $outputFile = null)
    {
        if (!file_exists($inputFile)) {
            throw new \Exception("File không tồn tại: {$inputFile}");
        }

        // Tạo tên file output nếu chưa có
        if (!$outputFile) {
            $outputFile = str_replace('.encrypted', '', $inputFile);
        }

        // Lấy encryption key từ .env
        $key = config('app.key');
        if (!$key) {
            throw new \Exception('APP_KEY chưa được set trong .env');
        }

        // Đọc file đã mã hóa
        $data = file_get_contents($inputFile);
        
        // Tách IV và encrypted data
        $iv = substr($data, 0, 16);
        $encrypted = base64_encode(substr($data, 16));

        // Giải mã
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            base64_decode(substr($key, 7)), // Bỏ "base64:" prefix
            0,
            $iv
        );

        if ($decrypted === false) {
            throw new \Exception('Lỗi khi giải mã file. Key có thể không đúng.');
        }

        // Lưu file SQL gốc
        if (file_put_contents($outputFile, $decrypted) === false) {
            throw new \Exception('Không thể ghi file đã giải mã');
        }

        Log::info("File đã được giải mã: {$outputFile}");
        
        return $outputFile;
    }

    /**
     * Kiểm tra file có bị mã hóa không
     * 
     * @param string $file
     * @return bool
     */
    public static function isEncrypted($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        // Check extension
        if (str_ends_with($file, '.encrypted')) {
            return true;
        }

        // Check nội dung (file SQL thường bắt đầu bằng -- hoặc /*)
        $handle = fopen($file, 'r');
        $firstBytes = fread($handle, 100);
        fclose($handle);

        // Nếu không phải text thì có thể là encrypted
        return !mb_check_encoding($firstBytes, 'UTF-8') || 
               !preg_match('/^(--|\/\*|CREATE|INSERT|DROP|USE)/i', $firstBytes);
    }
}
