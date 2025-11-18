<?php
/**
 * File: test-db-connection.php
 * Description: DB 연결 테스트 스크립트
 * URL: http://localhost/team09/test-db-connection.php
 */

require_once __DIR__ . '/src/database/db_connect.php';
require_once __DIR__ . '/src/util/exception_handler.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>DB 연결 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>DB 연결 테스트</h1>
    
    <?php
    try {
        echo "<h2>1. DB 설정 확인</h2>";
        echo "<pre>";
        echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
        echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
        echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
        echo "DB_PASS: " . (defined('DB_PASS') ? (strlen(DB_PASS) > 0 ? '***' : 'EMPTY') : 'NOT DEFINED') . "\n";
        echo "</pre>";
        
        echo "<h2>2. DB 연결 시도</h2>";
        $db = get_db_connection();
        echo "<p class='success'>✅ DB 연결 성공!</p>";
        
        echo "<h2>3. 간단한 쿼리 테스트</h2>";
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "<p class='success'>✅ 쿼리 실행 성공: " . $result['test'] . "</p>";
        
        echo "<h2>4. Region 테이블 확인</h2>";
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM Region LIMIT 1");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ Region 테이블 접근 성공: " . $result['count'] . "개 레코드</p>";
        } catch (\PDOException $e) {
            echo "<p class='error'>❌ Region 테이블 접근 실패: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>5. DateDim 테이블 확인</h2>";
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM DateDim LIMIT 1");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ DateDim 테이블 접근 성공: " . $result['count'] . "개 레코드</p>";
        } catch (\PDOException $e) {
            echo "<p class='error'>❌ DateDim 테이블 접근 실패: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>6. Temperature 테이블 확인</h2>";
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM Temperature LIMIT 1");
            $result = $stmt->fetch();
            echo "<p class='success'>✅ Temperature 테이블 접근 성공: " . $result['count'] . "개 레코드</p>";
        } catch (\PDOException $e) {
            echo "<p class='error'>❌ Temperature 테이블 접근 실패: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>7. DailyModel 테스트</h2>";
        require_once __DIR__ . '/vendor/autoload.php';
        use App\Model\DailyModel;
        
        $model = new DailyModel($db);
        $testResult = $model->getDailyWeather('090', '2024-09-17');
        
        if ($testResult === null) {
            echo "<p class='error'>⚠️ 데이터 없음 (region_code=090, date=2024-09-17)</p>";
        } else {
            echo "<p class='success'>✅ DailyModel.getDailyWeather() 실행 성공</p>";
            echo "<pre>" . print_r($testResult, true) . "</pre>";
        }
        
    } catch (\PDOException $e) {
        echo "<p class='error'>❌ DB 연결 실패</p>";
        echo "<pre>에러 메시지: " . $e->getMessage() . "\n";
        echo "에러 코드: " . $e->getCode() . "\n";
        echo "파일: " . $e->getFile() . "\n";
        echo "라인: " . $e->getLine() . "</pre>";
    } catch (\Exception $e) {
        echo "<p class='error'>❌ 오류 발생</p>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }
    ?>
    
    <hr>
    <p><a href="test-db-connection.php">새로고침</a></p>
</body>
</html>

