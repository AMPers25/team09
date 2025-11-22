<?php
/**
 * File: src/controller/DailyController.php
 * Author: 강한나
 * Description: 특정 지역/날짜 일일 날씨 통합 조회 Controller
 * URL: GET /api/weather/daily
 * Last Updated: 2025-11-17
 */

namespace App\Controller;

use App\Model\DailyModel;

class DailyController
{
    /** @var \PDO */
    private $dbConnection;
    
    /** @var DailyModel|null */
    private $model;

    /**
     * 생성자: DB 연결 또는 Model 주입 지원
     * - Router를 통한 호출: Model 주입
     * - 직접 호출: DB 연결 주입
     */
    public function __construct($dbConnectionOrModel)
    {
        if ($dbConnectionOrModel instanceof DailyModel) {
            // Router 패턴: Model 주입
            $this->model = $dbConnectionOrModel;
        } else {
            // 기존 패턴: DB 연결 주입
            $this->dbConnection = $dbConnectionOrModel;
        }
    }

    /**
     * Model 생성 헬퍼 (테스트 시 Mocking 용이)
     */
    protected function getModel(): DailyModel
    {
        if ($this->model !== null) {
            return $this->model;
        }
        return new DailyModel($this->dbConnection);
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
     * JWT 토큰 간단 검증 (형식만)
     * - Authorization: Bearer {JWT}
     */
    protected function isValidJwtFromHeader(): bool
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            return false;
        }

        if (stripos($authHeader, 'Bearer ') !== 0) {
            return false;
        }

        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return false;
        }

        // TODO: 실제 JWT 서명 검증 로직 추가
        return true;
    }

    /**
     * 일일 날씨 통합 조회 Action
     *
     * Method: GET
     * URL: /api/weather/daily?region_code=090&date=2025-10-12
     */
    public function getDailyWeatherAction(array $queryParams): void
    {
        // 1) HTTP Method 체크
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            $this->sendErrorResponse(400, '잘못된 요청 형식입니다.');
            return;
        }

        // 2) JWT 검증 (Router를 통한 /api/calendar/daily/ 경로는 우회)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isCalendarDailyPath = strpos($requestUri, '/api/calendar/daily/') !== false;
        
        if (!$isCalendarDailyPath && !$this->isValidJwtFromHeader()) {
            $this->sendErrorResponse(401, '유효한 토큰이 필요합니다.');
            return;
        }

        // 3) 필수 파라미터 검증
        $regionCode = $queryParams['region_code'] ?? null;
        $date       = $queryParams['date'] ?? null;

        if (empty($regionCode) || empty($date)) {
            $this->sendErrorResponse(400, '필수 데이터가 누락되었습니다.');
            return;
        }

        // 4) 형식 검증
        if (!$this->isValidRegionCode($regionCode) || !$this->isValidDate($date)) {
            $this->sendErrorResponse(400, '잘못된 데이터 형식입니다.');
            return;
        }

        try {
            // 5) Model 호출
            $model = $this->getModel();
            $row   = $model->getDailyWeather($regionCode, $date);

            if ($row === null) {
                // 404 - 데이터 없음
                $this->sendErrorResponse(404, '해당 조건의 일일 날씨 데이터가 없습니다.');
                return;
            }

            // 6) 명세에 맞는 데이터 매핑
            $data = [
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
                    'status_name'    => $row['status_name'] ?? null,
                ],
                'weather_alert' => [
                    'alert_time' => $row['alert_time'] ?? null,
                    'alert_type' => $row['alert_type'] ?? null,
                ],
                'air_quality' => [
                    'pm10' => isset($row['pm10']) ? (float)$row['pm10'] : null,
                ],
            ];

            // 7) 성공 응답
            $this->sendResponse(200, 'OK', $data);

        } catch (\Exception $e) {
            error_log('Controller Error in getDailyWeatherAction: ' . $e->getMessage());
            $this->sendErrorResponse(500, '서버 내부 오류입니다.');
        }
    }

    /**
     * JSON 성공 응답 헬퍼
     */
    protected function sendResponse(int $status, string $message, array $data): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * JSON 오류 응답 헬퍼
     */
    protected function sendErrorResponse(int $status, string $message): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode([
            'status'  => $status,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    }
}


