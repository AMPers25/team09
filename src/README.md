# 핵심 PHP 로직
DB 연결, 예외 처리, 분석 기능 등 공통 함수와 핵심 로직 저장
PHP 버전: 8.2.12

# Source Code (src) Architecture

이 디렉토리는 **PHP 기반의 MVC (Model-View-Controller) 패턴**에 따라 구성된 애플리케이션의 핵심 비즈니스 로직 및 제어 흐름을 담당합니다. Composer Autoloading (PSR-4)을 사용합니다.

---

## 1. 📂 계층 구조 및 역할 (MVC)

| 디렉토리 | MVC 계층 | 주요 책임 | 예시 파일 |
| :--- | :--- | :--- | :--- |
| **controller** | **Controller (C)** | 사용자 요청(Request) 접수, 입력값 검증, Model 호출, 최종 응답(JSON) 반환. | CleanDayController.php |
| **model** | **Model (M)** | 핵심 비즈니스 로직, 데이터 계산(집계/랭킹), DB 접근 및 트랜잭션 관리. | CleanDayModel.php, RegionRankingModel.php |
| **database** | **Data Access** | DB 연결 생성 및 관리, SQL 쿼리 실행 추상화. | db_connect.php, sql_wrapper.php |
| **util** | **Utility** | 애플리케이션 전반에 필요한 공통 기능(오류 처리, 세션 관리 등). | exception_handler.php |

---

## 2. 📊 Model 세분화: 고급 분석 기능 분리 (핵심)

프로젝트 요구사항인 **4가지 고급 분석 기능**을 충족하기 위해, Model 클래스를 **단일 책임 원칙(SRP)**에 따라 기능별로 분리했습니다.

| 기능 ID | Model 클래스 | 분석 목적 및 충족 요구사항 |
| :---: | :--- | :--- |
| **3-1** | `CleanDayModel.php` | **PM10 연속 클린 기간 추천.** Windowing (Gap-and-Island) 및 Ranking 구현. |
| **3-2** | `BestPeriodModel.php` | **주간 여행 적합 기간 추천.** 복합 그룹핑(Aggregate) 및 Ranking 구현. |
| **3-3** | `BestRegionModel.php` | **기간별 지역 랭킹 추천.** Ranking, Aggregates 구현 (province 정보 포함). |

---

## 3. 🛡️ 테스트 및 TDD 지원

* **테스트 구조:** 단위 테스트 파일은 `test/model/` 및 `test/controller/` 디렉토리에 위치합니다.
* **1차 검증 방식:** 모든 Model/Controller는 **Mocking** 기법을 사용하여 **DB 연결 없이** 비즈니스 로직과 오류 핸들링 경로의 정확성(TDD)을 검증했습니다.