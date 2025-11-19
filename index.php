<?php
/**
 * File: index.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-11-08
 * Role: 중앙 집중식 요청 처리 및 라우팅 진입점
 */

use App\Core\Router;

// Composer Autoloading (모든 클래스 경로 관리를 위해 필수)
require_once __DIR__ . '/vendor/autoload.php';
// 임시: Composer가 없다면 Router 클래스를 직접 로드
//require_once __DIR__ . '/src/core/Router.php';

// DB 연결
require_once __DIR__ . '/src/database/db_connect.php';

// PHP 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. DB 연결 객체 생성
$db = null;
try {
    $db = get_db_connection();
} catch (\Exception $e) {
    // db_connect.php 내부에서 치명적 오류 처리 및 종료
    exit;
}

// 2. Router 인스턴스 생성 및 DB 연결 주입 (DI)
$router = new Router($db);

// 3. 라우트 정의 파일 로드 및 등록
// api.php 파일은 함수를 반환하며, 이 함수는 $router를 인자로 받아 라우트를 등록합니다.
$routesLoader = require_once __DIR__ . '/routes/api.php';

// 반환된 함수($routesLoader)를 실행하면서 $router 객체를 인자로 넘겨줍니다.
// 이렇게 하면 $router 변수가 api.php 파일 내부의 스코프로 전달되어 "Undefined variable: $router" 오류가 해결됩니다.
if (is_callable($routesLoader)) {
    $routesLoader($router);
}

// 4. 요청 처리 시작 (URI 분석 및 Controller 연결)
// $_SERVER['REQUEST_URI']는 전체 URL 경로를, $_SERVER['REQUEST_METHOD']는 GET/POST 등을 담고 있습니다.
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

// 스크립트 실행 종료는 dispatch 내부에서 처리
?>
