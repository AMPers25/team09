<?php
/**
 * File: src/controller/WeatherRainRollupController.php
 * Author: 황혜린
 * Description: 기능 2-3. 특정 지역/기간의 일별 강수량 + 월 합계(ROLLUP) 조회 Controller
 * Last Updated: 2025-11-21
 */

namespace App\Controller;

use App\Model\WeatherRainRollupModel;

class WeatherRainRollupController
{
    /** @var WeatherRainRollupModel */
    private WeatherRainRollupModel $model;

    public function __construct(WeatherRainRollupModel $model)
    {
        $this->model = $model;
    }

    /**
     * GET /api/calendar/rain/{region_code}/{year}/{month}
     *  - path params 로 받은 월을 이용해 [2024-MM-01 ~ 2024-MM(마지막날)] 범위를 계산
     *  - Model::getRainRollup(region, from, to) 재사용
     */
    public function getRainRollupAction(array $params = []): void
    {
        $regionCode = $params['region_code'] ?? null;
        $yearParam = $params['year']       ?? null;
        $monthParam = $params['month']       ?? null;
        $year = 2024; // 고정된 연도

        // 1) 필수값 점검
        if (!$regionCode || !$monthParam) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, month)가 누락되었습니다.");
            return;
        }

        // 2) 형식/범위 검증
        if (!preg_match('/^\d{3}$/', (string)$regionCode)) {
            $this->sendErrorResponse(400, "region_code 형식이 올바르지 않습니다. (3자리)");
            return;
        }
        if (!ctype_digit((string)$monthParam) || (int)$monthParam < 1 || (int)$monthParam > 12) {
            $this->sendErrorResponse(400, "month 값이 올바르지 않습니다. (1~12)");
            return;
        }

        $month = (int)$monthParam;

        // 3) 월 범위 계산
        $from = sprintf('%04d-%02d-01', $year, $month);
        // 마지막 날: PHP로 안전하게 계산
        $lastDay = (int)date('t', strtotime($from));
        $to   = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);

        try {
            $rows = $this->model->getRainRollup($regionCode, $from, $to);
            $this->sendResponse(200, 'OK', $rows);
        } catch (\Exception $e) {
            error_log("Controller Error in getRainRollupAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.");
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