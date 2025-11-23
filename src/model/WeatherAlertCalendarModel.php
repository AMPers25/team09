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
     *  - status_code: string (날씨 상태 코드)
     *  - status_name: string (날씨 상태)
     *  - is_holiday : int (공휴일 여부)
     *
     * @param string $regionCode 3자리 지역 코드
     * @param string $fromDate   YYYY-MM-DD (해당 월 1일)
     * @param string $toDate     YYYY-MM-DD (해당 월 마지막 날)
     * @return array
     * @throws \Exception
     */
    public function getMonthlyAlertsByRange(string $regionCode, string $fromDate, string $toDate): array
    {
        // 해당 월의 모든 날짜에 대해 weather status 정보를 가져오고, alert 정보는 LEFT JOIN으로 처리
        $sql = "
            SELECT
                WA.alert_id,
                DATE_FORMAT(D.date_id, '%Y-%m-%d')     AS date_id,
                TIME_FORMAT(WA.alert_time, '%H:%i:%s')  AS alert_time,
                WA.alert_type,
                R.status_code,
                WS.status_name,
                D.is_holiday
            FROM DateDim D
            LEFT JOIN WeatherAlert WA ON WA.region_code = :region_code_alert 
                AND WA.date_id = D.date_id
            LEFT JOIN Rain R ON R.region_code = :region_code_rain 
                AND R.date_id = D.date_id
            LEFT JOIN WeatherStatusDim WS ON WS.status_code = R.status_code
            WHERE D.date_id BETWEEN :from_date AND :to_date
            ORDER BY D.date_id ASC, WA.alert_time ASC, WA.alert_id ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':region_code_alert', $regionCode, \PDO::PARAM_STR);
            $stmt->bindValue(':region_code_rain', $regionCode, \PDO::PARAM_STR);
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
                $r['status_code'] = $r['status_code'] ?? null;
                $r['status_name'] = $r['status_name'] ?? null;
                $r['is_holiday'] = isset($r['is_holiday']) ? (int)$r['is_holiday'] : 0;
            }
            unset($r);

            return $rows;

        } catch (\PDOException $e) {
            error_log("DB Error in getMonthlyAlertsByRange: " . $e->getMessage());
            throw new \Exception("기상 특보 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
