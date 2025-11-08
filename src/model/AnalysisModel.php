<?php
/**
 * File: src/model/AnalysisModel.php
 * Author: 김연수
 * Description: 고급 분석 기능을 위한 Model 클래스. (3-1, 3-2, 3-3 기능 포함)
 * Last Updated: 2025-11-08
 */

namespace App\Model;

class AnalysisModel {
    private $db; // DB 연결 객체 (PDO 등)

    /**
     * 생성자: DB 연결 객체를 주입받아 초기화합니다.
     * @param object $db_connection PDO 객체 또는 DB 래퍼 인스턴스
     */
    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * 기능 3-1: PM10 클린 연속 기간 추천 (Top 5)
     * 요구사항: Windowing, Ranking (Gap-and-Island)
     * @param string $regionCode 조회할 지역 코드
     * @return array Top 5 클린 기간 목록 (start_date, end_date, streak_days, pm10_avg, rank)
     */
    public function getCleanStreakRanking(string $regionCode): array
    {
        // PM10 좋음 기준 (<= 30)을 기반으로 Gap-and-Island 분석을 통해 연속 기간 계산
        $sql = "
            WITH
            -- 1) 범위/지역/좋음 조건 필터 (PM10 <= 30)
            filt AS (
              SELECT
                date_id,
                pm10
              FROM AirQuality
              WHERE region_code = :regionCode    -- 사용자가 고른 지역만 필터링
                AND pm10 BETWEEN 0 AND 30         -- PM10 “좋음”인 날만 필터링
                -- 분석 기간 2024년 전체로 고정
                AND date_id BETWEEN DATE('2024-01-01') AND DATE('2024-12-31') 
            ),
            -- 2) 연속성 판별용 키 (island_id) 계산: DATE_SUB(date_id, INTERVAL rn DAY)가 같으면 연속된 날짜
            seq AS (
              SELECT
                date_id,
                pm10,
                -- 연속성을 판단하기 위한 순번
                ROW_NUMBER() OVER (ORDER BY date_id) AS rn,
                -- island_id 계산 (연속된 날짜는 이 값이 동일)
                DATE_SUB(date_id, INTERVAL ROW_NUMBER() OVER (ORDER BY date_id) DAY) AS island_id
              FROM filt
            ),
            -- 3) 연속 구간 집계 및 통계 계산
            CleanStreaks AS (
              SELECT
                MIN(date_id) AS start_date,
                MAX(date_id) AS end_date,
                COUNT(date_id) AS streak_days,         -- 연속 일수 (랭킹 기준)
                AVG(pm10) AS pm10_avg                  -- 기간 평균 PM10
              FROM seq
              GROUP BY island_id
            )
            -- 4) 최종적으로 연속 일수를 기준으로 랭킹 매기기 (Top 5 추출)
            SELECT
              start_date,
              end_date,
              streak_days,
              pm10_avg,
              RANK() OVER (ORDER BY streak_days DESC, start_date ASC) AS rank
            FROM CleanStreaks
            ORDER BY
              rank ASC
            LIMIT 5;
        ";

        try {
            // 1. Prepared Statement 준비
            $stmt = $this->db->prepare($sql);

            // 2. 바인딩 (사용자 입력: region_code)
            // PDO::PARAM_STR은 문자열 타입 바인딩에 안전합니다.
            $stmt->bindParam(':regionCode', $regionCode, PDO::PARAM_STR);

            // 3. 실행 및 결과 반환
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // 데이터베이스 오류 처리 및 로깅
            error_log("DB Error in getCleanStreakRanking: " . $e->getMessage());
            // Controller에서 500 응답을 처리하도록 예외 던지기
            throw new Exception("PM10 클린 기간 분석 중 서버 내부 오류가 발생했습니다.");
        }
    }

}