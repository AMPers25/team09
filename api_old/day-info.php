<?php
/**
 * File: api/day-info.php
 * Author: 강한나 (ob1hnk)
 * Description: 일일 날씨 상세 정보 API 엔드포인트 (day-info.html용)
 * URL: GET /api/day-info?region_code=090&date=2024-09-17
 */

require_once __DIR__ . '/../src/util/exception_handler.php';
require_once __DIR__ . '/../src/database/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\DailyController;

// HTTP Method 검증
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Query 파라미터 추출
$regionCode = $_GET['region_code'] ?? null;
$date = $_GET['date'] ?? null;

// 필수 파라미터 검증
if (empty($regionCode) || empty($date)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => '필수 파라미터가 누락되었습니다. region_code와 date가 필요합니다.']);
    exit;
}

try {
    // JWT 검증을 우회하기 위해 Authorization 헤더 설정 (임시)
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer day-info-token';
    }

    // DB 연결 및 Controller 생성
    $db = get_db_connection();
    $controller = new DailyController($db);

    // Controller 응답 캡처
    ob_start();
    $controller->getDailyWeatherAction($_GET);
    $controllerResponse = ob_get_clean();

    // Controller 응답 파싱
    $controllerData = json_decode($controllerResponse, true);

    if (!$controllerData) {
        error_log('day-info.php: Controller 응답 파싱 실패. Raw response: ' . $controllerResponse);
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => '데이터 조회 실패', 'details' => 'Controller 응답을 파싱할 수 없습니다.']);
        exit;
    }

    // Controller가 에러 응답을 보낸 경우
    if (isset($controllerData['status']) && $controllerData['status'] !== 200) {
        error_log('day-info.php: Controller 에러 응답 - Status: ' . $controllerData['status'] . ', Message: ' . ($controllerData['message'] ?? ''));
        http_response_code($controllerData['status']);
        header('Content-Type: application/json');
        echo json_encode(['error' => $controllerData['message'] ?? '데이터 조회 실패']);
        exit;
    }

    if (!isset($controllerData['data'])) {
        error_log('day-info.php: Controller 응답에 data 필드가 없음. Response: ' . json_encode($controllerData));
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => '데이터 조회 실패', 'details' => '응답에 데이터가 없습니다.']);
        exit;
    }

    $data = $controllerData['data'];

    // 날짜를 한국어 형식으로 변환
    $dateParts = explode('-', $data['date_id']);
    $dateLabel = $dateParts[0] . '년 ' . (int)$dateParts[1] . '월 ' . (int)$dateParts[2] . '일';

    // 날씨 상태 코드를 이름으로 변환 (status_code -> condition)
    $statusCodeMap = [
        '90' => '맑음',
        '91' => '구름조금',
        '92' => '구름많음',
        '93' => '흐림',
        '01' => '비',
        '05' => '눈'
    ];
    $statusCode = isset($data['rain']['status_code']) ? (string)$data['rain']['status_code'] : null;
    $condition = $statusCodeMap[$statusCode] ?? '맑음';

    // 프론트엔드 형식으로 변환
    $response = [
        'regionName' => $data['region_name'] ?? null,
        'dateLabel' => $dateLabel,
        'temperatureRange' => ($data['temperature']['min_temp'] ?? '--') . '° / ' . ($data['temperature']['max_temp'] ?? '--') . '°',
        'condition' => $condition,
        'icon' => null, // 프론트엔드에서 condition으로 자동 매핑
        'temperature' => [
            ['label' => '평균기온', 'value' => isset($data['temperature']['avg_temp']) ? round($data['temperature']['avg_temp'], 1) . '°' : '--'],
            ['label' => '최고기온', 'value' => isset($data['temperature']['max_temp']) ? round($data['temperature']['max_temp'], 1) . '°' : '--'],
            ['label' => '최저기온', 'value' => isset($data['temperature']['min_temp']) ? round($data['temperature']['min_temp'], 1) . '°' : '--'],
            ['label' => '일교차', 'value' => isset($data['temperature']['daily_temp_range']) ? round($data['temperature']['daily_temp_range'], 1) . '°' : '--'],
        ],
        'rain' => [
            ['label' => '일 강수량', 'value' => isset($data['rain']['daily_rainfall']) ? round($data['rain']['daily_rainfall'], 1) . 'mm' : '--'],
            ['label' => '평균 습도', 'value' => isset($data['rain']['humidity']) ? round($data['rain']['humidity'], 1) . '%' : '--'],
            ['label' => '평균 풍속', 'value' => isset($data['rain']['wind_speed']) ? round($data['rain']['wind_speed'], 1) . 'm/s' : '--'],
            ['label' => '평균 운량', 'value' => isset($data['rain']['cloud_cover']) ? round($data['rain']['cloud_cover'], 1) . '옥타' : '--'],
        ],
        'weatherAlert' => [],
        'airQuality' => [
            'title' => '미세먼지 농도 (PM 10)',
            'value' => isset($data['air_quality']['pm10']) ? (string)(int)$data['air_quality']['pm10'] : null,
            'meta' => []
        ]
    ];

    // weather_alert 데이터가 있으면 추가
    if (isset($data['weather_alert']['alert_type']) && $data['weather_alert']['alert_type']) {
        $response['weatherAlert'][] = [
            'label' => $data['weather_alert']['alert_type'],
            'value' => $data['weather_alert']['alert_time'] ?? '--'
        ];
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    error_log('API Error in day-info: ' . $e->getMessage());
    error_log('File: ' . $e->getFile() . ', Line: ' . $e->getLine());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => '서버 내부 오류가 발생했습니다.',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

