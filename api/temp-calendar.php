<?php
/**
 * File: api/temp-calendar.php
 * Author: 강한나 (ob1hnk)
 * Description: 기온 캘린더 API 엔드포인트
 * URL: GET /api/temp-calendar?region_code=090&month=09
 */

require_once __DIR__ . '/../src/util/exception_handler.php';
require_once __DIR__ . '/../src/database/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\TemperatureCalendarController;
use App\Model\TemperatureCalendarModel;

// HTTP Method 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Query 파라미터 추출
$regionCode = $_GET['region_code'] ?? null;
$monthParam = $_GET['month'] ?? null;

// 필수 파라미터 검증
if (empty($regionCode) || empty($monthParam)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => '필수 파라미터가 누락되었습니다. region_code와 month가 필요합니다.']);
    exit;
}

// month를 정수로 변환
$month = (int)$monthParam;
$year = 2024; // 프론트엔드에서 FIXED_YEAR = 2024로 고정

// 월 범위 검증
if ($month < 1 || $month > 12) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => '월(month)은 1부터 12 사이의 값이어야 합니다.']);
    exit;
}

try {
    // DB 연결 및 Model/Controller 생성
    $db = get_db_connection();
    $model = new TemperatureCalendarModel($db);
    $controller = new TemperatureCalendarController($model);

    // Controller 호출 (파라미터 변환: region_code -> regionCode)
    $params = [
        'regionCode' => $regionCode,
        'year' => $year,
        'month' => $month
    ];

    // Controller의 응답을 캡처하기 위해 출력 버퍼 사용
    ob_start();
    $controller->getDailyCalendar($params);
    $controllerResponse = ob_get_clean();

    // Controller 응답 파싱
    $controllerData = json_decode($controllerResponse, true);

    if (!$controllerData) {
        error_log('Controller 응답 파싱 실패: ' . $controllerResponse);
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Controller 응답을 파싱할 수 없습니다.', 'raw' => $controllerResponse]);
        exit;
    }

    // Controller가 에러 응답을 보낸 경우
    if (isset($controllerData['status']) && $controllerData['status'] === 'error') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => $controllerData['message'] ?? '데이터 조회 실패']);
        exit;
    }

    // 성공 응답이 아닌 경우
    if (!isset($controllerData['status']) || $controllerData['status'] !== 'success') {
        error_log('Controller 응답 형식 오류: ' . json_encode($controllerData));
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => '예상하지 못한 응답 형식입니다.', 'data' => $controllerData]);
        exit;
    }

    // 백엔드 데이터를 프론트엔드 형식으로 변환
    $dailyTemps = $controllerData['data'] ?? [];
    
    // 날짜별 데이터를 days 배열로 변환
    $days = [];
    $minTemps = [];
    $maxTemps = [];
    
        foreach ($dailyTemps as $tempData) {
            $dateId = $tempData['date_id'];
            $avgTemp = (float)($tempData['avg_temp'] ?? 0);
            $minTemp = isset($tempData['min_temp']) ? (float)$tempData['min_temp'] : ($avgTemp - 3);
            $maxTemp = isset($tempData['max_temp']) ? (float)$tempData['max_temp'] : ($avgTemp + 3);
            
            // date_id에서 일(day) 추출 (YYYY-MM-DD 형식)
            $dateParts = explode('-', $dateId);
            $day = (int)($dateParts[2] ?? 0);
            
            if ($day > 0) {
            
            $days[] = [
                'day' => $day,
                'minTemp' => (int)round($minTemp),
                'maxTemp' => (int)round($maxTemp),
                'weather' => '맑음', // TODO: 실제 날씨 데이터가 있다면 사용
                'isHoliday' => false // TODO: 공휴일 체크 로직 추가
            ];
            
            $minTemps[] = $minTemp;
            $maxTemps[] = $maxTemp;
        }
    }
    
    // 월 통계 계산
    $avgMinTemp = !empty($minTemps) ? round(array_sum($minTemps) / count($minTemps)) : 0;
    $avgMaxTemp = !empty($maxTemps) ? round(array_sum($maxTemps) / count($maxTemps)) : 0;
    $monthMinTemp = !empty($minTemps) ? (int)round(min($minTemps)) : 0;
    $monthMaxTemp = !empty($maxTemps) ? (int)round(max($maxTemps)) : 0;
    
    // 프론트엔드가 기대하는 형식으로 응답
    $response = [
        'year' => $year,
        'month' => $month,
        'region_code' => $regionCode,
        'avgMinTemp' => $avgMinTemp,
        'avgMaxTemp' => $avgMaxTemp,
        'monthMinTemp' => $monthMinTemp,
        'monthMaxTemp' => $monthMaxTemp,
        'days' => $days,
        'weeklyAverages' => [] // 프론트엔드에서 자동 계산
    ];
    
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    error_log('API Error in temp-calendar: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => '서버 내부 오류가 발생했습니다.',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    exit;
}

