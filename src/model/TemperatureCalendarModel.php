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
        // 성능 향상을 위해 DateDim 테이블의 year 컬럼을 사용하도록 쿼리를 수정합니다.
        // DateDim에 year, month 컬럼이 있다고 가정합니다.
        $sql = "
            SELECT
                d.date_id,
                t.avg_temp,
                t.min_temp,
                t.max_temp
            FROM
                Temperature t
            JOIN
                DateDim d ON t.date_id = d.date_id
            WHERE
                t.region_code = :regionCode
                AND d.year = :year  -- YEAR(d.date_id) 대신 d.year 컬럼 사용
                AND d.month = :month
            ORDER BY
                d.date_id ASC;
        ";

        // SQL 쿼리 문자열 정규화 (줄바꿈/다중 공백 제거)
        // 1. 주석 제거 (선택 사항이지만 안전함)
        $cleanSql = preg_replace("/(--.*)|(\/\*.*?\*\/)/s", '', $sql);
        // 2. 줄바꿈, 탭, 다중 공백을 단일 공백으로 치환하고 앞뒤 공백 제거
        $cleanSql = trim(preg_replace('/\s+/', ' ', $cleanSql));

        try {

            $stmt = $this->db->prepare($cleanSql);
            $stmt->bindParam(':regionCode', $regionCode, \PDO::PARAM_STR);
            $stmt->bindParam(':year', $year, \PDO::PARAM_INT);   // 연도 바인딩
            $stmt->bindParam(':month', $month, \PDO::PARAM_INT); // 월 바인딩

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getDailyAverageTemperature: " . $e->getMessage());
            throw new \Exception("기온 데이터 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}