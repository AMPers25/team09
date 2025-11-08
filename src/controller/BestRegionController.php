<?php
// File: src/controller/BestRegionController.php
namespace App\Controller;

use App\Model\BestRegionModel;

class BestRegionController
{
    private $dbConnection;

    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
    }

    /**
     * 기능 3-3 API 핸들러: 지역별 여행 적합 지역 추천 (Top 5)
     * URL: GET /api/travel-index/region-ranking
     */
    public function getRegionRankingAction(array $queryParams)
    {
        // 1. 필수 Query Parameter 검증
        if (empty($queryParams['start_date']) || empty($queryParams['end_date'])) {
            $this->sendErrorResponse(400, "필수 데이터 (start_date, end_date)가 누락되었습니다.");
            return;
        }

        $startDate = $queryParams['start_date'];
        $endDate = $queryParams['end_date'];

        // 2. 입력값 형식 검증 (YYYY-MM-DD 형식 검사)
        // 실제 프로젝트에서는 더 강력한 날짜 유효성 검사가 필요합니다.
        if (!$this->isValidDateFormat($startDate) || !$this->isValidDateFormat($endDate)) {
            $this->sendErrorResponse(400, "날짜 형식이 유효하지 않습니다. (YYYY-MM-DD 형식이 필요합니다.)");
            return;
        }

        // 3. Model 호출 및 데이터 가져오기
        try {
            // Model 클래스를 BestRegionModel로 사용
            $model = new BestRegionModel($this->dbConnection);
            $results = $model->getBestRegionRanking($startDate, $endDate);

            if (empty($results)) {
                // 404 Not Found (데이터가 없는 경우)
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

    // ... (sendResponse, sendErrorResponse, isValidDateFormat 헬퍼 함수는 생략)
}