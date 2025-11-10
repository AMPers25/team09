<?php
/**
 * File: src/model/BestPeriodModelTest.php
 * Author: 김연수
 * Description: 기능 3-2. 여행 기간 추천 모델 테스트
 * Last Updated: 2025-11-08
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\BestPeriodModel;

class BestPeriodModelTest extends TestCase
{
    /**
     * @test
     * Model이 주간 평균 TI 점수를 기준으로 Top 5를 정확히 반환하는지 테스트
     */
    public function getBestWeekRanking_shouldReturnTop5Data()
    {
        $regionCode = 'BUSAN';
        // API 명세서의 Success Response Data 구조와 일치해야 합니다.
        $expectedResult = [
            ['start_date' => '2024-10-07', 'end_date' => '2024-10-13', 'avg_ti_score' => 93.55, 'rank' => 1],
            ['start_date' => '2024-05-13', 'end_date' => '2024-05-19', 'avg_ti_score' => 92.10, 'rank' => 2],
            ['start_date' => '2024-09-23', 'end_date' => '2024-09-29', 'avg_ti_score' => 91.88, 'rank' => 3],
            ['start_date' => '2024-04-15', 'end_date' => '2024-04-21', 'avg_ti_score' => 90.50, 'rank' => 4],
            ['start_date' => '2024-11-04', 'end_date' => '2024-11-10', 'avg_ti_score' => 89.90, 'rank' => 5],
        ];

        // 1. PDOStatement Mocking
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('bindParam')
            ->with(':regionCode', $regionCode, \PDO::PARAM_STR)
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);

        // 2. PDO (DB) Mocking
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            // 쿼리에 'RANK() OVER' 구문이 포함되어 있는지 확인
            ->with($this->stringContains('RANK() OVER (ORDER BY AVG(TI.travel_index_score) DESC'))
            ->willReturn($stmtMock);

        // 3. Model 인스턴스 생성 및 Mock DB 주입
        $model = new BestPeriodModel($dbMock);

        // 4. 메소드 실행 및 결과 검증
        $actualResult = $model->getBestWeekRanking($regionCode);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * DB 오류 발생 시 Exception을 던지는지 테스트 (500 에러 처리 준비)
     */
    public function getBestWeekRanking_shouldThrowExceptionOnDbError()
    {
        $regionCode = 'BUSAN';

        // 1. PDO (DB) Mocking: prepare() 호출 시 PDOException을 발생하도록 설정
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException("Simulated DB Connection Error"));

        $model = new BestPeriodModel($dbMock);

        // 2. 예외가 발생하는지 검증
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("주간 랭킹 분석 중 서버 내부 오류가 발생했습니다.");

        $model->getBestWeekRanking($regionCode);
    }
}