<?php
/**
 * 파일: src/db_connect.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-10-08
 * 역할: 데이터베이스 연결 객체를 생성하고 반환 (PHP PDO 사용)
 * 요구사항: config/db_config.php에 정의된 상수 필요
 */

// 1. DB 설정 파일 로드
require_once __DIR__ . '/../config/db_config.php';

/**
 * 프로젝트 데이터베이스 연결 객체(PDO)를 생성하고 반환
 *
 * @return PDO 성공 시 PDO 객체 반환
 * @throws PDOException 연결 실패 시 예외 발생
 */
function get_db_connection(): PDO
{
    // Data Source Name 설정
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    // PDO 옵션 설정 (보안 및 에러 처리 설정 포함)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        
        // 상세 오류 메시지는 서버 로그에만 기록합니다.
        // error_log() 함수를 사용하면 PHP 에러 로그 파일(일반적으로 php_error.log)에 기록됩니다.
        error_log("CRITICAL DB CONNECTION FAILED: " 
            . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());

        // HTTP 500 Internal Server Error 상태 코드를 설정
        http_response_code(500);

        // 사용자에게는 일반적인 서비스 장애 메시지만 출력하고 스크립트 종료
        // 이 오류는 어플리케이션이 동작할 수 없는 치명적인 상태입니다.
        die("<h1>Service Unavailable (500)</h1>" 
            . "<p>A critical internal server error occurred. We are currently unable to process your request.</p>"
            . "<p>Please contact the system administrator if the problem persists. (관리자: 서버 로그 확인 필수)</p>");
    }
}
