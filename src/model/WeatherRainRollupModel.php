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
     * 특정 지역/기간의 일별 강수량, 주 평균, 월 합계(ROLLUP)를 반환
     *
     * 반환 스키마(Data[item]):
     *  - level       : "DAY" | "WEEK_AVG" | "MONTH_TOTAL"
     *  - date_id     : "YYYY-MM-DD" | null (주/월 집계는 null)
     *  - ym          : "YYYY-MM"
     *  - week_start  : "YYYY-MM-DD" | null (주 시작일, 주 평균일 때만)
     *  - rainfall_mm : float
     *  - region_name : string | null (DAY 레벨에서만)
     *  - is_holiday  : int | null (DAY 레벨에서만)
     *  - status_name : string | null (DAY 레벨에서만)
     *
     * @param string $regionCode
     * @param string $fromDate (YYYY-MM-DD)
     * @param string $toDate   (YYYY-MM-DD)
     * @return array
     * @throws \Exception
     */
    public function getRainRollup(string $regionCode, string $fromDate, string $toDate): array
    {
        // ROLLUP으로 일별/주별 평균/월별 합계를 함께 가져옴
        // 주 시작일은 월요일로 계산 (DAYOFWEEK: 1=일요일, 2=월요일)
        $sql = "
            SELECT
                CASE 
                    WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 0 THEN 'DAY'
                    WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN 'WEEK_AVG'
                    WHEN GROUPING(s.week_start) = 1 AND GROUPING(s.date_id) = 1 THEN 'MONTH_TOTAL'
                    ELSE 'UNKNOWN'
                END AS level,
                CASE 
                    WHEN GROUPING(s.date_id) = 0 THEN DATE_FORMAT(s.date_id, '%Y-%m-%d')
                    ELSE NULL
                END AS date_id,
                MAX(s.ym) AS ym,
                CASE 
                    WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN DATE_FORMAT(s.week_start, '%Y-%m-%d')
                    ELSE NULL
                END AS week_start,
                CASE 
                    WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN ROUND(AVG(s.daily_rainfall), 1)
                    WHEN GROUPING(s.week_start) = 1 AND GROUPING(s.date_id) = 1 THEN ROUND(AVG(s.daily_rainfall), 1)
                    ELSE ROUND(SUM(s.daily_rainfall), 1)
                END AS rainfall_mm,
                CASE 
                    WHEN GROUPING(s.date_id) = 0 THEN MAX(s.region_name)
                    ELSE NULL
                END AS region_name,
                CASE 
                    WHEN GROUPING(s.date_id) = 0 THEN MAX(s.is_holiday)
                    ELSE NULL
                END AS is_holiday,
                CASE 
                    WHEN GROUPING(s.date_id) = 0 THEN MAX(s.status_name)
                    ELSE NULL
                END AS status_name
            FROM (
                SELECT
                    rn.region_code,
                    rn.date_id,
                    COALESCE(rn.daily_rainfall, 0) AS daily_rainfall,
                    DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
                    -- 주 시작일 계산: 월요일 기준 (일요일은 전 주의 월요일)
                    CASE 
                        WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)  -- 일요일: 6일 전 (월요일)
                        ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)  -- 월~토: 정상 계산
                    END AS week_start,
                    r.region_name,
                    d.is_holiday,
                    ws.status_name
                FROM Rain rn
                JOIN Region r ON r.region_code = rn.region_code
                JOIN DateDim d ON d.date_id = rn.date_id
                LEFT JOIN WeatherStatusDim ws ON ws.status_code = rn.status_code
                WHERE rn.region_code = :region_code
                  AND (
                      -- 해당 월의 날짜
                      rn.date_id BETWEEN :from_date AND :to_date
                      OR
                      -- 해당 월의 날짜가 속한 주의 전체 날짜 (전달/다음달 포함)
                      CASE 
                          WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
                          ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
                      END IN (
                          SELECT DISTINCT 
                              CASE 
                                  WHEN DAYOFWEEK(d2.date_id) = 1 THEN DATE_SUB(d2.date_id, INTERVAL 6 DAY)
                                  ELSE DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
                              END
                          FROM DateDim d2
                          WHERE d2.date_id BETWEEN :from_date_sub AND :to_date_sub
                      )
                  )
            ) s
            GROUP BY s.week_start, s.date_id WITH ROLLUP
            HAVING 
                -- 월별 필터링: 
                -- 1. DAY 레벨 (GROUPING(s.date_id) = 0): 요청한 월의 날짜만 포함
                -- 2. WEEK_AVG/MONTH_TOTAL 레벨 (GROUPING(s.date_id) = 1): 모두 포함 (필터링하지 않음)
                GROUPING(s.date_id) = 1 OR DATE_FORMAT(s.date_id, '%Y-%m') = :request_month
            ORDER BY GROUPING(s.week_start) ASC, s.week_start ASC, GROUPING(s.date_id) ASC, s.date_id ASC
        ";

        try {
            // 요청한 월 문자열 계산 (YYYY-MM 형식)
            $requestMonth = date('Y-m', strtotime($fromDate));
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindValue(':from_date',   $fromDate,   \PDO::PARAM_STR);
            $stmt->bindValue(':to_date',     $toDate,     \PDO::PARAM_STR);
            $stmt->bindValue(':from_date_sub', $fromDate, \PDO::PARAM_STR);
            $stmt->bindValue(':to_date_sub',   $toDate,   \PDO::PARAM_STR);
            $stmt->bindValue(':request_month', $requestMonth, \PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 타입/키 정리 (rainfall_mm은 float로 캐스팅)
            foreach ($rows as &$r) {
                // DB 드라이버에 따라 문자열로 올 수 있으니 안전하게 변환
                $r['rainfall_mm'] = isset($r['rainfall_mm'])
                    ? (float) round((float)$r['rainfall_mm'], 1)
                    : 0.0;
                // level, date_id, ym, week_start 키 보장
                $r['level']       = $r['level'] ?? 'DAY';
                $r['date_id']     = $r['date_id'] ?? null;
                $r['ym']          = $r['ym'] ?? null;
                $r['week_start']  = $r['week_start'] ?? null;
                $r['region_name'] = $r['region_name'] ?? null;
                $r['is_holiday']  = isset($r['is_holiday']) ? (int)$r['is_holiday'] : null;
                $r['status_name'] = $r['status_name'] ?? null;
            }
            unset($r);

            return $rows;

        } catch (\PDOException $e) {
            error_log("DB Error in getRainRollup: " . $e->getMessage());
            throw new \Exception("강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
