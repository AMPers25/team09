<?php
/**
 * File: src/model/BookmarkModel.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기(Bookmark) Model (세션 스코프)
 * Last Updated: 2025-11-21
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
     * @param string $sessionId  PHP 세션ID (익명 사용자 구분자)
     * @param string $regionCode Region.region_code
     * @param string $startDate  YYYY-MM-DD
     * @param string $endDate    YYYY-MM-DD
     * @return int bookmark_id
     * @throws \Exception
     */
    public function createBookmark(string $sessionId, string $regionCode, string $startDate, string $endDate): int
    {
        try {
            $this->db->beginTransaction();

            // 1) Bookmark INSERT (세션 스코프)
            $sqlInsert = "
                INSERT INTO Bookmark (session_id, region_code, start_date, end_date)
                VALUES (:session_id, :region_code, :start_date, :end_date)
            ";
            $stmtInsert = $this->db->prepare($sqlInsert);
            $stmtInsert->bindParam(':session_id',  $sessionId,  \PDO::PARAM_STR);
            $stmtInsert->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmtInsert->bindParam(':start_date',  $startDate,  \PDO::PARAM_STR);
            $stmtInsert->bindParam(':end_date',    $endDate,    \PDO::PARAM_STR);
            $stmtInsert->execute();

            $bookmarkId = (int)$this->db->lastInsertId();

            // 2) 해당 지역 인기도 +1
            $sqlUpdateRegion = "
                UPDATE Region
                   SET popular_count = popular_count + 1
                 WHERE region_code = :region_code
            ";
            $stmtUpdate = $this->db->prepare($sqlUpdateRegion);
            $stmtUpdate->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmtUpdate->execute();

            if ($stmtUpdate->rowCount() === 0) {
                $this->db->rollBack();
                throw new \Exception("존재하지 않는 지역 코드입니다.");
            }

            $this->db->commit();
            return $bookmarkId;

        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            // 중복(UNIQUE) 위반: SQLSTATE 23000
            if ((string)$e->getCode() === '23000') {
                error_log("DB Error in createBookmark: " . $e->getMessage());
                // ⚠️ 현재 UNIQUE( region_code, start_date, end_date )라 사용자 간 중복도 막힐 수 있음
                throw new \Exception("이미 동일한 기간과 지역이 즐겨찾기에 등록되어 있습니다.");
            }

            error_log("DB Error in createBookmark: " . $e->getMessage());
            throw new \Exception("즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * bookmark_id 기준 삭제 (세션 스코프)
     */
    public function deleteBookmarkById(string $sessionId, int $bookmarkId): bool
    {
        try {
            $sql = "DELETE FROM Bookmark WHERE bookmark_id = :bookmark_id AND session_id = :session_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':bookmark_id', $bookmarkId, \PDO::PARAM_INT);
            $stmt->bindParam(':session_id',  $sessionId,  \PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log("DB Error in deleteBookmarkById: " . $e->getMessage());
            throw new \Exception("즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * (region_code, 기간) 튜플 기준 삭제 (세션 스코프)
     */
    public function deleteBookmarkByTuple(string $sessionId, string $regionCode, string $startDate, string $endDate): bool
    {
        try {
            $sql = "
                DELETE FROM Bookmark
                 WHERE session_id  = :session_id
                   AND region_code = :region_code
                   AND start_date  = :start_date
                   AND end_date    = :end_date
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id',  $sessionId,  \PDO::PARAM_STR);
            $stmt->bindParam(':region_code', $regionCode, \PDO::PARAM_STR);
            $stmt->bindParam(':start_date',  $startDate,  \PDO::PARAM_STR);
            $stmt->bindParam(':end_date',    $endDate,    \PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            error_log("DB Error in deleteBookmarkByTuple: " . $e->getMessage());
            throw new \Exception("즐겨찾기 삭제 중 서버 내부 오류가 발생했습니다.");
        }
    }

    /**
     * 현재 세션의 즐겨찾기 목록 조회
     * 반환: bookmark_id, region_code, region_name, start_date, end_date
     */
    public function getBookmarksBySession(string $sessionId): array
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
            WHERE b.session_id = :session_id
            ORDER BY b.bookmark_id DESC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("DB Error in getBookmarksBySession: " . $e->getMessage());
            throw new \Exception("즐겨찾기 목록 조회 중 서버 내부 오류가 발생했습니다.");
        }
    }
}
