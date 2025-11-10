<?php

/**
 * File: src/controller/BookmarkController.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기(Bookmark) Controller
 * Last Updated: 2025-11-09
 */

namespace App\Controller;

class BookmarkController
{
    /** @var \PDO */
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * 기능 4-1: 즐겨찾기 생성 API
     * Method: POST
     * URL 예시: POST /api/bookmarks
     * Body(JSON or x-www-form-urlencoded):
     *   - region_code (필수)
     *   - start_date  (필수, YYYY-MM-DD)
     *   - end_date    (필수, YYYY-MM-DD)
     */
    public function createBookmarkAction(array $bodyParams)
    {
        // 필수 파라미터 검증
        if (
            !isset($bodyParams['region_code']) || empty($bodyParams['region_code']) ||
            !isset($bodyParams['start_date'])  || empty($bodyParams['start_date'])  ||
            !isset($bodyParams['end_date'])    || empty($bodyParams['end_date'])
        ) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, start_date, end_date)가 누락되었습니다.");
            return;
        }

        $regionCode = $bodyParams['region_code'];
        $startDate  = $bodyParams['start_date'];
        $endDate    = $bodyParams['end_date'];

        try {
            $model = new \App\Model\BookmarkModel($this->dbConnection);
            $bookmarkId = $model->createBookmark($regionCode, $startDate, $endDate);

            $this->sendResponse(201, "즐겨찾기 등록이 완료되었습니다.", [
                'bookmark_id' => $bookmarkId,
                'region_code' => $regionCode,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
            ]);

        } catch (\Exception $e) {
            // createBookmark 내부에서 의미있는 메시지를 던지므로 그대로 사용
            error_log("Controller Error in createBookmarkAction: " . $e->getMessage());
            // 중복 즐겨찾기 등도 일단 400으로 돌려주고 싶으면 아래처럼 처리
            if (strpos($e->getMessage(), "이미 동일한 기간과 지역이 즐겨찾기에 등록") !== false) {
                $this->sendErrorResponse(400, $e->getMessage());
            } else {
                $this->sendErrorResponse(500, "즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.");
            }
        }
    }

    /**
     * 기능 4-2: 즐겨찾기 삭제 API
     * Method: DELETE
     * URL 예시:
     *   - DELETE /api/bookmarks?bookmark_id=1
     *   또는
     *   - DELETE /api/bookmarks?region_code=01001&start_date=2024-07-01&end_date=2024-07-03
     */
    public function deleteBookmarkAction(array $queryParams)
    {
        $model = new \App\Model\BookmarkModel($this->dbConnection);

        try {
            $deleted = false;

            if (isset($queryParams['bookmark_id']) && !empty($queryParams['bookmark_id'])) {
                $bookmarkId = (int)$queryParams['bookmark_id'];
                $deleted = $model->deleteBookmarkById($bookmarkId);
            } elseif (
                isset($queryParams['region_code'], $queryParams['start_date'], $queryParams['end_date']) &&
                !empty($queryParams['region_code']) &&
                !empty($queryParams['start_date']) &&
                !empty($queryParams['end_date'])
            ) {
                $deleted = $model->deleteBookmarkByTuple(
                    $queryParams['region_code'],
                    $queryParams['start_date'],
                    $queryParams['end_date']
                );
            } else {
                $this->sendErrorResponse(400, "bookmark_id 또는 (region_code, start_date, end_date) 중 하나는 반드시 제공되어야 합니다.");
                return;
            }

            if ($deleted) {
                $this->sendResponse(200, "즐겨찾기 삭제가 완료되었습니다.", []);
            } else {
                $this->sendErrorResponse(404, "삭제할 즐겨찾기 데이터를 찾을 수 없습니다.");
            }

        } catch (\Exception $e) {
            error_log("Controller Error in deleteBookmarkAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * 기능 4-3: 즐겨찾기 목록 조회 API
     * Method: GET
     * URL 예시: GET /api/bookmarks
     *
     * 현재는 전체 즐겨찾기 목록을 반환.
     */
    public function listBookmarksAction(array $queryParams = [])
    {
        try {
            $model = new \App\Model\BookmarkModel($this->dbConnection);
            $results = $model->getAllBookmarks();

            $this->sendResponse(200, "즐겨찾기 목록 조회가 완료되었습니다.", $results);

        } catch (\Exception $e) {
            error_log("Controller Error in listBookmarksAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "즐겨찾기 목록 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * JSON 성공 응답 헬퍼
     */
    protected function sendResponse(int $status, string $message, array $data)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    }

    /**
     * JSON 오류 응답 헬퍼
     */
    protected function sendErrorResponse(int $status, string $message)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message]);
    }
}
