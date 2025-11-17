<?php
/**
 * File: test/model/TemperatureCalendarModelTest.php
 * Author: 김연수 (sooooscode)
 * Description: 기능 2-2. 기온 캘린더 조회 Test
 * Last Updated: 2025-11-16
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/model/TemperatureCalendarModel.php';

use PHPUnit\Framework\TestCase;
use App\Model\TemperatureCalendarModel;

class TemperatureCalendarModelTest extends TestCase
{
    /**
     * @test
     * Model이 요청된 월/지역의 일별 평균 기온 데이터를 정확히 반환하는지 테스트
     */
    public function getDailyAverageTemperature_shouldReturnDailyData()
    {
        $regionCode = '11000'; // 서울
        $year = 2025;
        $month = 10;

        // Mock 데이터 정의 (API Success Response Data 구조와 일치해야 함)
        $expectedResult = [
            ['date_id' => '2025-10-01', 'avg_temp' => 18.4],
            ['date_id' => '2025-10-02', 'avg_temp' => 19.0],
        ];

        // Model에서 정규화될 것으로 예상되는 SQL 문자열 (d.year로 수정됨)
        // prepare()는 stringContains로 체크하므로 유연하나, 주석으로 참고합니다.
        // $expectedSql = 'SELECT d.date_id, t.avg_temp FROM Temperature t JOIN DateDim d ON t.date_id = d.date_id WHERE t.region_code = :regionCode AND d.year = :year AND d.month = :month ORDER BY d.date_id ASC;';


        // 1. PDOStatement Mocking
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);

        // 3개의 파라미터 바인딩 검증 (Model의 바인딩 순서: :regionCode, :year, :month 에 맞게 수정)
        $stmtMock->expects($this->at(0))->method('bindParam')->with(':regionCode', $regionCode, \PDO::PARAM_STR);
        $stmtMock->expects($this->at(1))->method('bindParam')->with(':year', $year, \PDO::PARAM_INT); // 순서 변경
        $stmtMock->expects($this->at(2))->method('bindParam')->with(':month', $month, \PDO::PARAM_INT); // 순서 변경

        $stmtMock->expects($this->once())->method('fetchAll')->willReturn($expectedResult);

        // 2. PDO (DB) Mocking
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            // d.year로 변경된 쿼리 문자열에 대한 준비 호출을 검증합니다.
            ->with($this->stringContains('AND d.year = :year'))
            ->willReturn($stmtMock);

        // 3. Model 인스턴스 생성 및 Mock DB 주입
        $model = new TemperatureCalendarModel($dbMock);

        // 4. 메소드 실행 및 결과 검증
        $actualResult = $model->getDailyAverageTemperature($regionCode, $year, $month);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * DB 오류 발생 시 Exception을 던지는지 테스트
     */
    public function getDailyAverageTemperature_shouldThrowExceptionOnDbError()
    {
        $regionCode = '11000';
        $year = 2025;
        $month = 10;

        // 1. PDO (DB) Mocking: prepare() 호출 시 PDOException을 발생하도록 설정
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException("Simulated DB Connection Error"));

        $model = new TemperatureCalendarModel($dbMock);

        // 2. 예외가 발생하는지 검증
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("기온 데이터 조회 중 서버 내부 오류가 발생했습니다.");

        $model->getDailyAverageTemperature($regionCode, $year, $month);
    }
}