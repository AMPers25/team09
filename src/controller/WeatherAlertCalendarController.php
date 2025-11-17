<?php
/**
 * File: src/controller/WeatherAlertCalendarController.php
 * Author: 황혜린
 * Description: 특정 월의 지역별 기상 특보 목록 조회 Controller
 * Last Updated: 2025-11-17
 */

namespace App\Controller;

class WeatherAlertCalendarController
{
    /** @var \PDO */
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * GET /api/alerts/calendar?region_code=XXXXX&year=YYYY&month=MM
     *
     * Response:
     * {
     *   "status": 200,
     *   "message": "OK",
     *   "data": [
     *     { "alert_id": 1001, "date_id": "2025-10-03", "alert_time": "14:00:00", "alert_type": "폭우" }
     *   ]
     * }
     */
    public function getMonthlyAlertsAction(array $queryParams = []): void
    {
        $regionCode = $queryParams['region_code'] ?? null;
        $yearParam  = $queryParams['year']        ?? null;
        $monthParam = $queryParams['month']       ?? null;

        // 1) 필수 파라미터 검증
        if (!$regionCode || !$yearParam || !$monthParam) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, year, month)가 누락되었습니다.");
            return;
        }

        // 2) 형식/범위 검증
        if (!preg_match('/^\d{5}$/', $regionCode)) {
            $this->sendErrorResponse(400, "region_code 형식이 올바르지 않습니다. (5자리)");
            return;
        }
        if (!preg_match('/^\d{4}$/', (string)$yearParam)) {
            $this->sendErrorResponse(400, "year 형식이 올바르지 않습니다. (YYYY)");
            return;
        }
        if (!ctype_digit((string)$monthParam) || (int)$monthParam < 1 || (int)$monthParam > 12) {
            $this->sendErrorResponse(400, "month 값이 올바르지 않습니다. (1~12)");
            return;
        }

        $year  = (int)$yearParam;
        $month = (int)$monthParam;

        try {
            $model  = new \App\Model\WeatherAlertCalendarModel($this->dbConnection);
            $data   = $model->getMonthlyAlerts($regionCode, $year, $month);

            $this->sendResponse(200, "OK", $data);

        } catch (\Exception $e) {
            error_log("Controller Error in getMonthlyAlertsAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "기상 특보 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /** 공통 성공 응답 */
    protected function sendResponse(int $status, string $message, array $data): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ]);
    }

    /** 공통 오류 응답 */
    protected function sendErrorResponse(int $status, string $message): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode([
            'status'  => $status,
            'message' => $message
        ]);
    }
}
