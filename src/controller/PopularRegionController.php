<?php
/**
 * File: src/controller/PopularRegionController.php
 * Author: 황혜린
 * Description: 기능 1-2. 홈 인기 지역(popular_count 기준) 조회 Controller
 * Last Updated: 2025-11-09
 */

namespace App\Controller;

class PopularRegionController
{
    /** @var \PDO */
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * 인기 지역 목록 조회 API
     *
     * Method: GET
     * URL 예시: GET /api/regions/popular?limit=10
     *
     * Response (200 OK):
     * {
     *   "status": 200,
     *   "message": "OK",
     *   "data": [
     *     { "rank": 1, "region_code": "011", "region_name": "서울시", "popular_count": 128 },
     *     { "rank": 2, "region_code": "012", "region_name": "강릉시", "popular_count": 97 }
     *   ]
     * }
     */
    public function getPopularRegionsAction(array $queryParams = []): void
    {
        // limit 파라미터 (선택, 기본 10, 최대 100)
        $limit = 10;
        if (isset($queryParams['limit']) && ctype_digit((string)$queryParams['limit'])) {
            $limit = (int)$queryParams['limit'];
            if ($limit <= 0) {
                $limit = 10;
            } elseif ($limit > 100) {
                $limit = 100;
            }
        }

        try {
            $model = new \App\Model\PopularRegionModel($this->dbConnection);
            $results = $model->getPopularRegions($limit);

            $this->sendResponse(200, 'OK', $results);

        } catch (\Exception $e) {
            error_log('Controller Error in getPopularRegionsAction: ' . $e->getMessage());
            $this->sendErrorResponse(500, '인기 지역 조회 중 서버 내부 오류가 발생했습니다.');
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
        ]);
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
        ]);
    }
}
