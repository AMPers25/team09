<?php
/**
 * File: src/model/PopularRegionModel.php
 * Author: 황혜린
 * Description: 기능 1-2. 홈 인기 지역(popular_count 기준) 조회 Model
 * Last Updated: 2025-11-09
 */

namespace App\Model;

class PopularRegionModel
{
    /** @var \PDO */
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * popular_count가 높은 순으로 지역 목록 조회
     *
     * Data[item]:
     *  - rank          : 1부터 시작하는 순위
     *  - region_code   : 지역 코드
     *  - region_name   : 지역명
     *  - popular_count : 즐겨찾기 누적 횟수
     *
     * @param int $limit 최대 조회 개수 (기본 10)
     * @return array
     * @throws \Exception DB 오류 시
     */
    public function getPopularRegions(int $limit = 10): array
    {
        $sql = "
            SELECT
                ROW_NUMBER() OVER (ORDER BY popular_count DESC, region_name ASC) AS rank,
                region_code,
                region_name,
                popular_count
            FROM Region
            ORDER BY popular_count DESC, region_name ASC
            LIMIT :limit
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log('DB Error in getPopularRegions: ' . $e->getMessage());
            throw new \Exception('인기 지역 조회 중 서버 내부 오류가 발생했습니다.');
        }
    }
}
