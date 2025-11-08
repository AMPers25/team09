<?php
/**
 * File: src/model/CleanDayController.php
 * Author: 김연수
 * Description: 기능 3-1. 클린데이(미세먼지 연속 좋음 일수) 계산 위한  Controller 클래스
 * Last Updated: 2025-11-08
 */

namespace App\Controller;

use App\Model\CleanDayModel;
use Exception; // 내장 Exception은 \Exception으로 처리합니다.

class CleanDayController
{
    private $dbConnection;

    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    // Model 생성을 담당하는 헬퍼 함수 (Controller 테스트에서 Mocking 대상)
    protected function getModel(): \App\Model\CleanDayModel
    {
        // Model 인스턴스 생성 로직
        return new \App\Model\CleanDayModel($this->dbConnection);
    }

    /**
     * JSON 응답을 보내는 헬퍼 함수 (테스트를 위해 protected로 변경)
     */
    protected function sendJsonResponse(array $data, int $statusCode) // <-- protected로 변경
    {
        // 응답 메시지 및 상태 코드 설정은 Controller가 하므로, 여기서는 data만 받도록 단순화
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }

    /**
     * JSON 오류 응답을 보내는 헬퍼 함수 (테스트를 위해 protected로 변경)
     */
    protected function sendErrorResponse(int $statusCode, string $message)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode(['status' => $statusCode, 'message' => $message]);
    }


    /**
     * PM10 클린 기간 랭킹 요청 처리
     * GET /api/air-quality/clean-streak?region_code=SEOUL
     */
    public function getCleanStreakRankingAction()
    {
        // 1. 요청에서 지역 코드(region_code) 가져오기
        $regionCode = $_GET['region_code'] ?? null;

        // 2. 입력값 유효성 검사
        if (empty($regionCode)) {
            $this->sendErrorResponse(400, "필수 데이터 (region_code)가 누락되었습니다."); // <-- sendErrorResponse 사용
            return;
        }

        try {
            // 3. Model 인스턴스 생성 및 DB 연결 주입 (getModel 헬퍼 함수 사용)
            $model = $this->getModel();

            // 4. Model 메소드 호출 (핵심 비즈니스 로직 실행)
            $rankingData = $model->getCleanStreakRanking($regionCode);

            // 404 처리 (데이터가 없는 경우)
            if (empty($rankingData)) {
                $this->sendErrorResponse(404, "해당 지역의 2024년 주간 분석 데이터가 없습니다.");
                return;
            }

            // 5. 성공 응답 (200 OK)
            $responseBody = [
                'status' => 200,
                'message' => 'PM10 클린 연속 기간 Top 5 조회가 완료되었습니다.',
                'data' => $rankingData
            ];
            $this->sendJsonResponse($responseBody, 200);

        } catch (\Exception $e) {
            // DB 오류 등 서버 내부 오류 처리 (500 Internal Server Error)
            error_log("Controller Error: " . $e->getMessage());
            $this->sendErrorResponse(500, "서버 내부 오류입니다.");
        }
    }
}