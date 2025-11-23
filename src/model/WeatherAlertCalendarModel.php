<?php
/**
 * File: src/model/WeatherAlertCalendarModel.php
 * Author: 황혜린
 * Description: 기능 2-4. 특정 월의 지역별 기상 특보 목록 조회 Model
 * Last Updated: 2025-11-21
 */

namespace App\Model;

class WeatherAlertCalendarModel
{
    /** @var \PDO */
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * 특정 월의 기상 특보 목록을 반환
     *
     * 반환 스키마(Data[item]):
     *  - alert_id   : int
     *  - date_id    : 'YYYY-MM-DD'
     *  - alert_time : 'HH:MM:SS'
     *  - alert_type : string
     *
     * @param string $regionCode 3자리 지역 코드
     * @param string $fromDate   YYYY-MM-DD (해당 월 1일)
     * @param string $toDate     YYYY-MM-DD (해당 월 마지막 날)
     * @return array
     * @throws \Exception
     */
    public function getMonthlyAlertsByRange(string $regionCode, string $fromDate, string $toDate): array
    {
        $sql = "
            SELECT
                WA.alert_id,
                DATE_FORMAT(WA.date_id, '%Y-%m-%d')     AS date_id,
                TIME_FORMAT(WA.alert_time, '%H:%i:%s')  AS alert_time,
                WA.alert_type
            FROM WeatherAlert WA
            WHERE WA.region_code = :region_code
              AND WA.date_id BETWEEN :from_date AND :to_date
            ORDER BY WA.date_id ASC, WA.alert_time ASC, WA.alert_id ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindValue(':from_date',   $fromDate,   \PDO::PARAM_STR);
            $stmt->bindValue(':to_date',     $toDate,     \PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 키/타입 보정
            foreach ($rows as &$r) {
                $r['alert_id']   = isset($r['alert_id']) ? (int)$r['alert_id'] : null;
                $r['date_id']    = $r['date_id']    ?? null;
                $r['alert_time'] = $r['alert_time'] ?? null;
                $r['alert_type'] = $r['alert_type'] ?? null;
            }
            unset($r);

            return $rows;

        } catch (\PDOException $e) {
            error_log("DB Error in getMonthlyAlertsByRange: " . $e->getMessage());
            throw new \Exception("기상 특보 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
