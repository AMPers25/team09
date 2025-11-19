<?php
/**
 * File: src/controller/TemperatureCalendarController.php
 * Author: 김연수 (sooooscode)
 * Description: 기능 2-2. 기온 캘린더 조회 Controller
 * Last Updated: 2025-11-19
 */

namespace App\Controller;

use App\Model\TemperatureCalendarModel;

class TemperatureCalendarController {
    private $model;

    public function __construct(TemperatureCalendarModel $model) {
        $this->model = $model;
    }

    /**
     * 월/지역별 일일 평균기온 목록을 조회하고 JSON으로 응답합니다.
     *
     * @param array $params 라우터로부터 전달받은 요청 파라미터 (regionCode, month)
     */
    public function getDailyCalendar(array $params) {
        // 1. 입력 파라미터 유효성 검증 및 추출
        $regionCode = $params['region_code'] ?? null;
        // int로 형변환하고 유효성 검증을 위해 filter_var 사용
        $month = filter_var($params['month'] ?? null, FILTER_VALIDATE_INT);

        // 필수 파라미터 누락 검사
        if (!$regionCode || $month === false) {
            $this->sendErrorResponse(400, "잘못된 요청입니다. regionCode, month 파라미터를 확인해주세요.");
            return;
        }

        // 월 범위 검사 (1~12)
        if ($month < 1 || $month > 12) {
            $this->sendErrorResponse(400, "월(month)은 1부터 12 사이의 값이어야 합니다.");
            return;
        }

        try {
            // 2. Model을 호출하여 데이터 조회
            $dailyTemps = $this->model->getDailyAverageTemperature($regionCode, 2024, $month);

            // 3. 성공 응답 전송
            $this->sendJsonResponse(200, [
                'status' => 'success',
                'message' => '일일 평균 기온 데이터를 성공적으로 조회했습니다.',
                'data' => $dailyTemps
            ]);

        } catch (\Exception $e) {
            // 4. Model에서 발생한 예외 처리 (DB 오류 등)
            // 에러 로깅은 Model에서 이미 수행했다고 가정합니다.
            $this->sendErrorResponse(500, $e->getMessage());
        }
    }

    /**
     * JSON 응답을 전송하고 스크립트 실행을 종료합니다.
     * @param int $statusCode HTTP 상태 코드
     * @param array $data 응답 데이터
     */
    private function sendJsonResponse(int $statusCode, array $data) {
        // 테스트 환경에서는 헤더 설정 중복 오류를 방지하기 위해 headers_sent() 체크를 추가했습니다.
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        // 실제 환경에서는 exit;를 통해 즉시 종료
        // exit;
    }

    /**
     * JSON 오류 응답을 전송합니다.
     * @param int $statusCode HTTP 상태 코드
     * @param string $message 오류 메시지
     */
    private function sendErrorResponse(int $statusCode, string $message) {
        $this->sendJsonResponse($statusCode, [
            'status' => 'error',
            'message' => $message
        ]);
    }
}