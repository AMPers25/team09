-- 테스트를 위해 아래 코드 변경 시 참조할 것

-- 지역 코드
-- '090'        '093'        '104'        '108'        '112'
-- '115'        '119'        '131'        '133'        '143'
-- '146'        '152'        '156'        '159'        '162'
-- '168'        '184'        '232'        '239'        '283'



-- Region 테이블의 popular_count 더미값
UPDATE Region SET popular_count = FLOOR(RAND() * 1000)
WHERE region_code IN (
    SELECT region_code FROM (
        SELECT region_code FROM Region ORDER BY RAND() LIMIT 20
    ) AS temp
);

-- Bookmark 테이블 더미값
INSERT INTO Bookmark (session_id, region_code, start_date, end_date) VALUES
('TEST_SESSION_A', '131', '2024-07-01', '2024-07-10'),
('TEST_SESSION_B', '093', '2024-08-01', '2024-08-15'),
('TEST_SESSION_A', '159', '2024-01-01', '2024-01-01'), -- 같은 사용자 A의 두 번째 즐겨찾기
('TEST_SESSION_C', '104', '2024-02-10', '2024-02-20'),
('TEST_SESSION_D', '112', '2024-03-05', '2024-03-15'),
('TEST_SESSION_A', '115', '2024-04-12', '2024-04-22'), -- 같은 사용자 A의 세 번째 즐겨찾기
('TEST_SESSION_E', '119', '2024-05-20', '2024-05-30'),
('TEST_SESSION_F', '133', '2024-06-15', '2024-06-25'),
('TEST_SESSION_G', '143', '2024-09-01', '2024-09-10'),
('TEST_SESSION_H', '146', '2024-10-10', '2024-10-20'),
('TEST_SESSION_I', '152', '2024-11-05', '2024-11-15'),
('TEST_SESSION_J', '156', '2024-12-01', '2024-12-10');
