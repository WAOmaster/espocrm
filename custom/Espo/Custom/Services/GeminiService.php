<?php

namespace Espo\Custom\Services;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use RuntimeException;

class GeminiService
{
    private Config $config;
    private Log $log;

    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    public function __construct(Config $config, Log $log)
    {
        $this->config = $config;
        $this->log = $log;
    }

    public function generateContent(string $prompt): string
    {
        $apiKey = $this->config->get('geminiApiKey');
        $model = $this->config->get('geminiModel', 'gemini-1.5-flash');

        if (empty($apiKey)) {
            throw new RuntimeException("Gemini API Key is not configured.");
        }

        $url = sprintf(self::API_URL_TEMPLATE, $model, $apiKey);

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log->error("Gemini API Curl Error: " . $error);
            throw new RuntimeException("Failed to connect to Gemini API.");
        }

        if ($httpCode !== 200) {
            $this->log->error("Gemini API Error ({$httpCode}): " . $response);
            throw new RuntimeException("Gemini API returned error code {$httpCode}.");
        }

        $responseData = json_decode($response, true);
        
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        $this->log->error("Gemini API Unexpected Response: " . $response);
        throw new RuntimeException("Unexpected response format from Gemini API.");
    }
}
