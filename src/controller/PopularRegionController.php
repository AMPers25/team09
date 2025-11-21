<?php
/**
 * File: src/controller/PopularRegionController.php
 * Author: 황혜린
 * Description: 기능 1-2. 홈 인기 지역(popular_count 기준) 조회 Controller
 * Last Updated: 2025-11-21
 */

namespace App\Controller;

use App\Model\PopularRegionModel;

class PopularRegionController
{
    /** @var PopularRegionModel */
    private PopularRegionModel $model;

    public function __construct(PopularRegionModel $model)
    {
        $this->model = $model; // Router가 PopularRegionModel을 주입
    }

    /**
     * 인기 지역 목록 조회 API
     *
     * Method: GET
     * URL: /api/regions/popular?limit=10
     *
     * Response (200 OK):
     * {
     *   "status": 200,
     *   "message": "OK",
     *   "data": [
     *     { "rank": 1, "region_code": "011", "region_name": "강남구", "province": "서울특별시", "popular_count": 128 },
     *     { "rank": 2, "region_code": "012", "region_name": "서초구", "province": "서울특별시", "popular_count": 97 }
     *   ]
     * }
     */
    public function getPopularRegionsAction(array $queryParams = []): void
    {
        $limit = $this->sanitizeLimit($queryParams['limit'] ?? null);

        try {
            $results = $this->model->getPopularRegions($limit);
            $this->sendResponse(200, 'OK', $results);

        } catch (\Exception $e) {
            error_log('Controller Error in getPopularRegionsAction: ' . $e->getMessage());
            $this->sendErrorResponse(500, '인기 지역 조회 중 서버 내부 오류가 발생했습니다.');
        }
    }

    /** limit 파라미터 기본값/상한 처리 */
    private function sanitizeLimit($raw): int
    {
        if ($raw === null || !ctype_digit((string)$raw)) return 10;
        $n = (int)$raw;
        if ($n <= 0)   return 10;
        if ($n > 100)  return 100;
        return $n;
    }

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
