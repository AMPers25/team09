<?php
/**
 * File: src/model/BookmarkModelTest.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기 모델 테스트
 * Last Updated: 2025-11-09
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\BookmarkModel;

class BookmarkModelTest extends TestCase
{
    /**
     * @test
     * DB 오류 발생 시 createBookmark가 Exception을 던지는지 테스트
     */
    public function createBookmark_shouldThrowExceptionOnDbError()
    {
        $regionCode = '01001';
        $startDate  = '2024-07-01';
        $endDate    = '2024-07-03';

        // prepare() 호출 시 PDOException 발생
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('prepare')->willThrowException(new \PDOException("Simulated DB Error"));

        $model = new BookmarkModel($dbMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.");

        $model->createBookmark($regionCode, $startDate, $endDate);
    }

    /**
     * @test
     * UNIQUE 제약(중복 즐겨찾기) 위반 시 올바른 메시지로 Exception을 던지는지 테스트
     */
    public function createBookmark_shouldThrowDuplicateExceptionOnUniqueViolation()
    {
        $regionCode = '01001';
        $startDate  = '2024-07-01';
        $endDate    = '2024-07-03';

        // INSERT 과정에서 PDOException(code 23000) 발생하도록 설정
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willThrowException(
            new \PDOException("Duplicate entry", '23000')
        );

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmtMock);
        $dbMock->method('inTransaction')->willReturn(true);

        $model = new BookmarkModel($dbMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("이미 동일한 기간과 지역이 즐겨찾기에 등록되어 있습니다.");

        $model->createBookmark($regionCode, $startDate, $endDate);
    }

    /**
     * @test
     * 즐겨찾기 목록 조회가 예상된 데이터를 반환하는지 테스트
     */
    public function getAllBookmarks_shouldReturnData()
    {
        $expected = [
            [
                'bookmark_id' => 1,
                'region_code' => '01001',
                'region_name' => '서울',
                'start_date'  => '2024-07-01',
                'end_date'    => '2024-07-03',
            ],
            [
                'bookmark_id' => 2,
                'region_code' => '02001',
                'region_name' => '부산',
                'start_date'  => '2024-08-10',
                'end_date'    => '2024-08-15',
            ]
        ];

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM Bookmark b'))
            ->willReturn($stmtMock);

        $model = new BookmarkModel($dbMock);
        $actual = $model->getAllBookmarks();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * deleteBookmarkById가 삭제 성공 시 true를 반환하는지 테스트
     */
    public function deleteBookmarkById_shouldReturnTrue_onRowDeleted()
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindParam')->willReturn(true);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('rowCount')->willReturn(1);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())->method('prepare')->willReturn($stmtMock);

        $model = new BookmarkModel($dbMock);
        $result = $model->deleteBookmarkById(1);

        $this->assertTrue($result);
    }

    /**
     * @test
     * deleteBookmarkById가 삭제할 대상이 없을 때 false를 반환하는지 테스트
     */
    public function deleteBookmarkById_shouldReturnFalse_onNoRowDeleted()
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(0);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        $model = new BookmarkModel($dbMock);
        $result = $model->deleteBookmarkById(999);

        $this->assertFalse($result);
    }
}
