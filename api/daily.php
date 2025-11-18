<?php
/**
 * File: api/daily.php
 * Author: 강한나 (ob1hnk)
 * Description: 일일 날씨 통합 조회 API 엔드포인트
 * URL: GET /api/daily?region_code=090&date=2024-09-17
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

try {
    // DB 연결 및 Controller 생성
    $db = get_db_connection();
    $controller = new DailyController($db);

    // Controller 호출
    $controller->getDailyWeatherAction($_GET);

} catch (\Exception $e) {
    error_log('API Error in daily: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 500,
        'message' => '서버 내부 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

