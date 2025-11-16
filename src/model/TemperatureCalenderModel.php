<?php
/**
 * File: src/model/TemperatureCalendarModel.php
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
        // API 응답에 필요한 date_id와 avg_temp만 SELECT하도록 쿼리를 단순화함.
        $sql = "
            SELECT
                d.date_id,
                t.avg_temp
            FROM
                Temperature t
            JOIN
                DateDim d ON t.date_id = d.date_id
            WHERE
                t.region_code = :regionCode
                AND d.month = :month
                AND YEAR(d.date_id) = :year
            ORDER BY
                d.date_id ASC;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':regionCode', $regionCode, \PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, \PDO::PARAM_INT); // 연도 바인딩
            $stmt->bindParam(':month', $month, \PDO::PARAM_INT); // 월 바인딩

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getDailyAverageTemperature: " . $e->getMessage());
            throw new \Exception("기온 데이터 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}