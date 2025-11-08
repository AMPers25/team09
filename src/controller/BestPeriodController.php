<?php

/**
 * File: src/model/BestPeriodController.php
 * Author: 김연수
 * Description: 기능 3-2. 여행 기간 추천 Controller
 * Last Updated: 2025-11-08
 */

class BestPeriodController
{
    private $dbConnection;

    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    /**
     * 기능 3-2 API 핸들러: 여행 적합 기간 추천 (주간 Top 5)
     * URL: GET /api/travel-index/recommend-weeks
     */
    public function getWeekRankingAction(array $queryParams)
    {
        if (!isset($queryParams['region_code']) || empty($queryParams['region_code'])) {
            $this->sendErrorResponse(400, "필수 데이터 (region_code)가 누락되었습니다.");
            return;
        }

        $regionCode = $queryParams['region_code'];

        try {
            // BestPeriodModel 클래스를 사용
            $model = new \App\Model\BestPeriodModel($this->dbConnection);
            $results = $model->getBestWeekRanking($regionCode);

            if (empty($results)) {
                $this->sendErrorResponse(404, "해당 지역의 2024년 주간 분석 데이터가 없습니다.");
                return;
            }

            $this->sendResponse(200, "Top 5 여행 적합 주간 추천이 완료되었습니다.", $results);

        } catch (\Exception $e) {
            error_log("Controller Error for 3-2: " . $e->getMessage());
            $this->sendErrorResponse(500, "서버 내부 오류입니다.");
        }
    }

    /**
     * JSON 성공 응답을 보내는 헬퍼 함수 (API 명세서 형식 준수)
     */
    private function sendResponse(int $status, string $message, array $data)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    }

    /**
     * JSON 오류 응답을 보내는 헬퍼 함수
     */
    private function sendErrorResponse(int $status, string $message)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message]);
    }

}