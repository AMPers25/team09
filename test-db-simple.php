<?php
/**
 * File: test-db-simple.php
 * Description: 간단한 DB 연결 테스트 (에러 표시 활성화)
 */

// 에러 표시 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DB 연결 테스트 (간단 버전)</h1>";

// 1단계: 설정 파일 로드
echo "<h2>1. 설정 파일 로드</h2>";
try {
    require_once __DIR__ . '/config/db_config.php';
    echo "✅ config/db_config.php 로드 성공<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
} catch (\Exception $e) {
    echo "❌ 설정 파일 로드 실패: " . $e->getMessage() . "<br>";
    exit;
}

// 2단계: DB 연결 함수 로드
echo "<h2>2. DB 연결 함수 로드</h2>";
try {
    require_once __DIR__ . '/src/database/db_connect.php';
    echo "✅ db_connect.php 로드 성공<br>";
} catch (\Exception $e) {
    echo "❌ db_connect.php 로드 실패: " . $e->getMessage() . "<br>";
    exit;
}

// 3단계: DB 연결 시도 (직접 PDO로 시도해서 에러 확인)
echo "<h2>3. DB 연결 시도</h2>";
try {
    // 직접 PDO 연결 시도 (에러 메시지 확인용)
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    echo "연결 정보: $dsn<br>";
    echo "사용자: " . DB_USER . "<br>";
    
    $testDb = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✅ 직접 PDO 연결 성공!<br>";
    
    // 이제 get_db_connection() 사용
    $db = get_db_connection();
    echo "✅ get_db_connection() 성공!<br>";
    
    // 4단계: 간단한 쿼리
    echo "<h2>4. 쿼리 테스트</h2>";
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ 쿼리 실행 성공: " . $result['test'] . "<br>";
    
    // 5단계: 테이블 확인
    echo "<h2>5. 테이블 확인</h2>";
    $tables = ['Region', 'DateDim', 'Temperature', 'Rain', 'AirQuality', 'WeatherAlert'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM `$table` LIMIT 1");
            $result = $stmt->fetch();
            echo "✅ $table: " . $result['count'] . "개 레코드<br>";
        } catch (\PDOException $e) {
            echo "❌ $table: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (\PDOException $e) {
    echo "❌ DB 연결 실패<br>";
    echo "<strong>에러 메시지:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>에러 코드:</strong> " . $e->getCode() . "<br>";
    echo "<strong>SQLSTATE:</strong> " . ($e->errorInfo[0] ?? 'N/A') . "<br>";
    
    // 일반적인 해결 방법 제시
    echo "<h3>가능한 해결 방법:</h3>";
    echo "<ul>";
    echo "<li>XAMPP의 MySQL/MariaDB가 실행 중인지 확인</li>";
    echo "<li>phpMyAdmin에서 데이터베이스 'team09'가 생성되어 있는지 확인</li>";
    echo "<li>사용자 'team09'가 생성되어 있고 권한이 있는지 확인</li>";
    echo "<li>비밀번호가 'team09'인지 확인</li>";
    echo "</ul>";
} catch (\Exception $e) {
    echo "❌ 오류 발생<br>";
    echo "<strong>에러 메시지:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>파일:</strong> " . $e->getFile() . "<br>";
    echo "<strong>라인:</strong> " . $e->getLine() . "<br>";
}

