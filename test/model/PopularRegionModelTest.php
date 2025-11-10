<?php
/**
 * File: src/model/PopularRegionModelTest.php
 * Author: 황혜린
 * Description: 기능 1-2. 인기 지역 조회 모델 테스트
 * Last Updated: 2025-11-09
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\PopularRegionModel;

class PopularRegionModelTest extends TestCase
{
    /**
     * @test
     * getPopularRegions가 예상된 데이터를 반환하는지 테스트
     */
    public function getPopularRegions_shouldReturnRankedData()
    {
        $expected = [
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
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expected);

        // PDO Mock
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ROW_NUMBER() OVER'))
            ->willReturn($stmtMock);

        $model = new PopularRegionModel($dbMock);
        $actual = $model->getPopularRegions(10);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * DB 오류 발생 시 Exception을 던지는지 테스트
     */
    public function getPopularRegions_shouldThrowExceptionOnDbError()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException("Simulated DB Error"));

        $model = new PopularRegionModel($dbMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('인기 지역 조회 중 서버 내부 오류가 발생했습니다.');

        $model->getPopularRegions(10);
    }
}
