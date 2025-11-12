# Mock API 서버 테스트 가이드

백엔드가 준비되지 않았을 때 프론트엔드를 테스트하기 위한 Mock API 서버입니다.

## 빠른 시작

1. **의존성 설치 및 서버 실행**
   ```bash
   cd test
   npm install
   npm start
   ```

2. **테스트 페이지 열기**
   - 브라우저에서 http://localhost:3000/test/test-page.html 접속
   - 또는 직접 캘린더 페이지 접속: http://localhost:3000/pages/temp-calendar.html?region_code=108&month=09

## 방법 1: Node.js Express 서버 (권장)

### 사전 요구사항
- Node.js 설치 (https://nodejs.org/)
- npm 또는 yarn

### 설치 및 실행

1. **의존성 설치**
   ```bash
   cd test
   npm install express
   ```

2. **서버 실행**
   ```bash
   node mock-api-server.js
   ```

3. **브라우저에서 테스트**
   - 기온 캘린더: http://localhost:3000/pages/temp-calendar.html?region_code=108&month=09
   - 강수량 캘린더: http://localhost:3000/pages/rain-calendar.html?region_code=108&month=09
   - 경보특보 캘린더: http://localhost:3000/pages/alert-calendar.html?region_code=108&month=09

### 테스트 시나리오

1. **기본 테스트**
   - URL에 `region_code`와 `month` 파라미터가 있는지 확인
   - 캘린더가 정상적으로 로드되는지 확인
   - API 호출이 성공하는지 브라우저 개발자 도구 Network 탭에서 확인

2. **탭 전환 테스트**
   - '기온', '강수량', '경보특보' 버튼 클릭
   - 각 캘린더 페이지로 이동하면서 `region_code`와 `month`가 유지되는지 확인

3. **월 변경 테스트**
   - 화살표 버튼으로 월 변경
   - URL이 업데이트되는지 확인
   - 새로운 월의 데이터가 로드되는지 확인

4. **에러 케이스 테스트**
   - `region_code` 없이 접속: 에러 메시지 확인
   - 잘못된 `region_code`로 접속: 폴백 데이터 사용 확인

### 지원하는 API 엔드포인트

- `GET /api/temp-calendar?region_code={code}&month={mm}` - 기온 캘린더 데이터
- `GET /api/rain-calendar?region_code={code}&month={mm}` - 강수량 캘린더 데이터
- `GET /api/alert-calendar?region_code={code}&month={mm}` - 경보특보 캘린더 데이터
- `GET /api/day-info-link?region_code={code}&date={yyyy-mm-dd}` - 일별 상세 정보 링크

### region_code 예시

- `108` - 서울
- `159` - 부산
- `112` - 인천
- `143` - 대구
- `156` - 광주
- `133` - 대전
- `152` - 울산
- `239` - 세종
- `184` - 제주

## 방법 2: 브라우저에서 직접 테스트 (간단한 방법)

브라우저 개발자 도구의 Console에서 다음 코드를 실행하면 fetch를 intercept하여 Mock 응답을 반환합니다:

```javascript
// 브라우저 콘솔에 붙여넣기
(function() {
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (url.includes('/api/temp-calendar')) {
            const params = new URLSearchParams(url.split('?')[1]);
            const regionCode = params.get('region_code');
            const month = parseInt(params.get('month'), 10);
            const year = 2024;
            const daysInMonth = new Date(year, month, 0).getDate();
            const days = [];
            const weatherTypes = ['맑음', '구름', '흐림', '흐린', '비', '눈'];
            
            for (let day = 1; day <= daysInMonth; day++) {
                const minTemp = Math.floor(Math.random() * 8) + 14;
                const maxTemp = minTemp + Math.floor(Math.random() * 9) + 4;
                days.push({
                    day,
                    isHoliday: [13, 14, 15].includes(day),
                    weather: weatherTypes[Math.floor(Math.random() * weatherTypes.length)],
                    minTemp,
                    maxTemp
                });
            }
            
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve({
                    region_code: regionCode,
                    region: '서울',
                    year,
                    month,
                    avgMinTemp: 18,
                    avgMaxTemp: 25,
                    monthMinTemp: 14,
                    monthMaxTemp: 30,
                    weeklyAverages: [],
                    days
                })
            });
        }
        return originalFetch.apply(this, arguments);
    };
    console.log('✅ Mock API 활성화됨');
})();
```

## 문제 해결

### 포트가 이미 사용 중인 경우
`mock-api-server.js` 파일에서 `PORT` 값을 변경하세요 (예: 3001, 8080).

### CORS 오류가 발생하는 경우
서버가 정상적으로 실행되고 있는지 확인하고, 브라우저에서 `http://localhost:3000`으로 접속했는지 확인하세요.

### API 호출이 실패하는 경우
1. 브라우저 개발자 도구의 Network 탭에서 요청 URL 확인
2. 서버 콘솔에서 요청이 로그되는지 확인
3. `region_code`와 `month` 파라미터가 올바른지 확인

