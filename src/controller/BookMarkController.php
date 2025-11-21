<?php
/**
 * File: src/controller/BookmarkController.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기(Bookmark) Controller
 * Last Updated: 2025-11-21
 */

namespace App\Controller;

use App\Model\BookmarkModel;

class BookmarkController
{
    /** @var BookmarkModel */
    private BookmarkModel $model;

    public function __construct(BookmarkModel $model)
    {
        $this->model = $model; // Router가 만든 Model 인스턴스를 주입
        // 세션 start는 index.php에서만
    }

    /** 현재 세션의 세션ID 반환 (세션 미시작/CLI 환경 모두 안전) */
    protected function getSessionId(): string
    {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '') {
            return (string)$_SESSION['user_id'];
        }
        $sid = session_id();
        return ($sid !== '') ? $sid : 'cli-test-session';
    }

    /** POST /api/bookmarks */
    public function createBookmarkAction(array $bodyParams): void
    {
        if (empty($bodyParams['region_code']) || empty($bodyParams['start_date']) || empty($bodyParams['end_date'])) {
            $this->sendErrorResponse(400, "필수 데이터(region_code, start_date, end_date)가 누락되었습니다.");
            return;
        }

        $sessionId  = $this->getSessionId();
        $regionCode = $bodyParams['region_code'];
        $startDate  = $bodyParams['start_date'];
        $endDate    = $bodyParams['end_date'];

        try {
            // 주입된 모델 사용
            $bookmarkId = $this->model->createBookmark($sessionId, $regionCode, $startDate, $endDate);

            $this->sendResponse(201, "즐겨찾기 등록이 완료되었습니다.", [
                'bookmark_id' => $bookmarkId,
                'session_id'  => $sessionId,
                'region_code' => $regionCode,
                'start_date'  => $startDate,
                'end_date'    => $endDate,
            ]);

        } catch (\Exception $e) {
            error_log("Controller Error in createBookmarkAction: " . $e->getMessage());
            if (strpos($e->getMessage(), "이미 동일한 기간과 지역이 즐겨찾기에 등록") !== false) {
                $this->sendErrorResponse(400, $e->getMessage());
            } else {
                $this->sendErrorResponse(500, "즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.");
            }
        }
    }

    /** DELETE /api/bookmarks/{bookmark_id} 또는 쿼리 튜플로 삭제 */
    public function deleteBookmarkAction(array $queryParams): void
    {
        $sessionId = $this->getSessionId();

        try {
            $deleted = false;

            if (!empty($queryParams['bookmark_id'])) {
                $deleted = $this->model->deleteBookmarkById($sessionId, (int)$queryParams['bookmark_id']);

            } elseif (
                !empty($queryParams['region_code']) &&
                !empty($queryParams['start_date'])  &&
                !empty($queryParams['end_date'])
            ) {
                $deleted = $this->model->deleteBookmarkByTuple(
                    $sessionId,
                    $queryParams['region_code'],
                    $queryParams['start_date'],
                    $queryParams['end_date']
                );

            } else {
                $this->sendErrorResponse(400, "bookmark_id 또는 (region_code, start_date, end_date) 중 하나는 반드시 제공되어야 합니다.");
                return;
            }

            if ($deleted) $this->sendResponse(200, "즐겨찾기 삭제가 완료되었습니다.", []);
            else          $this->sendErrorResponse(404, "삭제할 즐겨찾기 데이터를 찾을 수 없습니다.");

        } catch (\Exception $e) {
            error_log("Controller Error in deleteBookmarkAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /** GET /api/bookmarks */
    public function listBookmarksAction(array $queryParams = []): void
    {
        $sessionId = $this->getSessionId();

        try {
            $results = $this->model->getBookmarksBySession($sessionId);
            $this->sendResponse(200, "즐겨찾기 목록 조회가 완료되었습니다.", $results);

        } catch (\Exception $e) {
            error_log("Controller Error in listBookmarksAction: " . $e->getMessage());
            $this->sendErrorResponse(500, "즐겨찾기 목록 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }

    protected function sendResponse(int $status, string $message, array $data): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    }

    protected function sendErrorResponse(int $status, string $message): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode(['status' => $status, 'message' => $message]);
    }
}
