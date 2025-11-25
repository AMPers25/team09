/* 3-2 여행 적합 날짜 추천 페이지 */
(function(){
  const $  = (s, el=document)=>el.querySelector(s);
  const has = v => v!==undefined && v!==null && String(v).trim()!=='';

  // 공통 fetchJson(app.js) 사용. 없으면 폴백.
  const fetchJson = window.fetchJson || (async url=>{
    const r = await fetch(url, { headers:{'Accept':'application/json'} });
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
  });
  const REGIONS = window.REGION_OPTIONS || [];

  // -------------------------------
  // 1) 쿼리 파라미터 읽기/쓰기
  // -------------------------------
  const params = new URLSearchParams(location.search);
  const region = params.get('region') || '';

  // -------------------------------
  // 2) 상단 컨트롤 바 채우기
  // -------------------------------
  const $region = $('#region');
  
  async function fillRegions(){
    while($region.options.length>1) $region.remove(1);
    REGIONS.forEach(r=>{
      const opt = document.createElement('option');
      opt.value = r.code;
      opt.textContent = r.province ? `${r.province} ${r.name}` : r.name;
      $region.appendChild(opt);
    });
    if (has(region)) $region.value = region;
  }



  function reloadWith(next){
    const q = new URLSearchParams({
      region: next.region || $region.value || ''
    });
    location.href = `date_reco.html?${q.toString()}`;
  }

  // 변경 이벤트 → 즉시 리로드
  function bindGoButton() {
    const btn = document.querySelector("#goFilter");
    if (!btn) return;

    btn.addEventListener("click", () => {
        reloadWith({});
    });
  }
  // 제목 오른쪽 버튼 → 3-1 페이지 이동
(function(){
  const btn = document.querySelector('#goCleanDays');
  if(!btn) return;

  btn.addEventListener('click', ()=>{
    const r = document.querySelector('#region')?.value;

    const params = new URLSearchParams();
    if (r) params.set('region', r);

    // 실제 파일명에 맞게 수정
    location.href = `cleandays.html?${params.toString()}`;
  });
})();

  // -------------------------------
  // 3) 데이터 로드
  //    
  // -------------------------------
  async function loadData(){
    const regionCode = $region.value;
    if (!regionCode) return [];

    const res = await fetchJson(`/team09/api/recommend/best-period/${regionCode}?region_code=${regionCode}`);
    return res.data || [];
  }

  // -------------------------------
  // 4) 렌더링
  // -------------------------------
  function fmt(d){ return String(d||'').replaceAll('-','/'); }

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
      <li class="li-row" data-start="${r.start_date}" data-end="${r.end_date}">
        <button class="icon-btn star" title="즐겨찾기 추가" aria-label="즐겨찾기 추가">
          <i class="fa-regular fa-star"></i>
        </button>

        <div>
          <div class="li-range">${idx + 1}.  ${fmt(r.start_date)} ~ ${fmt(r.end_date)}</div>
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

    // 5-a) 캘린더 이동
    if (e.target.closest('.cal')){
      const start = li.dataset.start;
      const dt = new Date(start);
      const y = dt.getFullYear();
      const m = String(dt.getMonth()+1).padStart(2,'0');
      const q = new URLSearchParams();
      if (has($region.value)) q.set('region_code', $region.value);
      q.set('year', y); q.set('month', m);
      location.href = `calendar_temp.html?${q.toString()}`; // 한나님이 쓰는 파일명으로 맞춰서 변경 !!!!!!!
      return;
    }

    // 5-b) 즐겨찾기(별) 토글
    const starBtn = e.target.closest('.star');
    if (!starBtn) return;

    // 이 페이지는 "특정 지역" 기준이니까 region_code는 선택된 값 사용
    const regionCode = $region && has($region.value) ? $region.value : null;
    const start = li.dataset.start;
    const end   = li.dataset.end;

    if (!regionCode || !start || !end) {
      alert('즐겨찾기 정보를 찾을 수 없습니다.');
      return;
    }

    // 이미 active면 ⇒ 즐겨찾기 해제 (DELETE B안: region_code + start_date + end_date)
    if (starBtn.classList.contains('active')) {
      // 1) UI 먼저 토글 (낙관적)
      starBtn.classList.remove('active');
      starBtn.innerHTML = '<i class="fa-regular fa-star"></i>';

      try {
        // /api/bookmarks?region_code=...&start_date=...&end_date=...
        const url = new URL(`/team09/api/bookmarks`, window.location.origin);
        url.searchParams.set('region_code', regionCode);
        url.searchParams.set('start_date', start);
        url.searchParams.set('end_date',   end);

        const res = await fetchJson(url.toString(), { method: 'DELETE' , credentials: 'include'});
        if (res.status !== 200 ) throw new Error('delete failed');

      } catch (err) {
        // 서버 삭제 실패 시 UI 롤백
        starBtn.classList.add('active');
        starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';
        console.error(err);
        alert('즐겨찾기 삭제에 실패했습니다. 잠시 후 다시 시도해주세요.');
      }
      return;
    }

    // 아직 active가 아니면 ⇒ 즐겨찾기 추가 (POST)
    starBtn.classList.add('active');
    starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';

    const payload = {
      region_code: regionCode,
      start_date : start,
      end_date   : end
    };

    try{
      const r = await fetchJson(`/team09/api/bookmarks`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      // ★★★ r.ok 가 아니라 r.status 로 처리해야 함 ★★★
      if (r.status !== 200 && r.status !== 201) {
        throw new Error('save failed');
      }
      // TODO: 서버가 bookmark_id를 응답으로 준다면,
      // 여기서 li.dataset.id = res.bookmark_id 같은 식으로 저장해둘 수도 있음.
    }catch(err){
      console.error(err);
      // 서버 저장 실패 시 UI 롤백
      starBtn.classList.remove('active');
      starBtn.innerHTML = '<i class="fa-regular fa-star"></i>';
      // 로컬에서라도 기억하고 싶다면 주석 해제해서 사용
      /*
      const key = 'bookmarks';
      const cur = JSON.parse(localStorage.getItem(key) || '[]');
      cur.push({ ...payload });
      localStorage.setItem(key, JSON.stringify(cur));
      */
      alert('즐겨찾기 추가에 실패했습니다. 잠시 후 다시 시도해주세요.');
    }
  }

  // -------------------------------
  // 초기화
  // -------------------------------
  (async function start(){
    await fillRegions();
    bindGoButton();

    let rows = [];
    try {
      rows = await loadData();
    } catch (err) {
      console.error('여행 적합 기간 API 호출 실패:', err);
      alert('여행 적합 기간 데이터를 불러오지 못했습니다. 잠시 후 다시 시도해주세요.');
      renderList([]);
      return;
    }

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
        String(bm.region_code) === String($region.value) &&
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

      const matched = bookmarks.some(bm =>
        bm.start_date === start &&
        bm.end_date   === end &&
        String(bm.region_code) === String($region.value)
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