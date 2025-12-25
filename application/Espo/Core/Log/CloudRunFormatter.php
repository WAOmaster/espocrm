<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Log;

use Espo\Core\Api\Request;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;
use Throwable;

/**
 * Formats log records as structured JSON for Google Cloud Logging.
 * Cloud Run automatically parses JSON logs and extracts structured fields.
 *
 * @see https://cloud.google.com/logging/docs/structured-logging
 */
class CloudRunFormatter extends JsonFormatter
{
    /**
     * Map Monolog levels to Google Cloud Logging severity levels.
     * @var array<string, string>
     */
    private const SEVERITY_MAP = [
        'DEBUG' => 'DEBUG',
        'INFO' => 'INFO',
        'NOTICE' => 'NOTICE',
        'WARNING' => 'WARNING',
        'ERROR' => 'ERROR',
        'CRITICAL' => 'CRITICAL',
        'ALERT' => 'ALERT',
        'EMERGENCY' => 'EMERGENCY',
    ];

    public function __construct()
    {
        parent::__construct(self::BATCH_MODE_NEWLINES, true);
    }

    public function format(LogRecord $record): string
    {
        $output = [
            'severity' => self::SEVERITY_MAP[$record->level->name] ?? 'DEFAULT',
            'message' => $record->message,
            'timestamp' => $record->datetime->format('c'),
            'logging.googleapis.com/sourceLocation' => [
                'function' => $record->channel,
            ],
        ];

        // Add httpRequest field for Cloud Logging integration
        $request = $record->context['request'] ?? null;
        if ($request instanceof Request) {
            $output['httpRequest'] = [
                'requestMethod' => $request->getMethod(),
                'requestUrl' => $request->getResourcePath(),
            ];
        }

        // Add exception details
        $exception = $record->context['exception'] ?? null;
        if ($exception instanceof Throwable) {
            $output['error'] = [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'type' => get_class($exception),
            ];

            // Add stack trace for errors
            if ($record->level->value >= 400) { // ERROR and above
                $output['error']['stackTrace'] = $exception->getTraceAsString();
            }
        }

        // Add any other context (excluding already processed items)
        $additionalContext = array_diff_key($record->context, ['request' => 1, 'exception' => 1]);
        if (!empty($additionalContext)) {
            $output['context'] = $this->sanitizeContext($additionalContext);
        }

        return json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Sanitize context data for JSON serialization.
     */
    private function sanitizeContext(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $result[$key] = (string) $value;
                } else {
                    $result[$key] = get_class($value);
                }
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeContext($value);
            } elseif (is_scalar($value) || is_null($value)) {
                $result[$key] = $value;
            } else {
                $result[$key] = gettype($value);
            }
        }

        return $result;
    }
}
