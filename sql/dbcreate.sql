-- ======================================================================================
-- File: dbcreate.sql
-- Author: Yeonsu Kim (Backend Developer)
-- Update: 2025-11-04
-- 설명: 
-- 프로젝트 요구사항 2-(2)에 따른 dbcreate.sql 스크립트
-- 데이터베이스 사용자 ID, 비밀번호, DB 이름은 프로젝트 요구사항 3-(1)에 따라 'team09'로 설정
-- =======================================================================================

-- 데이터베이스 생성 및 사용
CREATE DATABASE IF NOT EXISTS team09;
USE team09;

-- 2. team09 사용자 생성 (비밀번호: team09)
CREATE USER IF NOT EXISTS 'team09'@'localhost' IDENTIFIED BY 'team09';

-- 3. team09 사용자에게 team09 데이터베이스에 대한 모든 권한 부여
GRANT ALL PRIVILEGES ON team09.* TO 'team09'@'localhost';

-- 4. 권한 테이블 새로고침
FLUSH PRIVILEGES;

-- ***********************************************
-- 1. 차원 테이블 (Dimensions)
-- ***********************************************

-- 1-1. Region (지역) 테이블
CREATE TABLE Region (
    region_code CHAR(5) PRIMARY KEY COMMENT '지역 코드 (PK, 예: 01~75)',
    region_name VARCHAR(50) NOT NULL COMMENT '지역명 (예: 서울, 제주)',
    province VARCHAR(20) COMMENT '광역자치단체명 (광역시 등은 NULL)',
    popular_count INT DEFAULT 0 COMMENT '지역별 검색 횟수 (랭킹 분석용)'
) ENGINE=InnoDB;

-- 지역명 Index (검색 성능 향상)
CREATE UNIQUE INDEX idx_region_name ON Region (region_name);

-- 1-2. DateDim (날짜) 테이블
CREATE TABLE DateDim (
    date_id DATE PRIMARY KEY COMMENT '날짜 (PK, YYYY-MM-DD)',
    month TINYINT NOT NULL,
    day TINYINT NOT NULL,
    is_holiday BOOLEAN NOT NULL DEFAULT FALSE COMMENT '공휴일 여부'
) ENGINE=InnoDB;

-- 1-3. WeatherStatusDim (기상 현상 차원) 테이블
CREATE TABLE WeatherStatusDim (
    status_code CHAR(2) PRIMARY KEY COMMENT '기상 현상 코드 (PK, 예: 91, 84)',
    status_name VARCHAR(50) NOT NULL COMMENT '기상 현상 (예: 맑음, 비, 흐림)'
) ENGINE=InnoDB;

-- 1-4. Bookmark (즐겨찾기) 테이블
CREATE TABLE Bookmark (
    bookmark_id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'PK. 즐겨찾기 ID',
    session_id VARCHAR(255) NOT NULL COMMENT '익명 사용자 식별자 (PHP 세션 ID)',
    region_code CHAR(5) NOT NULL COMMENT 'Region 테이블의 FK. 즐겨찾기 지역 코드',
    start_date DATE NOT NULL COMMENT 'DateDim 테이블의 FK. 즐겨찾기 시작 날짜',
    end_date DATE NOT NULL COMMENT 'DateDim 테이블의 FK. 즐겨찾기 종료 날짜',
    
    -- 외래 키 (FK) 정의
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (start_date) REFERENCES DateDim(date_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (end_date) REFERENCES DateDim(date_id) ON DELETE RESTRICT ON UPDATE CASCADE,

    -- 동일 기간/지역의 중복 즐겨찾기 방지
    UNIQUE KEY uk_bookmark (region_code, start_date, end_date)
) ENGINE=InnoDB COMMENT='여행지 즐겨찾기 목록';

-- ***********************************************
-- 2. 사실 테이블 (Fact Tables)
-- ***********************************************

-- 2-1. AirQuality (미세먼지)
CREATE TABLE AirQuality (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    pm10 INT COMMENT '미세먼지 농도 (PM10)',
    
    -- 복합 기본 키 (PK)
    PRIMARY KEY (region_code, date_id),
    
    -- 외래 키 (FK) 정의
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2-2. Temperature (기온)
CREATE TABLE Temperature (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    avg_temp DECIMAL(4, 1) COMMENT '평균기온',
    max_temp DECIMAL(4, 1) COMMENT '최고기온',
    min_temp DECIMAL(4, 1) COMMENT '최저기온',
    daily_temp_range DECIMAL(4, 1) COMMENT '일교차',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2-3. Rain (강수 및 기타)
CREATE TABLE Rain (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    status_code CHAR(3) NOT NULL COMMENT '기상 현상 코드 (FK)',
    daily_rainfall DECIMAL(6, 2) COMMENT '일 강수량 (mm)',
    humidity DECIMAL(4,1) COMMENT '평균 습도 (%)',
    wind_speed DECIMAL(4, 1) COMMENT '평균 풍속 (m/s)',
    cloud_cover DECIMAL(3,1) COMMENT '평균 전운량 (1/10)',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (status_code) REFERENCES WeatherStatusDim(status_code) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2-4. TravelIndex (여행 적합 지수)
CREATE TABLE TravelIndex (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    travel_index_score DECIMAL(5, 2) NOT NULL COMMENT '종합 여행 지수 점수',
    temp_score INT NOT NULL COMMENT '기온 점수',
    clear_score INT NOT NULL COMMENT '청명도 점수 (미세먼지, 구름양)',
    dry_score INT NOT NULL COMMENT '건조/강수 점수 (강수량, 풍속)',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2-5. WeatherAlert (기상 주의보)
CREATE TABLE WeatherAlert (
    alert_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '경보 ID (PK)',
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    alert_time TIME COMMENT '특보 발효 시간',
    alert_type VARCHAR(20) NOT NULL COMMENT '특보 타입 (예: 폭염, 한파)',
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- WeatherAlert의 복합 Index (검색용)
CREATE INDEX idx_alert_search ON WeatherAlert (region_code, date_id);


