<?php
/**
 * File: index.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-11-08
 * Role: 중앙 집중식 요청 처리 및 라우팅 진입점
 */

// 1. Autoloading 및 환경 설정
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload
// 에러 보고 및 세션 시작 등 초기 환경 설정

// 2. 필수 서비스 초기화
// DB 연결 초기화 (연결 객체 $db 생성)
$db = \App\Database\db_connect::get_db_connection();

// 3. 라우터 인스턴스 생성 및 라우트 등록
$router = new \App\Core\Router($db); // (가상의 라우터 클래스)
$router->registerRoute('GET', '/api/calendar/{region}/{year}/{month}',
    \App\Controller\TemperatureCalendarController::class, 'getDailyCalendar');

$router->registerRoute('GET', '/api/analysis/cleanday/{region}/{period}',
    \App\Controller\CleanDayController::class, 'getCleanDayPeriod');

// 4. 요청 처리 시작
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

?>
