<?php
/**
 * File: src/sql_wrapper.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-10-09
 * Role: Prepared Statement 기반의 안전한 쿼리 실행 래퍼 함수를 제공합니다.
 */

// DB 연결 함수가 정의된 파일을 로드합니다.
require_once __DIR__ . '/db_connect.php';

/**
 * SELECT 쿼리를 실행하고 모든 결과 레코드를 가져옵니다.
 * * @param string $sql 실행할 SQL 쿼리 문자열 (플레이스홀더 '?' 또는 ':name' 사용)
 * @param array $params Prepared Statement에 바인딩할 파라미터 배열
 * @return array 쿼리 결과 (연관 배열)
 */
function fetch_data(string $sql, array $params = []): array
{
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        // 쿼리 실행 오류: 서버 로그에 기록하고 일반 오류 반환
        error_log("SQL Fetch Error: " . $e->getMessage());
        // 사용자에게는 빈 배열이나 오류 메시지를 반환하여 상위 함수에서 처리하도록 유도
        return []; 
    }
}

/**
 * INSERT, UPDATE, DELETE 쿼리를 실행하고 영향을 받은 행의 수를 반환
 * * @param string $sql 실행할 SQL 쿼리 문자열
 * @param array $params Prepared Statement에 바인딩할 파라미터 배열
 * @return int 영향을 받은 행의 수
 */
function execute_query(string $sql, array $params = []): int
{
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (\PDOException $e) {
        error_log("SQL Execute Error: " . $e->getMessage());
        // 실패 시 0 또는 -1 (오류)를 반환하여 호출 함수에서 실패 처리
        return 0; 
    }
}
