<?php
/**
 * File: src/controller/WeatherAlertCalendarController.php
 * Author: 황혜린
 * Description: 기능 2-4. 특정 월의 지역별 기상 특보 목록 조회 Controller
 * Last Updated: 2025-11-21
 */

namespace App\Controller;

use App\Model\WeatherAlertCalendarModel;

class WeatherAlertCalendarController
{
    /** @var WeatherAlertCalendarModel */
    private WeatherAlertCalendarModel $model;

    public function __construct(WeatherAlertCalendarModel $model)
    {
        $this->model = $model;
    }

    /**
     * GET /api/calendar/alert/{region_code}/{year}/{month}
     *  - path params로 연도, 월 지정
     *  - 컨트롤러에서 월 시작/끝을 계산해 모델에 넘김
     */
    public function getMonthlyAlertsAction(array $params = []): void
    {
        $regionCode = $params['region_code'] ?? null;
        $monthParam = $params['month']       ?? null;
        $year = 2024; // 고정된 연도

        // 1) 필수값
        if (!$regionCode || !$monthParam) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, year, month)가 누락되었습니다.");
            return;
        }

        // 2) 검증
        if (!preg_match('/^\d{3}$/', (string)$regionCode)) {
            $this->sendErrorResponse(400, "region_code 형식이 올바르지 않습니다. (3자리)");
            return;
        }
        if (!ctype_digit((string)$monthParam) || (int)$monthParam < 1 || (int)$monthParam > 12) {
            $this->sendErrorResponse(400, "month 값이 올바르지 않습니다. (1~12)");
            return;
        }

        $month = (int)$monthParam;

        // 3) 월 범위 계산 (PHP에서 처리)
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from)); // 그 달의 마지막 날

        try {
            $data = $this->model->getMonthlyAlertsByRange($regionCode, $from, $to);
            $this->sendResponse(200, "OK", $data);
        } catch (\Exception $e) {
            error_log("Controller Error in getMonthlyAlertsAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "기상 특보 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }

    protected function sendResponse(int $status, string $message, array $data): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status'=>$status,'message'=>$message,'data'=>$data]);
    }

    protected function sendErrorResponse(int $status, string $message): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status'=>$status,'message'=>$message]);
    }
}
