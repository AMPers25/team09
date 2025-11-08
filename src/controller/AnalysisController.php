<?php
/**
 * File: src/model/AnalysisController.php
 * Author: 김연수
 * Description: 고급 분석 기능을 위한 Controller 클래스. (3-1, 3-2, 3-3 기능 포함)
 * Last Updated: 2025-11-08
 */

// 임시
// require 'AnalysisModel.php'; // 실제 환경에서는 Autoload 필요
// require 'DB.php';           // 실제 환경에서는 DB 연결 클래스 필요

class AnalysisController
{
    private $dbConnection;

    /**
     * 생성자: DB 연결 객체를 주입받아 저장합니다.
     * 실제 Controller는 보통 Framework에서 의존성 주입(DI)으로 DB 객체를 받습니다.
     * @param object $dbConnection PDO 객체 등
     */
    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * PM10 클린 기간 랭킹 요청 처리
     * GET /api/air-quality/clean-streak?region_code=SEOUL
     */
    public function getCleanStreakRankingAction()
    {
        // 1. 요청에서 지역 코드(region_code) 가져오기 (GET 요청 예시)
        // 실제 환경에서는 프레임워크의 요청 객체(Request Object)를 사용합니다.
        $regionCode = $_GET['region_code'] ?? null;

        // 2. 입력값 유효성 검사
        if (empty($regionCode)) {
            // 400 Bad Request 응답
            $this->sendJsonResponse(['error' => 'Region code is required.'], 400);
            return;
        }

        try {
            // 3. Model 인스턴스 생성 및 DB 연결 주입
            $model = new AnalysisModel($this->dbConnection);

            // 4. Model 메소드 호출 (핵심 비즈니스 로직 실행)
            $rankingData = $model->getCleanStreakRanking($regionCode);

            // 5. 성공 응답 (200 OK)
            $this->sendJsonResponse($rankingData, 200);

        } catch (Exception $e) {
            // DB 오류 등 서버 내부 오류 처리 (500 Internal Server Error)
            // Model에서 던진 예외 메시지를 사용자에게는 노출하지 않고 일반적인 메시지 전달
            error_log("Controller Error: " . $e->getMessage());
            $this->sendJsonResponse(['error' => 'An internal server error occurred during analysis.'], 500);
        }
    }

    /**
     * JSON 응답을 보내는 헬퍼 함수
     * @param array $data 응답 데이터
     * @param int $statusCode HTTP 상태 코드
     */
    private function sendJsonResponse(array $data, int $statusCode)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
}