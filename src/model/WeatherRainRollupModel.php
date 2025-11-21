<?php
/**
 * File: src/model/WeatherRainRollupModel.php
 * Author: 황혜린
 * Description: 기능 2-3. 특정 지역/기간의 일별 강수량 + 월 합계(ROLLUP) 조회 Model
 * Last Updated: 2025-11-17
 */

namespace App\Model;

class WeatherRainRollupModel
{
    /** @var \PDO */
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * 특정 지역/기간의 일별 강수량과 월 합계(ROLLUP)를 반환
     *
     * 반환 스키마(Data[item]):
     *  - level       : "DAY" | "MONTH_TOTAL"
     *  - date_id     : "YYYY-MM-DD" | null (월 합계는 null)
     *  - ym          : "YYYY-MM"
     *  - rainfall_mm : float
     *
     * @param string $regionCode
     * @param string $fromDate (YYYY-MM-DD)
     * @param string $toDate   (YYYY-MM-DD)
     * @return array
     * @throws \Exception
     */
    public function getRainRollup(string $regionCode, string $fromDate, string $toDate): array
    {
        // ROLLUP로 일/월 합계를 함께 가져오고, 그랜드 토탈은 제거(HAVING GROUPING(ym)=0)
        $sql = "
            SELECT
                CASE WHEN GROUPING(s.date_id) = 0 THEN 'DAY' ELSE 'MONTH_TOTAL' END AS level,
                CASE WHEN GROUPING(s.date_id) = 0 THEN DATE_FORMAT(s.date_id, '%Y-%m-%d') ELSE NULL END AS date_id,
                s.ym AS ym,
                ROUND(SUM(s.daily_rainfall), 1) AS rainfall_mm
            FROM (
                SELECT
                    region_code,
                    date_id,
                    COALESCE(daily_rainfall, 0) AS daily_rainfall,
                    DATE_FORMAT(date_id, '%Y-%m') AS ym
                FROM Rain
                WHERE region_code = :region_code
                  AND date_id BETWEEN :from_date AND :to_date
            ) s
            GROUP BY s.ym, s.date_id WITH ROLLUP
            HAVING GROUPING(s.ym) = 0
            ORDER BY s.ym ASC, GROUPING(s.date_id) ASC, s.date_id ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindValue(':from_date',   $fromDate,   \PDO::PARAM_STR);
            $stmt->bindValue(':to_date',     $toDate,     \PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 타입/키 정리 (rainfall_mm은 float로 캐스팅)
            foreach ($rows as &$r) {
                // DB 드라이버에 따라 문자열로 올 수 있으니 안전하게 변환
                $r['rainfall_mm'] = isset($r['rainfall_mm'])
                    ? (float) round((float)$r['rainfall_mm'], 1)
                    : 0.0;
                // level, date_id, ym 키 보장
                $r['level']   = $r['level'] ?? 'DAY';
                $r['date_id'] = $r['date_id'] ?? null;
                $r['ym']      = $r['ym'] ?? null;
            }
            unset($r);

            return $rows;

        } catch (\PDOException $e) {
            error_log("DB Error in getRainRollup: " . $e->getMessage());
            throw new \Exception("강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
