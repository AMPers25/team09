<?php
/**
 * File: config/db_config.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-10-08
 * role: 데이터베이스 접속 정보를 상수로 정의
 * Point: 프로젝트 요구사항 3-(1)에 따라 DB_USER, DB_PASS, DB_NAME은 팀 번호에 맞게 설정
 */

// 1. 데이터베이스 호스트 주소
// macOS XAMPP에서는 localhost 대신 127.0.0.1 사용 권장
define("DB_HOST", "127.0.0.1"); // XAMPP 환경의 기본값

// 2. 데이터베이스 사용자 ID
define("DB_USER", "team09");

// 3. 데이터베이스 비밀번호
define("DB_PASS", "team09");

// 4. 데이터베이스 이름
define("DB_NAME", "team09");

// 5. 데이터베이스 포트
define("DB_PORT", "3306");

?>
