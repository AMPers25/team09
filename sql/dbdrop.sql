-- ======================================================================================
-- File: dbdrop.sql
-- Author: Hannah Kang (Frontend Developer)
-- Update: 2025-11-23
-- 설명:
-- 프로젝트 요구사항 2-(3)에 따른 dbdrop.sql 스크립트
-- 샘플 데이터베이스에 포함된 모든 테이블을 삭제하기 위한 DROP TABLE 구문을 포함
-- ======================================================================================




-- 데이터베이스 생성 및 사용
USE team09;

DROP TABLE IF EXISTS Bookmark;
DROP TABLE IF EXISTS AirQuality;
DROP TABLE IF EXISTS Temperature;
DROP TABLE IF EXISTS Rain;
DROP TABLE IF EXISTS TravelIndex;
DROP TABLE IF EXISTS WeatherAlert;
DROP TABLE IF EXISTS WeatherStatusDim;
DROP TABLE IF EXISTS Region;
DROP TABLE IF EXISTS DateDim;
