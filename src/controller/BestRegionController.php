<?php
/**
 * File: src/model/BestRegionController.php
 * Author: 김연수
 * Description: 기능 3-3. 여행 지역 추천 Controller
 * Last Updated: 2025-11-08
 */

// File: src/controller/BestRegionController.php
namespace App\Controller;

use App\Model\BestRegionModel;

class BestRegionController
{
    private $model;

    public function __construct(BestRegionModel $model) {
        $this->model = $model;
    }
    // JSON 성공 응답을 보내는 헬퍼 함수 (Mocking 가능하도록 protected 유지)
    protected function sendResponse(int $status, string $message, array $data)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    }

    // JSON 오류 응답을 보내는 헬퍼 함수
    protected function sendErrorResponse(int $status, string $message)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message]);
    }

    // 날짜 유효성 검사 함수
    protected function isValidDateFormat(string $date): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * 기능 3-3 API 핸들러: 지역별 여행 적합 지역 추천 (Top 5)
     * URL: GET /api/recommend/best-region/{start_date}/{end_date}
     */
    public function getRegionRankingAction(array $params)
    {
        // 1. 필수 Query Parameter 검증
        if (empty($params['start_date']) || empty($params['end_date'])) {
            $this->sendErrorResponse(400, "필수 데이터 (start_date, end_date)가 누락되었습니다.");
            return;
        }

        $startDate = $params['start_date'];
        $endDate = $params['end_date'];

        // 2. 입력값 형식 검증
        if (!$this->isValidDateFormat($startDate) || !$this->isValidDateFormat($endDate)) {
            $this->sendErrorResponse(400, "날짜 형식이 유효하지 않습니다. (YYYY-MM-DD 형식이 필요합니다.)");
            return;
        }

        // 3. Model 호출 및 데이터 가져오기
        try {

            $results = $this->model->getBestRegionRanking($startDate, $endDate);

            if (empty($results)) {
                $this->sendErrorResponse(404, "해당 기간의 지역 분석 데이터가 없습니다.");
                return;
            }

            // 4. 성공 응답 (200 OK)
            $this->sendResponse(200, "Top 5 여행 적합 지역 랭킹 조회가 완료되었습니다.", $results);

        } catch (\Exception $e) {
            // 500 Internal Server Error 처리
            error_log("Controller Error for 3-3: " . $e->getMessage());
            $this->sendErrorResponse(500, "서버 내부 오류입니다.");
        }

    }
}