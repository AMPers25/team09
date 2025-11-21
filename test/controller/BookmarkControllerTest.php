<?php
/**
 * File: test/controller/BookmarkControllerTest.php
 * Author: 황혜린
 * Description: 기능 4. 즐겨찾기 컨트롤러 테스트 (세션 스코프 대응)
 * Last Updated: 2025-11-21
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/BookmarkController.php';
require_once __DIR__ . '/../../src/model/BookmarkModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\BookmarkController;

class BookmarkControllerTest extends TestCase
{
    /**
     * @test
     * 유효한 요청에 대해 201 Created 응답을 반환하는지 테스트
     * (세션 스코프: session_id 포함)
     */
    public function createBookmarkAction_shouldReturn201_onValidRequest()
    {
        // 세션 미리 시작(컨트롤러에서도 자동 시작하지만, 안정성 위해 명시)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $body = [
            'region_code' => '01001',
            'start_date'  => '2024-07-01',
            'end_date'    => '2024-07-03',
        ];

        // 1) PDO / Statement Mock
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('rowCount')->willReturn(1); // Region UPDATE 1건 성공

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        $dbMock->method('prepare')->willReturn($stmtMock);
        $dbMock->method('lastInsertId')->willReturn('123');
        $dbMock->method('commit')->willReturn(true);

        // 2) Controller Mock (sendResponse/sendErrorResponse만 Mock)
        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 3) 201 호출 검증 + data 필드에 session_id/필수키 존재 확인
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(201),
                $this->stringContains('즐겨찾기 등록이 완료되었습니다.'),
                $this->callback(function ($data) {
                    return is_array($data)
                        && isset($data['bookmark_id'])
                        && isset($data['session_id'])
                        && isset($data['region_code'])
                        && isset($data['start_date'])
                        && isset($data['end_date']);
                })
            );

        $controllerMock->expects($this->never())
            ->method('sendErrorResponse');

        // 4) 실행
        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * 필수 파라미터 누락 시 400 Bad Request
     */
    public function createBookmarkAction_shouldReturn400_onMissingParams()
    {
        $body = []; // 모두 누락

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
                $this->stringContains('필수 데이터(region_code, start_date, end_date)가 누락되었습니다.')
            );

        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * Model/DB 예외 발생 시 500
     */
    public function createBookmarkAction_shouldReturn500_onModelException()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $body = [
            'region_code' => '01001',
            'start_date'  => '2024-07-01',
            'end_date'    => '2024-07-03',
        ];

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('beginTransaction')->willReturn(true);
        // Model 내부 prepare() 시 예외 발생
        $dbMock->method('prepare')->willThrowException(new \PDOException('Simulated DB error'));

        $controllerMock = $this->getMockBuilder(BookmarkController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains('즐겨찾기 등록 중 서버 내부 오류가 발생했습니다.')
            );

        $controllerMock->createBookmarkAction($body);
    }

    /**
     * @test
     * 즐겨찾기 목록 조회 시 200 OK + 배열 반환
     * (세션 스코프: 내부적으로 session_id 조건으로 조회)
     */
    public function listBookmarksAction_shouldReturn200_withData()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['user_id'] = 'test-session-123';

        $mockData = [
            [
                'bookmark_id' => 1,
                'region_code' => '01001',
                'region_name' => '서울',
                'start_date'  => '2024-07-01',
                'end_date'    => '2024-07-03',
            ],
        ];

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
                $this->stringContains('즐겨찾기 목록 조회가 완료되었습니다.'),
                $this->equalTo($mockData)
            );

        $controllerMock->listBookmarksAction([]);
    }

    /**
     * @test
     * 즐겨찾기 삭제: 파라미터 누락 시 400
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
                $this->stringContains('bookmark_id 또는 (region_code, start_date, end_date) 중 하나는 반드시 제공되어야 합니다.')
            );

        $controllerMock->deleteBookmarkAction([]);
    }
}
