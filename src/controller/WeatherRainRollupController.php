<?php
/**
 * File: src/controller/WeatherRainRollupController.php
 * Author: 황혜린
 * Description: 특정 지역/기간의 일별 강수량 + 월 합계(ROLLUP) 조회 Controller
 * Last Updated: 2025-11-17
 */

namespace App\Controller;

class WeatherRainRollupController
{
    /** @var \PDO */
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * GET /api/weather/rain-rollup?region_code=XXXXX&from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Response:
     * {
     *   "status": 200,
     *   "message": "OK",
     *   "data": [
     *     { "level": "DAY", "date_id": "2025-10-01", "ym": "2025-10", "rainfall_mm": 3.2 },
     *     { "level": "DAY", "date_id": "2025-10-02", "ym": "2025-10", "rainfall_mm": 0.0 },
     *     { "level": "MONTH_TOTAL", "date_id": null,  "ym": "2025-10", "rainfall_mm": 78.4 }
     *   ]
     * }
     */
    public function getRainRollupAction(array $queryParams = []): void
    {
        // 1) 필수 파라미터 검증
        $regionCode = $queryParams['region_code'] ?? null;
        $from       = $queryParams['from'] ?? null;
        $to         = $queryParams['to'] ?? null;

        if (!$regionCode || !$from || !$to) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, from, to)가 누락되었습니다.");
            return;
        }

        // 간단한 형식 검증(YYYY-MM-DD)
        if (!$this->isValidDate($from) || !$this->isValidDate($to)) {
            $this->sendErrorResponse(400, "날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)");
            return;
        }

        try {
            $model  = new \App\Model\WeatherRainRollupModel($this->dbConnection);
            $result = $model->getRainRollup($regionCode, $from, $to);

            $this->sendResponse(200, "OK", $result);

        } catch (\Exception $e) {
            error_log("Controller Error in getRainRollupAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }

    private function isValidDate(string $yyyyMmDd): bool
    {
        // 간단한 포맷 체크 & 실제 날짜 검증
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $yyyyMmDd)) {
            return false;
        }
        [$y, $m, $d] = explode('-', $yyyyMmDd);
        return checkdate((int)$m, (int)$d, (int)$y);
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
