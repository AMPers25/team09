<?php
/**
 * File: src/model/WeatherRainRollupModel.php
 * Author: 황혜린, 강한나, 강리현
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
     * 특정 지역/기간의 일별 강수량, 주 평균을 반환
     *
     * 반환 스키마(Data[item]):
     *  - level       : "DAY" | "WEEK_AVG"
     *  - date_id     : "YYYY-MM-DD" | null (주 평균일 때는 null)
     *  - ym          : "YYYY-MM"
     *  - week_start  : "YYYY-MM-DD" | null (주 시작일, 주 평균일 때만)
     *  - rainfall_mm : float
     *  - region_name  : string | null (DAY 레벨에서만)
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
        // ROLLUP으로 일별 강수량과 주별 평균을 함께 조회
        // 주 시작일은 월요일로 계산 (DAYOFWEEK: 1=일요일, 2=월요일)
        // 주별 평균은 해당 월의 날짜만으로 계산 (전달/다음달 고려하지 않음)
        // 월 평균은 프론트엔드에서 일별 데이터로 계산
        // 주의: GROUPING 함수는 GROUP BY 절의 표현식과 정확히 일치해야 함
        
        
        $sql = "
            SELECT
                CASE 
                    WHEN rn.date_id IS NOT NULL THEN 'DAY'
                    ELSE 'WEEK_AVG'
                END AS level,
    
                rn.date_id AS date_id,
                DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
    
                CASE 
                    WHEN rn.date_id IS NULL THEN DATE_FORMAT(week_start, '%Y-%m-%d')
                    ELSE NULL
                END AS week_start,

                CASE 
                    WHEN rn.date_id IS NULL THEN ROUND(AVG(rn.daily_rainfall), 1)
                    ELSE ROUND(SUM(rn.daily_rainfall), 1)
                END AS rainfall_mm,

                r.region_name,
                d.is_holiday,
                ws.status_name

            FROM (
                SELECT
                    rn.*,
                    -- 주 시작일 계산 (월요일 기준)
                    CASE WHEN DAYOFWEEK(rn.date_id) = 1 
                        THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
                        ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
                    END AS week_start
                FROM Rain rn
                WHERE rn.region_code = :region_code
                    AND rn.date_id BETWEEN :from_date AND :to_date
            ) rn
            JOIN Region r ON r.region_code = rn.region_code
            JOIN DateDim d ON d.date_id = rn.date_id
            LEFT JOIN WeatherStatusDim ws ON ws.status_code = rn.status_code

            GROUP BY week_start, rn.date_id WITH ROLLUP

            HAVING NOT (
                    week_start IS NULL 
                AND rn.date_id IS NULL
            )

            

        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindValue(':from_date',   $fromDate,   \PDO::PARAM_STR);
            $stmt->bindValue(':to_date',     $toDate,     \PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            // 정렬: DAY 먼저, WEEK_AVG 나중 / 날짜 순서
            usort($rows, function($a, $b) {

                // 1) DAY를 먼저
                if ($a['level'] !== $b['level']) {
                    return $a['level'] === 'DAY' ? -1 : 1;
                }

                // 2) DAY 간 비교 → date_id
                if ($a['level'] === 'DAY') {
                    return strcmp($a['date_id'], $b['date_id']);
                }

                // 3) WEEK_AVG 간 비교 → week_start
                return strcmp($a['week_start'], $b['week_start']);
            });
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
