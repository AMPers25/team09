/* 3-3 여행 적합 장소 추천 페이지 */
(function(){
  const $  = (s, el=document)=>el.querySelector(s);
  const has = v => v!==undefined && v!==null && String(v).trim()!=='';

  // 공통 fetchJson(app.js) 사용. 없으면 폴백.
  const fetchJson = window.fetchJson || (async url=>{
    const r = await fetch(url, { headers:{'Accept':'application/json'} });
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
  });

  // -------------------------------
  // 1) 쿼리 파라미터 읽기 (start / end 만 사용)
  // -------------------------------
  const params = new URLSearchParams(location.search);
  const startParam = params.get('start') || '';
  const endParam   = params.get('end')   || '';

  const $start = $('#start');
  const $end   = $('#end');

  const MIN_DATE = '2024-01-01';
  const MAX_DATE = '2024-12-31';

  // -------------------------------
  // 2) 상단 날짜 필드에 값 세팅 + 기본값
  // -------------------------------
  function setDates() {
    // 1) min/max 제한 먼저 건다
    if ($start) {
      $start.min = MIN_DATE;
      $start.max = MAX_DATE;
    }
    if ($end) {
      $end.min = MIN_DATE;
      $end.max = MAX_DATE;
    }

    // 2) 쿼리스트링에 start/end가 있으면 그 값을 우선 적용
    if ($start && has(startParam)) $start.value = startParam;
    if ($end   && has(endParam))   $end.value   = endParam;

    // 3) 둘 다 비어 있는 경우에만 기본값(2024년 오늘 월/일 기준) 설정
    const noStart = !$start || !has($start.value);
    const noEnd   = !$end   || !has($end.value);

    if (noStart && noEnd && $start && $end) {
      const now  = new Date();                         // 예: 2025-11-18
      let base   = new Date(2024, now.getMonth(), now.getDate()); // 2024-11-18

      const minD = new Date(MIN_DATE);
      const maxD = new Date(MAX_DATE);
      if (base < minD) base = minD;
      if (base > maxD) base = maxD;

      const yyyy = base.getFullYear();
      const mm   = String(base.getMonth() + 1).padStart(2, '0');
      const dd   = String(base.getDate()).padStart(2, '0');
      const baseStr = `${yyyy}-${mm}-${dd}`;

      $start.value = baseStr;
      $end.value   = baseStr;
    }
  }


  // -------------------------------
  // 3) 날짜 바뀌면 쿼리스트링 업데이트 후 페이지 리로드
  // -------------------------------
  function reloadWith() {
    const q = new URLSearchParams();
    if ($start && has($start.value)) q.set('start', $start.value);
    if ($end   && has($end.value))   q.set('end',   $end.value);
    location.href = `place_reco.html?${q.toString()}`;
  }

  function bindGoButton() {
    const btn = document.querySelector("#goFilter");
    if (!btn) return;

    btn.addEventListener("click", () => {
        reloadWith({});
    });
  }


  // -------------------------------
  // 4) 데이터 로드
  //    
  // -------------------------------
  async function loadData() {
    let rows = [];
    try {
      const start_date = $start.value;
      const end_date = $end.value;

      const res = await fetchJson(`/team09/api/recommend/best-region/${start_date}/${end_date}`);
      rows = res.data || [];
    } catch (e) {
      try {
        const mock = await fetchJson('mock/place_reco_sample.json');
        rows = Array.isArray(mock.data) ? mock.data : mock;
      } catch {
        rows = [];
      }
    }
    return rows;
  }

  // -------------------------------
  // 5) 렌더링
  // -------------------------------

  function renderList(rows){
    const $list  = $('#list');
    const $empty = $('#empty');
    if (!rows || rows.length===0){
      $list.innerHTML = '';
      if($empty) $empty.hidden = false;
      return;
    }
    if($empty) $empty.hidden = true;

    $list.innerHTML = rows.map((r, idx)=>`
      <li class="li-row" data-region="${r.region_code}">
        <button class="icon-btn star" title="즐겨찾기 추가" aria-label="즐겨찾기 추가">
          <i class="fa-regular fa-star"></i>
        </button>

        <div>
          <div class="li-range">${idx + 1}.  ${r.province ? `${r.province} ` : ''}${r.region_name}</div>
        </div>

        <div></div>

        <button class="icon-btn cal" title="캘린더 보기" aria-label="캘린더 보기">
          <i class="fa-regular fa-calendar-days"></i>
        </button>
      </li>
    `).join('');
  }

  // -------------------------------
  // 5) 이벤트(별/캘린더)
  // -------------------------------
async function onClickList(e){
    const li = e.target.closest('.li-row');
    if (!li) return;

    const regionCode = li.dataset.region;
    const startDate  = $start && has($start.value) ? $start.value : '';
    const endDate    = $end   && has($end.value)   ? $end.value   : '';

    // 5-a) 캘린더 이동
    if (e.target.closest('.cal')){
      const base = startDate ? new Date(startDate) : new Date();
      const y = base.getFullYear();
      const m = String(base.getMonth()+1).padStart(2,'0');
      const q = new URLSearchParams();
      if (has(regionCode)) q.set('region_code', regionCode);
      q.set('year', y); q.set('month', m);
      location.href = `temp-calendar.html?${q.toString()}`;
      return;
    }

    // 5-b) 즐겨찾기(별)
    const starBtn = e.target.closest('.star');
    if (!starBtn) return;

    // 날짜가 없으면 즐겨찾기 못 하게 막기 (API B안에 필요해서)
    if (!has(startDate) || !has(endDate)) {
    alert('즐겨찾기에 저장하려면 날짜 범위를 먼저 선택해주세요.');
    return;
    }

    const isActive = starBtn.classList.contains('active');

    // 이미 활성 상태면 = 삭제
    if (isActive) {
        // 1) UI를 먼저 비활성화(낙관적)
        starBtn.classList.remove('active');
        starBtn.innerHTML = '<i class="fa-regular fa-star"></i>';

        try {
            // B안: region_code + start_date + end_date로 삭제
            const url = new URL(`/team09/api/bookmarks`, window.location.origin);
            url.searchParams.set('region_code', regionCode);
            url.searchParams.set('start_date', startDate);
            url.searchParams.set('end_date',   endDate);

            const res = await fetch(url.toString(), { method: 'DELETE' , credentials: 'include'});
            if (res.status !== 200 ) throw new Error('delete failed');

            // // localStorage에도 저장해뒀다면 같이 제거 (선택)
            // const key = 'bookmarks';
            // const cur = JSON.parse(localStorage.getItem(key) || '[]');
            // const next = cur.filter(b =>
            // !(String(b.region_code)===String(regionCode) &&
            // b.start_date===startDate &&
            // b.end_date===endDate)
            // );
            // localStorage.setItem(key, JSON.stringify(next));
        } catch (err) {
            // 실패 시 UI 롤백
            starBtn.classList.add('active');
            starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';
            alert('즐겨찾기 삭제에 실패했습니다. 잠시 후 다시 시도해주세요.');
        }
        return;
    }

    // 비활성 상태면 = 추가
    starBtn.classList.add('active');
    starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';

    const payload = {
        region_code: regionCode,
        start_date : startDate,
        end_date   : endDate
    };

    try {
        // 즐겨찾기 추가 API (POST)
        const r = await fetch(`/team09/api/bookmarks`, {
        method:'POST',
        credentials: 'include',
        headers:{ 'Content-Type':'application/json' },
        body: JSON.stringify(payload)
        });

        if (r.status !== 200 && r.status !== 201) {
          throw new Error('save failed');
        }

        // (선택) 서버가 bookmark_id를 반환한다면 여기서 li.dataset.id = ... 해도 됨
        // const resJson = await r.json();
        // if (resJson.bookmark_id) li.dataset.bookmarkId = resJson.bookmark_id;

        // // 로컬스토리지에도 같이 저장해두고 싶으면:
        // const key = 'bookmarks';
        // const cur = JSON.parse(localStorage.getItem(key) || '[]');
        // cur.push(payload);
        // localStorage.setItem(key, JSON.stringify(cur));
    } catch (err) {
        console.error(err);
        // 실패 시 UI 롤백
        starBtn.classList.remove('active');
        starBtn.innerHTML = '<i class="fa-regular fa-star"></i>';
        alert('즐겨찾기 추가에 실패했습니다. 잠시 후 다시 시도해주세요.');
    }
}
  

  // -------------------------------
  // 초기화
  // -------------------------------
  (async function start(){
    setDates();
    bindGoButton();

    // 1) 여행 적합 날짜 추천 데이터
    const rows = await loadData();

    // 2) 즐겨찾기 목록 불러오기
    let bookmarks = [];
    try {
      const bmRes = await fetchJson(`/team09/api/bookmarks`);
      bookmarks = bmRes.data || [];
    } catch (e) {
      console.warn("즐겨찾기 목록 불러오기 실패:", e);
    }

    // 3) rows 와 bookmarks 매칭 후 'active' 설정
    rows.forEach(r => {
      const exists = bookmarks.some(bm =>
        String(bm.region_code) === String(r.region_code) &&
        bm.start_date === r.start_date &&
        bm.end_date === r.end_date
      );
      r.isFavorite = exists;
    });
    // 4) 랜더링
    renderList(rows);

    // 5) 렌더 후 별 UI 반영
    document.querySelectorAll(".li-row").forEach(li => {
      const start = li.dataset.start;
      const end   = li.dataset.end;
      const regionCode = li.dataset.region;

      const matched = bookmarks.some(bm =>
        bm.start_date === start &&
        bm.end_date   === end &&
        String(bm.region_code) === String(regionCode)
      );

      if (matched) {
        const starBtn = li.querySelector(".star");
        starBtn.classList.add("active");
        starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';
      }
    });

    $('#list').addEventListener('click', onClickList);
  })();
})();