<?php
/**
 * File: test/model/CleanDayModelTest.php
 * Author: 김연수
 * Description: 기능 3-1. 클린데이(미세먼지 연속 좋음 일수) 계산하는 Test 클래스
 * Last Updated: 2025-11-08
 */

// 임시
// 1. Composer Autoload 로드 (테스트용)
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\CleanDayModel;


class CleanDayModelTest extends TestCase
{
    /**
     * @test
     * Model이 DB와 정상적으로 통신하고 결과를 반환하는지 테스트
     */
    public function getCleanStreakRanking_shouldReturnExpectedData()
    {
        $regionCode = 'SEOUL';
        $expectedResult = [
            ['start_date' => '2024-03-10', 'end_date' => '2024-03-20', 'streak_days' => 11, 'pm10_avg' => 25.5, 'rank' => 1],
            // ... 나머지 Top 5 데이터
        ];

        // 1. PDOStatement Mocking
        // fetchAll을 호출할 때 $expectedResult를 반환하도록 설정
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true); // execute는 성공 시 true 반환 가정
        $stmtMock->expects($this->once())
            ->method('bindParam')
            ->with(':regionCode', $regionCode, PDO::PARAM_STR) // regionCode가 정확히 바인딩되었는지 확인
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);

        // 2. PDO (DB) Mocking
        // prepare를 호출할 때 SQL 쿼리가 포함된 상태로 호출되면 $stmtMock을 반환하도록 설정
        $dbMock = $this->createMock(PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            // 여기서는 SQL 쿼리의 일부분만 검사하여 prepare가 호출되었는지 확인합니다.
            ->with($this->stringContains('RANK() OVER (ORDER BY streak_days DESC'))
            ->willReturn($stmtMock);


        // 3. Model 인스턴스 생성 및 Mock DB 주입
        $model = new \App\Model\CleanDayModel($dbMock);

        // 4. 메소드 실행 및 결과 검증
        $actualResult = $model->getCleanStreakRanking($regionCode);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * DB 실행 중 PDOException이 발생했을 때 Model이 Exception을 던지는지 테스트
     */
    public function getCleanStreakRanking_shouldThrowExceptionOnDbError()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PM10 클린 기간 분석 중 서버 내부 오류가 발생했습니다.');

        // 1. PDO Mocking: execute() 호출 시 PDOException을 발생하도록 설정
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('Mocked DB Connection Error')); // DB 에러 시 발생

        // 2. PDO (DB) Mocking: prepare 호출 시 $stmtMock 반환
        $dbMock = $this->createMock(PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        // 3. Model 인스턴스 생성 및 Mock DB 주입
        $model = new \App\Model\CleanDayModel($dbMock);

        // 4. 메소드 실행 (예외가 발생하는지 확인)
        $model->getCleanStreakRanking('BUSAN');
    }
}