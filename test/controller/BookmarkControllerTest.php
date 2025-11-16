<?php
/**
 * File: src/test/controller/BookmarkControllerTest.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기 컨트롤러 테스트
 * Last Updated: 2025-11-09
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/BookmarkController.php';
require_once __DIR__ . '/../../src/model/BookmarkModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\BookmarkController;
use App\Model\BookmarkModel;

class BookmarkControllerTest extends TestCase
{
    /**
     * @test
     * 유효한 요청에 대해 201 Created 응답을 반환하는지 테스트
     */
    public function createBookmarkAction_shouldReturn201_onValidRequest()
    {
        $body = [
            'region_code' => '01001',
            'start_date'  => '2024-07-01',
            'end_date'    => '2024-07-03',
        ];

        // 1. PDO / PDOStatement Mock 설정 (Model 내부에서 사용)
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(1);  // Region UPDATE 1건

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmtMock);
        $dbMock->method('lastInsertId')->willReturn('123');
        $dbMock->method('commit')->willReturn(true);

        // 2. Controller Mocking (sendResponse / sendErrorResponse만 목킹)
        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 3. 201 응답이 제대로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(201),
                $this->stringContains("즐겨찾기 등록이 완료되었습니다."),
                $this->isType('array')   // data 배열이면 OK
            );

        // 에러 응답은 호출되지 않아야 함
        $controllerMock->expects($this->never())
            ->method('sendErrorResponse');

        // 4. 메소드 실행
        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * 필수 파라미터 누락 시 400 Bad Request 응답을 반환하는지 테스트
     */
    public function createBookmarkAction_shouldReturn400_onMissingParams()
    {
        // region_code, start_date, end_date 가 전부 빠졌다고 가정
        $body = [];

        $dbMock = $this->createMock(\PDO::class);
        // 필수 파라미터 검증 단계에서 DB 접근이 없어야 함
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("필수 데이터(region_code, start_date, end_date)가 누락되었습니다.")
            );

        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * Model에서 예외가 발생했을 때 500 Internal Server Error를 반환하는지 테스트
     */
    public function createBookmarkAction_shouldReturn500_onModelException()
    {
        $body = [
            'region_code' => '01001',
            'start_date'  => '2024-07-01',
            'end_date'    => '2024-07-03',
        ];

        // Model 내부 prepare 호출 시 PDOException 발생하도록 설정
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('prepare')->willThrowException(new \PDOException("Simulated DB error"));

        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains("즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.")
            );

        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * 즐겨찾기 목록 조회 시 200 OK와 데이터 배열을 반환하는지 테스트
     */
    public function listBookmarksAction_shouldReturn200_withData()
    {
        $mockData = [
            [
                'bookmark_id' => 1,
                'region_code' => '01001',
                'region_name' => '서울',
                'start_date'  => '2024-07-01',
                'end_date'    => '2024-07-03',
            ]
        ];

        // BookmarkModel::getAllBookmarks() 내부 동작을 위한 PDO / Statement Mock
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->stringContains("즐겨찾기 목록 조회가 완료되었습니다."),
                $this->equalTo($mockData)
            );

        $controllerMock->listBookmarksAction([]);
    }

    /**
     * @test
     * 즐겨찾기 삭제 시 bookmark_id 누락되면 400을 반환하는지 테스트
     */
    public function deleteBookmarkAction_shouldReturn400_onMissingParams()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("bookmark_id 또는 (region_code, start_date, end_date) 중 하나는 반드시 제공되어야 합니다.")
            );

        // 파라미터 없이 호출
        $controllerMock->deleteBookmarkAction([]);
    }
}
