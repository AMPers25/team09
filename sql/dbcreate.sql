-- ======================================================================================
-- File: dbcreate.sql
-- Author: Yeonsu Kim (Backend Developer)
-- Date: 2025-10-04
-- 설명: 
-- 프로젝트 요구사항 2-(2)에 따른 dbcreate.sql 스크립트
-- 데이터베이스 사용자 ID, 비밀번호, DB 이름은 프로젝트 요구사항 3-(1)에 따라 'team09'로 가정
-- =======================================================================================

-- 데이터베이스 생성 및 사용
CREATE DATABASE IF NOT EXISTS team09;
USE team09;

-- ***********************************************
-- 1. 차원 테이블 (Dimensions)
-- ***********************************************

-- 1-1. Region (지역) 테이블: PK, Index 포함
CREATE TABLE Region (
    region_code CHAR(5) PRIMARY KEY COMMENT '지역 코드 (PK, 예: 01~75)',
    region_name VARCHAR(50) NOT NULL COMMENT '지역명 (예: 서울, 제주)',
    latitude DECIMAL(10, 7) COMMENT '위도',
    longitude DECIMAL(10, 7) COMMENT '경도',
    query_count INT DEFAULT 0 COMMENT '지역별 검색 횟수 (랭킹 분석용)'
) ENGINE=InnoDB;

-- 지역명 Index (검색 성능 향상)
CREATE INDEX idx_region_name ON Region (region_name);

-- 1-2. DateDim (날짜) 테이블: PK 포함
CREATE TABLE DateDim (
    date_id DATE PRIMARY KEY COMMENT '날짜 (PK, YYYY-MM-DD)',
    year SMALLINT NOT NULL,
    month TINYINT NOT NULL,
    day TINYINT NOT NULL,
    is_weekend BOOLEAN NOT NULL DEFAULT FALSE COMMENT '주말 여부',
    is_holiday BOOLEAN NOT NULL DEFAULT FALSE COMMENT '공휴일 여부'
) ENGINE=InnoDB;

-- ***********************************************
-- 2. 응용 테이블 (Application Table)
-- ***********************************************

-- 2-1. TripList (여행 후보지, 날짜 list): PK, FK, Index 포함
CREATE TABLE TripList (
    list_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '목록 ID (PK)',
    region_code CHAR(5) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    
    -- 외래 키 (FK) 정의
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (start_date) REFERENCES DateDim(date_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 검색 및 관계 유지를 위한 Index
CREATE INDEX idx_triplist_region ON TripList (region_code);
CREATE INDEX idx_triplist_start_date ON TripList (start_date);

-- ***********************************************
-- 3. 사실 테이블 (Fact Tables) - 복합 PK, FK 포함
-- ***********************************************

-- 3-1. AirQuality (미세먼지)
CREATE TABLE AirQuality (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    pm10 INT COMMENT '미세먼지 농도 (PM10)',
    pm25 INT COMMENT '초미세먼지 농도 (PM2.5)',
    
    -- 복합 기본 키 (PK)
    PRIMARY KEY (region_code, date_id),
    
    -- 외래 키 (FK) 정의
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3-2. Temperature (기온)
CREATE TABLE Temperature (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    avg_temp DECIMAL(3, 1) COMMENT '평균기온',
    max_temp DECIMAL(3, 1) COMMENT '최고기온',
    min_temp DECIMAL(3, 1) COMMENT '최저기온',
    daily_temp_range DECIMAL(3, 1) COMMENT '일교차',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3-3. Rain (강수 및 기타)
CREATE TABLE Rain (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    daily_rainfall DECIMAL(5, 2) COMMENT '일 강수량 (mm)',
    humidity INT COMMENT '평균 습도 (%)',
    wind_speed DECIMAL(4, 2) COMMENT '평균 풍속 (m/s)',
    cloud_cover INT COMMENT '평균 운량 (옥타)',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3-4. LifeWeatherIndex (생활 기상 지수)
CREATE TABLE LifeWeatherIndex (
    region_code CHAR(5) NOT NULL,
    date_id DATE NOT NULL,
    uv_index TINYINT COMMENT '자외선 지수',
    sensory_temp DECIMAL(3, 1) COMMENT '체감온도',
    discomfort_index DECIMAL(3, 1) COMMENT '불쾌지수',
    
    PRIMARY KEY (region_code, date_id),
    
    FOREIGN KEY (region_code) REFERENCES Region(region_code) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (date_id) REFERENCES DateDim(date_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3-5. WeatherAlert (기상 주의보) - 단일 PK
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


