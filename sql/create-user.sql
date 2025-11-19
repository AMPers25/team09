-- ======================================================================================
-- File: create-user.sql
-- Description: team09 데이터베이스 사용자 생성 스크립트
-- 사용 방법: phpMyAdmin에서 root로 로그인 후 이 스크립트 실행
-- ======================================================================================

-- 사용자 생성 (비밀번호: team09)
CREATE USER IF NOT EXISTS 'team09'@'localhost' IDENTIFIED BY 'team09';
CREATE USER IF NOT EXISTS 'team09'@'127.0.0.1' IDENTIFIED BY 'team09';

-- 데이터베이스 권한 부여
GRANT ALL PRIVILEGES ON team09.* TO 'team09'@'localhost';
GRANT ALL PRIVILEGES ON team09.* TO 'team09'@'127.0.0.1';

-- 권한 적용
FLUSH PRIVILEGES;

-- 확인
SELECT User, Host FROM mysql.user WHERE User = 'team09';

