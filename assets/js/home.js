// 홈 전용 스크립트

(async function initHome(){
  // 1) 엘리먼트 참조 (id가 없을 수도 있으므로 fallback 포함)
  const $region = qs('#region');
  const $start  = qs('#start') || qs('input[type="date"][placeholder="시작 날짜"]');
  const $end    = qs('#end')   || qs('input[type="date"][placeholder="종료 날짜"]');

  const $btnCalendar  = qs('#btnCalendar');
  const $btnDateReco  = qs('#btnDateReco');
  const $btnPlaceReco = qs('#btnPlaceReco');
  const $btnFavorites = qs('#btnFavorites');

  const MIN_DATE = '2024-01-01';
  const MAX_DATE = '2024-12-31';

  // min/max 속성 강제 세팅 
  if ($start) {
    $start.min = MIN_DATE;
    $start.max = MAX_DATE;
  }
  if ($end) {
    $end.min = MIN_DATE;
    $end.max = MAX_DATE;
  }

  // 쿼리스트링 날짜가 없는 경우에만 기본값 넣기
  function setDefaultDatesIfEmpty() {
    if (!($start && $end)) return;

    const hasStart = has($start.value);
    const hasEnd   = has($end.value);
    if (hasStart || hasEnd) return;   // 이미 값 있으면(다른 페이지에서 넘어온 경우) 건드리지 않음

    const now = new Date();                      // 오늘 (예: 2025-11-18)
    let base = new Date(2024, now.getMonth(), now.getDate()); // 2024-11-18 이런 식으로 치환

    // 2024-01-01 ~ 2024-12-31 범위 안으로 잘라주기
    const min = new Date('2024-01-01');
    const max = new Date('2024-12-31');
    if (base < min) base = min;
    if (base > max) base = max;

    const yyyy = base.getFullYear();
    const mm   = String(base.getMonth() + 1).padStart(2, '0');
    const dd   = String(base.getDate()).padStart(2, '0');
    const baseStr = `${yyyy}-${mm}-${dd}`;

    // 시작/종료 기본값은 일단 같은 날로
    $start.value = baseStr;
    $end.value   = baseStr;
  }

  setDefaultDatesIfEmpty();

  // region 요소가 없는 다른 페이지에서 home.js를 import했을 때 에러없이 조용히 패스
  if (!$region) {
    console.warn('region <select> not found.');
    return;
  }

  // 2) 지역 옵션 채우기 (첫 번째 placeholder option은 유지)
  //    - 첫 option이 placeholder라면 보존하고, 나머지는 제거
  const firstOpt = $region.options[0];
  const keepFirstAsPlaceholder = firstOpt && firstOpt.disabled;

  // 기존 옵션 비우기 (placeholder는 유지)
  if (keepFirstAsPlaceholder) {
    // 0번 제외하고 모두 제거
    while ($region.options.length > 1) {
      $region.remove(1);
    }
  } else {
    // placeholder가 없다면 새로 추가
    $region.innerHTML = '<option value="" disabled selected>지역 선택</option>';
  }

  try {
    const regions = await fetchJson('mock/regions_20.json'); //  [ {code, name, province} ]
    regions.forEach(r=>{
      const opt = document.createElement('option');
      opt.value = r.code;         // 내부 값(코드)
      opt.textContent = r.province ? `${r.province} ${r.name}` : r.name;   // 사용자 표시명
      $region.appendChild(opt);
    });
  } catch (e) {
    console.error('⚠️ 지역 목록 로드 실패:', e);
    // 최소 fallback
    if ($region.options.length <= 1) {
      $region.insertAdjacentHTML('beforeend',
        '<option value="108">서울</option><option value="159">부산</option>');
    }
  }

  // 3) 인기 조회 지역 Top5 (있으면 표시)
  const $popularList = qs('#popularList');
  if ($popularList) {
    try{
      const popularJson = await fetchJson('mock/popular_regions.json');
      const regions = await fetchJson('mock/regions_20.json'); // 이름 조인용
      const regionMap = Object.fromEntries(regions.map(r => [r.code, r]));

      // {ok:true, data:[...]} 구조이므로 .data 사용
      const popularArr = popularJson.data || popularJson;
      const top5 = popularArr.slice(0, 5);

      // 지역코드 → 지역이름 변환
      $popularList.innerHTML = top5.map(r => {
        const info = regionMap[r.region_code] || {};
        const name = info.province ? `${info.province} ${info.name}` : info.name || r.region_code;
        return `<li>${name} (${r.popular_count})</li>`;
      }).join('');
    } catch (e) {
      console.error('⚠️ 인기지역 로드 실패:', e);
      $popularList.innerHTML = `<li>데이터가 없습니다.</li>`;
    }
  }

  // 4) 버튼 활성화 규칙
  function applyRules(){
    const regionOn = has($region.value);
    const datesOn  = $start && $end && has($start.value) && has($end.value);

    // 시작 날짜와 종료 날짜가 같은 경우 비활성화 처리
    const sameDate = datesOn && ($start.value === $end.value);

    // 규칙:
    // - 지역 X, 날짜 X → 모두 비활성
    // - 지역 O, 날짜 X → 지역추천 비활성 / 캘린더·날짜추천 활성
    // - 지역 X, 날짜 O → 날짜추천 비활성 / 캘린더·지역추천 활성
    // - 지역 O, 날짜 O → 모두 활성
    if ($btnCalendar)  $btnCalendar.disabled  = !(regionOn || datesOn);
    if ($btnDateReco)  $btnDateReco.disabled  = !regionOn;
    if ($btnPlaceReco) $btnPlaceReco.disabled = !datesOn || sameDate;
  }

  applyRules();
  // 값 변경 시 규칙 재적용 (요소가 있을 때만)
  [$region, $start, $end].filter(Boolean).forEach(el=>{
    el.addEventListener('change', applyRules);
    el.addEventListener('input',  applyRules);
  });

  // 즐겨찾기 버튼은 항상 활성화
  if ($btnFavorites) $btnFavorites.disabled = false;

  // 5) 페이지 이동 (쿼리스트링 구성)
  if ($btnCalendar) {  // 캘린더 버튼
    $btnCalendar.addEventListener('click', ()=>{
      // 월별 조회: 시작일 없으면 오늘 기준
      const base = ($start && has($start.value)) ? new Date($start.value) : new Date();
      const y = base.getFullYear();
      const m = String(base.getMonth()+1).padStart(2,'0');

      const params = new URLSearchParams();
      if (has($region.value)) params.set('region', $region.value);
      params.set('year', y);
      params.set('month', m);

      location.href = `calendar_temp.html?${params.toString()}`;        // 한나님이 정하신 파일 이름으로 수정!!!!!!!!!!!!
    });
  }

  if ($btnDateReco) {  // 여행 날짜 추천 버튼
    $btnDateReco.addEventListener('click', ()=>{
      if (!($start && $end)) return;
      const params = new URLSearchParams();
      params.set('start', $start.value);
      params.set('end',   $end.value);
      if (has($region.value)) params.set('region', $region.value);

      location.href = `date_reco.html?${params.toString()}`;
    });
  }

  if ($btnPlaceReco) {  // 여행 장소 추천 버튼
    $btnPlaceReco.addEventListener('click', ()=>{
      const params = new URLSearchParams();
      params.set('region', $region.value);
      if ($start && $end && has($start.value) && has($end.value)) {
        params.set('start', $start.value);
        params.set('end',   $end.value);
        params.set('ignoreRegion', '1'); // 스펙: 지역 무시 플래그
      }
      location.href = `place_reco.html?${params.toString()}`;
    });
  }

  if ($btnFavorites) {
    $btnFavorites.addEventListener('click', () => {
      // 필터를 넘길 필요 없으면 그냥 페이지 이동만:
      location.href = 'bookmark.html';
    });
  }
})();