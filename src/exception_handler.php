<?php
/**
 * File: src/exception_handler.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-10-
 * Role: PHP의 전역 예외 처리기(Exception Handler) 및 오류 처리기 등록
 * 모든 예외와 오류를 안전하게 로그에 기록하고 사용자에게는 일반 오류 메시지를 제공하여 보안 유지
 */

// 이 파일은 프로젝트의 모든 PHP 파일에서 가장 먼저 require 되어야 함

// 1. 전역 예외 처리 함수 (Uncaught Exception 및 Throwable 처리)
function custom_exception_handler(Throwable $exception)
{
    // 1. 상세 오류를 서버 로그에 기록 (보안 확보 및 디버깅 정보 기록)
    error_log("UNCAUGHT EXCEPTION: " 
        . $exception->getMessage() 
        . " | File: " . $exception->getFile() 
        . " | Line: " . $exception->getLine() 
        . " | Trace: " . $exception->getTraceAsString());

    // 2. HTTP 500 상태 코드 설정
    http_response_code(500);

    // 3. 요청이 AJAX인지 확인하여 응답 형식 결정 (분석 페이지 대응)
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($is_ajax) {
        // A. AJAX 요청인 경우: JSON 표준 에러 응답 반환
        header('Content-Type: application/json');
        
        // 프로젝트의 500 JSON 실패 명세 준수
        echo json_encode([
            "status" => 500,
            "message" => "서버 내부 오류입니다. (Internal Server Error)"
        ]);
    } else {
        // B. 일반적인 페이지 요청인 경우: HTML 에러 페이지 출력
        die("<!DOCTYPE html><title>500 Internal Error</title>"
            . "<h1 style='color: red;'>500 Internal Server Error</h1>" 
            . "<p>An unexpected error occurred. Please try again later.</p>"
            . "<p>Administrator: Check the server error log for technical details.</p>");
    }

    // 스크립트 실행 종료
    exit;
}

// 2. PHP 오류 핸들러 (E_WARNING, E_NOTICE 등)
// Notice나 Warning 같은 사소한 오류도 외부에 노출되는 것을 막기 위해 정의합니다.
function custom_error_handler($errno, $errstr, $errfile, $errline)
{
    // 현재 error_reporting 설정에서 무시된 오류는 무시합니다.
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // 상세 오류를 서버 로그에 기록 (사용자에게는 보이지 않음)
    error_log("PHP ERROR [$errno]: $errstr in $errfile on line $errline");
    
    // PHP의 표준 오류 핸들러를 실행하지 않도록 하여 사용자에게 오류가 노출되는 것을 막습니다.
    return true; 
}

// 3. 전역 핸들러 설정
set_exception_handler('custom_exception_handler');
set_error_handler('custom_error_handler');

// 개발이 완료된 후에도 화면에 상세 오류가 노출되지 않도록 display_errors를 0으로 설정하는 것이 보안상 안전합니다.
ini_set('display_errors', 0); 
error_reporting(E_ALL);


?>
