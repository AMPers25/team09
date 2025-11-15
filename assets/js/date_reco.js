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

  // -------------------------------
  // 1) 쿼리 파라미터 읽기/쓰기
  // -------------------------------
  const params = new URLSearchParams(location.search);
  const region = params.get('region') || '';
  const start  = params.get('start')  || '';
  const end    = params.get('end')    || '';

  // -------------------------------
  // 2) 상단 컨트롤 바 채우기
  // -------------------------------
  const $region = $('#region');
  const $start  = $('#start');
  const $end    = $('#end');

  async function fillRegions(){
    // placeholder 남겨두고 뒤에 붙이기
    while($region.options.length>1) $region.remove(1);
    const data = await fetchJson('../pages/mock/regions_20.json');
    data.forEach(r=>{
      const opt = document.createElement('option');
      opt.value = r.code;
      opt.textContent = r.province ? `${r.province} ${r.name}` : r.name;
      $region.appendChild(opt);
    });
    if (has(region)) $region.value = region;
  }

  function setDates(){
    if (has(start)) $start.value = start;
    if (has(end))   $end.value   = end;
  }

  function reloadWith(next){
    const q = new URLSearchParams({
      region: next.region || $region.value || '',
      start : next.start  || $start.value  || '',
      end   : next.end    || $end.value    || ''
    });
    location.href = `date_reco.html?${q.toString()}`;
  }

  // 변경 이벤트 → 즉시 리로드
  function bindFilterEvents(){
    $region.addEventListener('change', ()=>reloadWith({}));
    $start.addEventListener('change',  ()=>reloadWith({}));
    $end.addEventListener('change',    ()=>reloadWith({}));
    
  }
  // 제목 오른쪽 버튼 → 3-1 페이지 이동
(function(){
  const btn = document.querySelector('#goCleanDays');
  if(!btn) return;

  btn.addEventListener('click', ()=>{
    const r = document.querySelector('#region')?.value;
    const s = document.querySelector('#start')?.value;
    const e = document.querySelector('#end')?.value;

    const params = new URLSearchParams();
    if (r) params.set('region', r);
    if (s) params.set('start', s);
    if (e) params.set('end',   e);

    // 실제 파일명에 맞게 수정
    location.href = `cleandays.html?${params.toString()}`;
  });
})();

  // -------------------------------
  // 3) 데이터 로드
  //    
  // -------------------------------
  async function loadData(){
    let rows = [];
    try{
      const u = new URL('/api/air-quality/best-period', location.origin);
      if (has($region.value)) u.searchParams.set('region', $region.value);
      if (has($start.value))  u.searchParams.set('start',  $start.value);
      if (has($end.value))    u.searchParams.set('end',    $end.value);
      const res = await fetchJson(u.toString());
      rows = res.data || [];
    }catch(e){
      // mock 폴백 (파일명을 고정하거나, 필요하면 region코드에 맞춰 분기)
      try{
        const mock = await fetchJson('mock/date_reco_sample.json');
        rows = Array.isArray(mock.data) ? mock.data : mock;
      }catch{ rows = []; }
    }
    return rows;
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

    $list.innerHTML = rows.map(r=>`
      <li class="li-row" data-start="${r.start_date}" data-end="${r.end_date}">
        <button class="icon-btn star" title="즐겨찾기 추가" aria-label="즐겨찾기 추가">
          <i class="fa-regular fa-star"></i>
        </button>

        <div>
          <div class="li-range">${r.rank}. ${fmt(r.start_date)} ~ ${fmt(r.end_date)}</div>
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
      if (has($region.value)) q.set('region', $region.value);
      q.set('year', y); q.set('month', m);
      location.href = `calendar_temp.html?${q.toString()}`; // 한나님이 쓰는 파일명으로 맞춰서 변경 !!!!!!!
      return;
    }

    // 5-b) 즐겨찾기(별)
    const starBtn = e.target.closest('.star');
    if (starBtn){
      // 이미 선택되어 있으면 아무것도 안 함(원한다면 토글 삭제도 가능)
      if (starBtn.classList.contains('active')) return;

      starBtn.classList.add('active');
      starBtn.innerHTML = '<i class="fa-solid fa-star"></i>';

      const payload = {
        region_code: $region.value,
        start_date : li.dataset.start,
        end_date   : li.dataset.end
      };

      // 서버에 저장 시도(낙관적 UI)
      try{
        const r = await fetch('/api/bookmarks', {
          method:'POST',
          headers:{ 'Content-Type':'application/json' },
          body: JSON.stringify(payload)
        });
        // 서버에서 오류가 나면 롤백
        if (!r.ok) throw new Error('save failed');
      }catch{
        // mock: localStorage에 저장해두기(선택)
        const key = 'bookmarks';
        const cur = JSON.parse(localStorage.getItem(key) || '[]');
        cur.push({ ...payload });
        localStorage.setItem(key, JSON.stringify(cur));
      }
    }
  }

  // -------------------------------
  // 초기화
  // -------------------------------
  (async function start(){
    await fillRegions();
    setDates();
    bindFilterEvents();

    const rows = await loadData();
    renderList(rows);

    $('#list').addEventListener('click', onClickList);
  })();
})();