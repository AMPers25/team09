<?php
/**
 * File: src/model/DailyModel.php
 * Author: 강한나
 * Description: 기능 2-1. 일일 날씨 통합 조회 Model
 * Last Updated: 2025-11-17
 */

namespace App\Model;

class DailyModel
{
    /** @var \PDO */
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * 특정 지역/날짜의 일일 날씨 통합 정보 조회
     *
     * @param string $regionCode 지역 코드
     * @param string $date       조회 날짜 (YYYY-MM-DD)
     * @return array|null        레코드 1건 또는 null (없을 때)
     * @throws \Exception        DB 오류 시
     */
    public function getDailyWeather(string $regionCode, string $date): ?array
    {
        $sql = "
            SELECT
              r.region_code,
              r.region_name,
              d.date_id,
              t.avg_temp, t.max_temp, t.min_temp, t.daily_temp_range,
              rn.daily_rainfall, rn.humidity, rn.wind_speed, rn.cloud_cover, rn.status_code,
              ws.status_name,
              wa.alert_time, wa.alert_type,
              aq.pm10
            FROM Region r
            JOIN DateDim d ON d.date_id = :date_id
            LEFT JOIN Temperature t ON t.region_code = r.region_code AND t.date_id = d.date_id
            LEFT JOIN Rain rn ON rn.region_code = r.region_code AND rn.date_id = d.date_id
            LEFT JOIN WeatherStatusDim ws ON ws.status_code = rn.status_code
            LEFT JOIN WeatherAlert wa ON wa.region_code = r.region_code AND wa.date_id = d.date_id
            LEFT JOIN AirQuality aq ON aq.region_code = r.region_code AND aq.date_id = d.date_id
            WHERE r.region_code = :region_code
            LIMIT 1;
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':date_id', $date, \PDO::PARAM_STR);
            $stmt->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return $row;
        } catch (\PDOException $e) {
            error_log('DB Error in DailyModel::getDailyWeather: ' . $e->getMessage());
            throw new \Exception('일일 날씨 데이터 조회 중 서버 내부 오류가 발생했습니다.');
        }
    }
}


