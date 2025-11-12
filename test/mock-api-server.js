/**
 * Mock API Server for Calendar Testing
 * 
 * 사용법:
 * 1. Node.js가 설치되어 있어야 합니다 (node --version으로 확인)
 * 2. 터미널에서 이 파일이 있는 디렉토리로 이동
 * 3. npm install express (또는 yarn add express)
 * 4. node mock-api-server.js 실행
 * 5. 브라우저에서 http://localhost:3000/pages/temp-calendar.html?region_code=108&month=09 접속
 */

const express = require('express');
const path = require('path');
const app = express();
const PORT = 3000;

// CORS 허용
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Accept');
    next();
});

// 정적 파일 서빙 (HTML, CSS, JS 등)
app.use(express.static(path.join(__dirname, '..')));

// Mock 데이터 생성 함수들
function generateTempCalendarData(regionCode, month) {
    const year = 2024;
    const daysInMonth = new Date(year, month, 0).getDate();
    const days = [];
    const holidays = [13, 14, 15];
    const weatherTypes = ['맑음', '구름', '흐림', '흐린', '비', '눈'];
    
    let totalMin = 0;
    let totalMax = 0;
    
    for (let day = 1; day <= daysInMonth; day++) {
        const minTemp = Math.floor(Math.random() * 8) + 14;
        const maxTemp = minTemp + Math.floor(Math.random() * 9) + 4;
        totalMin += minTemp;
        totalMax += maxTemp;
        
        const weather = weatherTypes[Math.floor(Math.random() * weatherTypes.length)];
        
        days.push({
            day,
            isHoliday: holidays.includes(day),
            weather,
            minTemp,
            maxTemp
        });
    }
    
    return {
        region_code: regionCode,
        region: getRegionName(regionCode),
        year,
        month,
        avgMinTemp: Math.round(totalMin / daysInMonth),
        avgMaxTemp: Math.round(totalMax / daysInMonth),
        monthMinTemp: Math.min(...days.map(d => d.minTemp)),
        monthMaxTemp: Math.max(...days.map(d => d.maxTemp)),
        weeklyAverages: [],
        days
    };
}

function generateRainCalendarData(regionCode, month) {
    const year = 2024;
    const daysInMonth = new Date(year, month, 0).getDate();
    const days = [];
    const holidays = [13, 14, 15];
    const weatherTypes = ['맑음', '구름', '흐림', '흐린', '비', '눈'];
    
    let totalRain = 0;
    
    for (let day = 1; day <= daysInMonth; day++) {
        const rainfall = Number((Math.random() * 20).toFixed(1));
        totalRain += rainfall;
        
        days.push({
            day,
            isHoliday: holidays.includes(day),
            rainfall,
            weather: weatherTypes[Math.floor(Math.random() * weatherTypes.length)]
        });
    }
    
    return {
        region_code: regionCode,
        region: getRegionName(regionCode),
        year,
        month,
        avgRainfall: Number((totalRain / daysInMonth).toFixed(1)),
        weeklyAverages: [],
        days
    };
}

function generateAlertCalendarData(regionCode, month) {
    const year = 2024;
    const daysInMonth = new Date(year, month, 0).getDate();
    const days = [];
    const holidays = [13, 14, 15];
    const weatherTypes = ['맑음', '구름', '흐림', '흐린', '비', '눈'];
    const alertTypes = [
        '대설주의보', '대설경보', '한파주의보', '한파경보',
        '강풍주의보', '강풍경보', '풍랑주의보', '풍랑경보',
        '건조주의보', '건조경보', '호우주의보', '호우경보',
        '폭염주의보', '폭염경보', '폭풍해일주의보', '폭풍해일경보',
        '태풍주의보', '태풍경보'
    ];
    
    for (let day = 1; day <= daysInMonth; day++) {
        let alertType = null;
        if (Math.random() < 0.2) {
            alertType = alertTypes[Math.floor(Math.random() * alertTypes.length)];
        }
        
        days.push({
            day,
            isHoliday: holidays.includes(day),
            weather: weatherTypes[Math.floor(Math.random() * weatherTypes.length)],
            alertType
        });
    }
    
    return {
        region_code: regionCode,
        region: getRegionName(regionCode),
        year,
        month,
        days
    };
}

function getRegionName(regionCode) {
    const regionMap = {
        '108': '서울',
        '159': '부산',
        '112': '인천',
        '143': '대구',
        '156': '광주',
        '133': '대전',
        '152': '울산',
        '239': '세종',
        '184': '제주'
    };
    return regionMap[regionCode] || `지역${regionCode}`;
}

// API 엔드포인트들
app.get('/api/temp-calendar', (req, res) => {
    const regionCode = req.query.region_code;
    const month = parseInt(req.query.month, 10);
    
    if (!regionCode) {
        return res.status(400).json({ error: 'region_code 파라미터가 필요합니다.' });
    }
    
    if (!month || month < 1 || month > 12) {
        return res.status(400).json({ error: '유효한 month 파라미터가 필요합니다 (1-12).' });
    }
    
    console.log(`[Mock API] temp-calendar 요청: region_code=${regionCode}, month=${month}`);
    const data = generateTempCalendarData(regionCode, month);
    res.json(data);
});

app.get('/api/rain-calendar', (req, res) => {
    const regionCode = req.query.region_code;
    const month = parseInt(req.query.month, 10);
    
    if (!regionCode) {
        return res.status(400).json({ error: 'region_code 파라미터가 필요합니다.' });
    }
    
    if (!month || month < 1 || month > 12) {
        return res.status(400).json({ error: '유효한 month 파라미터가 필요합니다 (1-12).' });
    }
    
    console.log(`[Mock API] rain-calendar 요청: region_code=${regionCode}, month=${month}`);
    const data = generateRainCalendarData(regionCode, month);
    res.json(data);
});

app.get('/api/alert-calendar', (req, res) => {
    const regionCode = req.query.region_code;
    const month = parseInt(req.query.month, 10);
    
    if (!regionCode) {
        return res.status(400).json({ error: 'region_code 파라미터가 필요합니다.' });
    }
    
    if (!month || month < 1 || month > 12) {
        return res.status(400).json({ error: '유효한 month 파라미터가 필요합니다 (1-12).' });
    }
    
    console.log(`[Mock API] alert-calendar 요청: region_code=${regionCode}, month=${month}`);
    const data = generateAlertCalendarData(regionCode, month);
    res.json(data);
});

app.get('/api/day-info-link', (req, res) => {
    const regionCode = req.query.region_code || req.query.region;
    const date = req.query.date;
    
    if (!regionCode || !date) {
        return res.status(400).json({ error: 'region_code와 date 파라미터가 필요합니다.' });
    }
    
    console.log(`[Mock API] day-info-link 요청: region_code=${regionCode}, date=${date}`);
    const url = `/pages/day-info.html?region_code=${regionCode}&date=${date}`;
    res.json({ url });
});

// 서버 시작
app.listen(PORT, () => {
    console.log(`\n🚀 Mock API 서버가 시작되었습니다!`);
    console.log(`📍 서버 주소: http://localhost:${PORT}`);
    console.log(`\n📋 테스트 URL 예시:`);
    console.log(`   - 기온 캘린더: http://localhost:${PORT}/pages/temp-calendar.html?region_code=108&month=09`);
    console.log(`   - 강수량 캘린더: http://localhost:${PORT}/pages/rain-calendar.html?region_code=108&month=09`);
    console.log(`   - 경보특보 캘린더: http://localhost:${PORT}/pages/alert-calendar.html?region_code=108&month=09`);
    console.log(`\n💡 region_code 예시: 108(서울), 159(부산), 112(인천), 184(제주)`);
    console.log(`💡 month: 01~12 (두 자리 숫자)\n`);
});

