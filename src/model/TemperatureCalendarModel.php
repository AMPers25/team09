<?php
/**
 * File: src/model/TemperatureCalendarModel.php
 * Author: 김연수 (sooooscode)
 * Description: 기능 2-2. 기온 캘린더 조회 Model
 * Last Updated: 2025-11-16
 */

namespace App\Model;

class TemperatureCalendarModel {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    /**
     * 기능 2-2: 월/지역별 일일 평균기온 목록을 반환
     * @param string $regionCode 조회할 지역 코드
     * @param int $year 조회할 연도 (YYYY)
     * @param int $month 조회할 월 (1~12)
     * @return array 일자별 평균기온 목록 (date_id, avg_temp)
     */
    public function getDailyAverageTemperature(string $regionCode, int $year, int $month): array {
        $sql = "
            SELECT
                r.region_name,
                d.date_id,
                d.day,
                d.is_holiday,
                t.avg_temp,
                t.min_temp,
                t.max_temp,
                ws.status_name,
                (SELECT ROUND(AVG(t2.avg_temp), 1) 
                 FROM Temperature t2 
                 JOIN DateDim d2 ON t2.date_id = d2.date_id 
                 WHERE t2.region_code = t.region_code 
                   AND YEAR(d2.date_id) = 2024 
                   AND d2.month = d.month) AS month_avg_temp,
                (SELECT MIN(t2.min_temp) 
                 FROM Temperature t2 
                 JOIN DateDim d2 ON t2.date_id = d2.date_id 
                 WHERE t2.region_code = t.region_code 
                   AND YEAR(d2.date_id) = 2024 
                   AND d2.month = d.month) AS month_min_temp,
                (SELECT MAX(t2.max_temp) 
                 FROM Temperature t2 
                 JOIN DateDim d2 ON t2.date_id = d2.date_id 
                 WHERE t2.region_code = t.region_code 
                   AND YEAR(d2.date_id) = 2024 
                   AND d2.month = d.month) AS month_max_temp
            FROM
                Temperature t
            JOIN
                Region r ON r.region_code = t.region_code
            JOIN
                DateDim d ON t.date_id = d.date_id
            LEFT JOIN
                Rain rn ON rn.region_code = t.region_code AND rn.date_id = t.date_id
            LEFT JOIN
                WeatherStatusDim ws ON ws.status_code = rn.status_code
            WHERE
                t.region_code = :regionCode
                AND YEAR(d.date_id) = 2024
                AND d.month = :month
            ORDER BY
                d.date_id ASC;
        ";

        // SQL 쿼리 문자열 정규화 (줄바꿈/다중 공백 제거)
        $cleanSql = preg_replace("/(--.*)|(\/\*.*?\*\/)/s", '', $sql);
        $cleanSql = trim(preg_replace('/\s+/', ' ', $cleanSql));

        try {
            $stmt = $this->db->prepare($cleanSql);
            $stmt->bindParam(':regionCode', $regionCode, \PDO::PARAM_STR);
            $stmt->bindParam(':month', $month, \PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getDailyAverageTemperature: " . $e->getMessage());
            throw new \Exception("기온 데이터 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}