<?php
/**
 * File: src/model/BestRegionModel.php
 * Author: 김연수
 * Description: 기능 3-3. 여행 지역 추천 Model
 * Last Updated: 2025-11-08
 */

namespace App\Model;

class BestRegionModel {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * 기능 3-3: 지역별 여행 적합 지역 추천 (Top 5)
     * 요구사항: Ranking (Windowing), Aggregates (Complex Grouping)
     * @param string $startDate 분석 시작일 (YYYY-MM-DD)
     * @param string $endDate 분석 종료일 (YYYY-MM-DD)
     * @return array Top 5 지역 목록 (region_code, region_name, province, avg_ti_score, rank)
     */
    public function getBestRegionRanking(string $startDate, string $endDate): array {
        $sql = "
            SELECT
                ranking_data.region_code,
                R.region_name,
                R.province, 
                ranking_data.avg_ti_score,
                ranking_data.ti_rank AS rank
            FROM
                (
                    SELECT
                        TI.region_code,
                        AVG(TI.travel_index_score) AS avg_ti_score,
                        RANK() OVER (ORDER BY AVG(TI.travel_index_score) DESC) AS ti_rank
                    FROM TravelIndex TI
                    WHERE TI.date_id BETWEEN :startDate AND :endDate
                    GROUP BY TI.region_code
                ) AS ranking_data
            JOIN Region R ON ranking_data.region_code = R.region_code
            WHERE ranking_data.ti_rank <= 5
            ORDER BY ranking_data.ti_rank ASC;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':startDate', $startDate, \PDO::PARAM_STR);
            $stmt->bindParam(':endDate', $endDate, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("DB Error in getBestRegionRanking: " . $e->getMessage());
            throw new \Exception("지역 랭킹 분석 중 서버 내부 오류가 발생했습니다.");
        }
    }
}