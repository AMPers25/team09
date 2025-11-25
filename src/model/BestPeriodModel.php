<?php

/**
 * File: src/model/BestPeriodModel.php
 * Author: 김연수
 * Description: 기능 3-2. 여행 기간 추천 Model
 * Last Updated: 2025-11-08
 */

namespace App\Model;

class BestPeriodModel {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * 기능 3-2: 여행 적합 기간 추천 (주간 Top 5)
     * 요구사항: Aggregates (Average) based on Complex Grouping, Ranking
     * @param string $regionCode 조회할 지역 코드 (e.g., '108')
     * @return array Top 5 주간 추천 결과 목록 (start_date, end_date, avg_ti_score, rank)
     */
    public function getBestWeekRanking(string $regionCode): array {
        // 복합 그룹핑(주간)을 통한 평균 TI 점수 랭킹 분석 SQL
        $sql = "
            SELECT
                ranking_data.week_start_date AS start_date,
                DATE_ADD(ranking_data.week_start_date, INTERVAL 6 DAY) AS end_date,
                ranking_data.avg_ti_score,
                ranking_data.ti_rank AS `rank`
            FROM
                (
                    SELECT
                        -- Aggregates: 주간 평균 여행 적합 지수 계산
                        AVG(TI.travel_index_score) AS avg_ti_score,
                        
                        -- 복합 그룹핑: 해당 주의 시작 날짜 (월요일) 계산
                        DATE_SUB(TI.date_id, INTERVAL DAYOFWEEK(TI.date_id) - 2 DAY) AS week_start_date,
                        
                        -- Ranking: 주간 평균을 기준으로 순위 계산
                        RANK() OVER (ORDER BY AVG(TI.travel_index_score) DESC) AS ti_rank
                    FROM TravelIndex TI
                    WHERE TI.region_code = :regionCode
                      AND TI.date_id BETWEEN DATE('2024-01-01') AND DATE('2024-12-31')
                    GROUP BY
                        TI.region_code, DATE_SUB(TI.date_id, INTERVAL DAYOFWEEK(TI.date_id) - 2 DAY)
                ) AS ranking_data
            WHERE ranking_data.ti_rank <= 5
            ORDER BY ranking_data.ti_rank ASC;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':regionCode', $regionCode, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getBestWeekRanking: " . $e->getMessage());
            // Controller에서 500 응답 처리를 위해 예외 던지기
            throw new \Exception("주간 랭킹 분석 중 서버 내부 오류가 발생했습니다.");
        }
    }
}