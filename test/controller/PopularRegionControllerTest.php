<?php
/**
 * File: src/model/PopularRegionControllerTest.php
 * Author: 황혜린
 * Description: 기능 1-2. 인기 지역 조회 컨트롤러 테스트
 * Last Updated: 2025-11-09
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/PopularRegionController.php';
require_once __DIR__ . '/../../src/model/PopularRegionModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\PopularRegionController;

class PopularRegionControllerTest extends TestCase
{
    /**
     * @test
     * 정상 요청 시 200 OK와 데이터 배열을 반환하는지 테스트
     */
    public function getPopularRegionsAction_shouldReturn200Ok_onValidRequest()
    {
        $mockData = [
            [
                'rank'          => 1,
                'region_code'   => '01101',
                'region_name'   => '서울 강남구',
                'popular_count' => 128,
            ],
            [
                'rank'          => 2,
                'region_code'   => '01102',
                'region_name'   => '서울 서초구',
                'popular_count' => 97,
            ],
        ];

        // PDOStatement Mock
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($mockData);

        // PDO Mock
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // Controller Mock (sendResponse / sendErrorResponse만 목킹)
        $controllerMock = $this->getMockBuilder(PopularRegionController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 200 OK 응답 검증
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->equalTo('OK'),
                $this->equalTo($mockData)
            );

        $controllerMock->expects($this->never())
            ->method('sendErrorResponse');

        // limit 파라미터 포함 요청
        $controllerMock->getPopularRegionsAction(['limit' => '5']);
    }

    /**
     * @test
     * Model/DB에서 예외 발생 시 500 Internal Server Error를 반환하는지 테스트
     */
    public function getPopularRegionsAction_shouldReturn500_onModelException()
    {
        // prepare() 단계에서 PDOException 발생 → Model에서 Exception으로 변환
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willThrowException(new \PDOException("Simulated DB Error"));

        $controllerMock = $this->getMockBuilder(PopularRegionController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains('인기 지역 조회 중 서버 내부 오류가 발생했습니다.')
            );

        $controllerMock->getPopularRegionsAction([]);
    }
}
