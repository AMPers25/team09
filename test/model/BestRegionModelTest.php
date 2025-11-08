<?php
// File: test/model/BestRegionModelTest.php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\BestRegionModel;

class BestRegionModelTest extends TestCase
{
    /**
     * @test
     * Model이 특정 기간 내 지역별 평균 TI 점수 기준으로 Top 5를 정확히 반환하는지 테스트
     */
    public function getBestRegionRanking_shouldReturnTop5Data()
    {
        $startDate = '2024-10-01';
        $endDate = '2024-10-31';
        $expectedResult = [
            ['region_code' => '48011', 'region_name' => '제주시', 'province' => null, 'avg_ti_score' => 93.55, 'rank' => 1],
            ['region_code' => '11000', 'region_name' => '서울시', 'province' => null, 'avg_ti_score' => 93.50, 'rank' => 2],
            ['region_code' => '37050', 'region_name' => '강릉시', 'province' => '강원도', 'avg_ti_score' => 91.88, 'rank' => 3],
        ];

        // 1. PDOStatement Mocking: 파라미터 바인딩 및 데이터 반환 설정
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->at(0))->method('bindParam')->with(':startDate', $startDate, \PDO::PARAM_STR);
        $stmtMock->expects($this->at(1))->method('bindParam')->with(':endDate', $endDate, \PDO::PARAM_STR);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn($expectedResult);

        // 2. PDO (DB) Mocking: SQL 쿼리 로직 검증 및 $stmtMock 반환
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('RANK() OVER (ORDER BY AVG(TI.travel_index_score) DESC'))
            ->willReturn($stmtMock);

        $model = new BestRegionModel($dbMock);

        // 4. 메소드 실행 및 결과 검증
        $actualResult = $model->getBestRegionRanking($startDate, $endDate);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * DB 오류 발생 시 Exception을 던지는지 테스트
     */
    public function getBestRegionRanking_shouldThrowExceptionOnDbError()
    {
        $startDate = '2024-10-01';
        $endDate = '2024-10-31';

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException("Simulated DB Connection Error"));

        $model = new BestRegionModel($dbMock);

        // 예외 검증
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("지역 랭킹 분석 중 서버 내부 오류가 발생했습니다.");

        $model->getBestRegionRanking($startDate, $endDate);
    }
}