<?php

/**
 * File: src/model/BookmarkModel.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기(Bookmark) Model
 * Last Updated: 2025-11-09
 */

namespace App\Model;

class BookmarkModel
{
    /** @var \PDO */
    private $db;

    public function __construct($db_connection)
    {
        $this->db = $db_connection;
    }

    /**
     * 즐겨찾기 생성 + 해당 지역 popular_count 증가 (트랜잭션)
     *
     * @param string $regionCode  Region.region_code
     * @param string $startDate   YYYY-MM-DD
     * @param string $endDate     YYYY-MM-DD
     * @return int  생성된 bookmark_id
     * @throws \Exception  유니크 제약 위반 또는 DB 오류 시
     */
    public function createBookmark(string $regionCode, string $startDate, string $endDate): int
    {
        try {
            // 트랜잭션 시작
            $this->db->beginTransaction();

            // 1) Bookmark INSERT
            $sqlInsert = "
                INSERT INTO Bookmark (region_code, start_date, end_date)
                VALUES (:region_code, :start_date, :end_date)
            ";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmtInsert->bindParam(':start_date', $startDate, \PDO::PARAM_STR);
            $stmtInsert->bindParam(':end_date', $endDate, \PDO::PARAM_STR);
            $stmtInsert->execute();

            $bookmarkId = (int)$this->db->lastInsertId();

            // 2) Region.popular_count +1
            $sqlUpdateRegion = "
                UPDATE Region
                   SET popular_count = popular_count + 1
                 WHERE region_code = :region_code
            ";
            $stmtUpdate = $this->db->prepare($sqlUpdateRegion);
            $stmtUpdate->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmtUpdate->execute();

            // 혹시 region_code가 잘못되어 0건 업데이트라면 롤백
            if ($stmtUpdate->rowCount() === 0) {
                $this->db->rollBack();
                throw new \Exception("존재하지 않는 지역 코드입니다.");
            }

            // 모두 성공 → 커밋
            $this->db->commit();
            return $bookmarkId;

        } catch (\PDOException $e) {
            // 실패 시 롤백
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // 중복(UNIQUE 제약) 위반: SQLSTATE 23000
            if ((string)$e->getCode() === '23000') {
                error_log("DB Error in createBookmark: " . $e->getMessage());
                throw new \Exception("이미 동일한 기간과 지역이 즐겨찾기에 등록되어 있습니다.");
            }

            // 그 외 DB 오류는 일반 내부 오류로 처리
            error_log("DB Error in createBookmark: " . $e->getMessage());
            throw new \Exception("즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * bookmark_id 기준으로 즐겨찾기 삭제
     *
     * @param int $bookmarkId
     * @return bool true: 삭제됨, false: 대상 없음
     * @throws \Exception DB 오류 시
     */
    public function deleteBookmarkById(int $bookmarkId): bool
    {
        try {
            $sql = "DELETE FROM Bookmark WHERE bookmark_id = :bookmark_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':bookmark_id', $bookmarkId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log("DB Error in deleteBookmarkById: " . $e->getMessage());
            throw new \Exception("즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * (옵션) region_code + 기간 튜플로 즐겨찾기 삭제
     *
     * @param string $regionCode
     * @param string $startDate
     * @param string $endDate
     * @return bool true: 삭제됨, false: 대상 없음
     * @throws \Exception
     */
    public function deleteBookmarkByTuple(string $regionCode, string $startDate, string $endDate): bool
    {
        try {
            $sql = "
                DELETE FROM Bookmark
                 WHERE region_code = :region_code
                   AND start_date  = :start_date
                   AND end_date    = :end_date
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindParam(':start_date', $startDate, \PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $endDate, \PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log("DB Error in deleteBookmarkByTuple: " . $e->getMessage());
            throw new \Exception("즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * 즐겨찾기 전체 목록 조회
     * (요구사항: 4페이지에서 자신의 즐겨찾기 목록 확인)
     * 현재 Bookmark 스키마에 user_id가 없으므로 "전체 즐겨찾기" 기준.
     *
     * @return array [
     *   ['bookmark_id' => 1, 'region_code' => '01001', 'region_name' => '서울', 'start_date' => '2024-07-01', 'end_date' => '2024-07-03'],
     *   ...
     * ]
     */
    public function getAllBookmarks(): array
    {
        $sql = "
            SELECT
                b.bookmark_id,
                b.region_code,
                r.region_name,
                b.start_date,
                b.end_date
            FROM Bookmark b
            JOIN Region r ON r.region_code = b.region_code
            ORDER BY b.bookmark_id DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getAllBookmarks: " . $e->getMessage());
            throw new \Exception("즐겨찾기 목록 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
