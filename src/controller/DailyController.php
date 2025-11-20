<?php
/**
 * File: src/controller/DailyController.php
 * Author: 강한나
 * Description:
 *   - Route 1: GET /api/calendar/daily/{region_code}/{date}
 *   - Route 2: GET /api/day-info/{region_code}/{date}
 * Last Updated: 2025-11-20
 */

namespace App\Controller;

use App\Model\DailyModel;

class DailyController
{
    private DailyModel $model;

    public function __construct(DailyModel $model)
    {
        $this->model = $model;
    }

    /**
     * region_code 형식 검증: 숫자 3~5자리
     */
    protected function isValidRegionCode(string $regionCode): bool
    {
        return (bool)preg_match('/^\d{3,5}$/', $regionCode);
    }

    /**
     * YYYY-MM-DD 형식 검증 + 실제 날짜 여부
     */
    protected function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }

    /**
     * 일일 날씨 통합 조회 (Router: /api/calendar/daily/{region_code}/{date})
     */
    public function getDailyWeather(array $params): void
    {
        $isDayInfo = $this->isDayInfoRoute($params);
        $regionCode = $this->extractRegionCode($params);
        $date = $this->extractDate($params);

        if (!$regionCode || !$date) {
            $this->respondError($isDayInfo, 400, '필수 파라미터(region_code, date)가 필요합니다.');
            return;
        }

        if (!$this->isValidRegionCode($regionCode) || !$this->isValidDate($date)) {
            $this->respondError($isDayInfo, 400, '잘못된 데이터 형식입니다.');
            return;
        }

        try {
            $row = $this->model->getDailyWeather($regionCode, $date);

            if ($row === null) {
                $this->respondError($isDayInfo, 404, '해당 조건의 일일 날씨 데이터가 없습니다.');
                return;
            }

            $base = $this->buildBasePayload($row);

            if ($isDayInfo) {
                $this->respondFrontend(200, $this->buildDayInfoPayload($base));
            } else {
                $this->respondApi(200, 'OK', $base);
            }

        } catch (\Exception $e) {
            error_log('Controller Error in getDailyWeather: ' . $e->getMessage());
            $this->respondError($isDayInfo, 500, '서버 내부 오류입니다.');
        }
    }

    private function buildDayInfoPayload(array $base): array
    {
        $dateParts = explode('-', $base['date_id']);
        $dateLabel = $dateParts[0] . '년 ' . (int)$dateParts[1] . '월 ' . (int)$dateParts[2] . '일';

        $statusCodeMap = [
            '90' => '맑음',
            '91' => '구름조금',
            '92' => '구름많음',
            '93' => '흐림',
            '01' => '비',
            '05' => '눈'
        ];
        $statusCode = isset($base['rain']['status_code']) ? str_pad((string)$base['rain']['status_code'], 2, '0', STR_PAD_LEFT) : null;
        $condition = $statusCodeMap[$statusCode] ?? '맑음';

        return [
            'regionName' => $base['region_name'] ?? '--',
            'dateLabel' => $dateLabel,
            'temperatureRange' => $this->formatRange($base['temperature']['min_temp'] ?? null, $base['temperature']['max_temp'] ?? null),
            'condition' => $condition,
            'icon' => null,
            'temperature' => [
                ['label' => '평균기온', 'value' => $this->formatDegree($base['temperature']['avg_temp'] ?? null)],
                ['label' => '최고기온', 'value' => $this->formatDegree($base['temperature']['max_temp'] ?? null)],
                ['label' => '최저기온', 'value' => $this->formatDegree($base['temperature']['min_temp'] ?? null)],
                ['label' => '일교차', 'value' => $this->formatDegree($base['temperature']['daily_temp_range'] ?? null)],
            ],
            'rain' => [
                ['label' => '일 강수량', 'value' => $this->formatNumber($base['rain']['daily_rainfall'] ?? null, 'mm')],
                ['label' => '평균 습도', 'value' => $this->formatNumber($base['rain']['humidity'] ?? null, '%')],
                ['label' => '평균 풍속', 'value' => $this->formatNumber($base['rain']['wind_speed'] ?? null, 'm/s')],
                ['label' => '평균 운량', 'value' => $this->formatNumber($base['rain']['cloud_cover'] ?? null, '옥타')],
            ],
            'weatherAlert' => $this->buildAlert($base['weather_alert']),
            'airQuality' => [
                'title' => '미세먼지 (PM10)',
                'value' => isset($base['air_quality']['pm10']) ? (string)(int)$base['air_quality']['pm10'] : null,
                'meta' => []
            ]
        ];
    }


    private function respondApi(int $statusCode, string $message, ?array $data = null): void
    {
        $payload = [
            'status'  => $statusCode,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        $this->emitJson($statusCode, $payload);
    }

    private function respondError(bool $isDayInfo, int $statusCode, string $message): void
    {
        if ($isDayInfo) {
            $this->respondFrontend($statusCode, ['error' => $message]);
        } else {
            $this->respondApi($statusCode, $message);
        }
    }

    private function isDayInfoRoute(array $params): bool
    {
        return ($params['_route'] ?? '') === '/api/day-info/{region_code}/{date}';
    }

    private function respondFrontend(int $statusCode, array $payload): void
    {
        $this->emitJson($statusCode, $payload);
    }

    private function extractRegionCode(array $params): ?string
    {
        return $params['region_code'] ?? null;
    }

    private function extractDate(array $params): ?string
    {
        return $params['date'] ?? null;
    }

    private function buildBasePayload(array $row): array
    {
        return [
            'region_code' => $row['region_code'],
            'region_name' => $row['region_name'] ?? null,
            'date_id'     => $row['date_id'],
            'temperature' => [
                'avg_temp'         => isset($row['avg_temp']) ? (float)$row['avg_temp'] : null,
                'max_temp'         => isset($row['max_temp']) ? (float)$row['max_temp'] : null,
                'min_temp'         => isset($row['min_temp']) ? (float)$row['min_temp'] : null,
                'daily_temp_range' => isset($row['daily_temp_range']) ? (float)$row['daily_temp_range'] : null,
            ],
            'rain' => [
                'daily_rainfall' => isset($row['daily_rainfall']) ? (float)$row['daily_rainfall'] : null,
                'humidity'       => isset($row['humidity']) ? (float)$row['humidity'] : null,
                'wind_speed'     => isset($row['wind_speed']) ? (float)$row['wind_speed'] : null,
                'cloud_cover'    => isset($row['cloud_cover']) ? (float)$row['cloud_cover'] : null,
                'status_code'    => isset($row['status_code']) ? (int)$row['status_code'] : null,
            ],
            'weather_alert' => [
                'alert_time' => $row['alert_time'] ?? null,
                'alert_type' => $row['alert_type'] ?? null,
            ],
            'air_quality' => [
                'pm10' => isset($row['pm10']) ? (float)$row['pm10'] : null,
            ],
        ];
    }

    private function formatDegree(?float $value): string
    {
        return $value === null ? '--' : round($value, 1) . '°';
    }

    private function formatNumber(?float $value, string $unit): string
    {
        return $value === null ? '--' : round($value, 1) . $unit;
    }

    private function formatRange(?float $min, ?float $max): string
    {
        $minLabel = $min === null ? '--' : round($min, 1) . '°';
        $maxLabel = $max === null ? '--' : round($max, 1) . '°';
        return $minLabel . ' / ' . $maxLabel;
    }

    private function buildAlert(array $alert): array
    {
        if (empty($alert['alert_type']) && empty($alert['alert_time'])) {
            return [];
        }

        return [[
            'label' => $alert['alert_type'] ?? '기상특보',
            'value' => $alert['alert_time'] ?? '--'
        ]];
    }

    private function emitJson(int $statusCode, array $payload): void
    {
        if ($this->canSendHttpHeaders()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function canSendHttpHeaders(): bool
    {
        return PHP_SAPI !== 'cli' && !headers_sent();
    }
}
