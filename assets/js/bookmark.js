(function(){
    // --- 공통 유틸 폴백 (app.js가 있으면 그걸 사용) ---
    const qs  = window.qs  || ((s, el=document)=>el.querySelector(s));
    const has = window.has || (v => v !== undefined && v !== null && String(v).trim() !== '');
    const fetchJson = window.fetchJson || (async (url)=>{
     const r = await fetch(url, { headers: { 'Accept':'application/json' }});
     if(!r.ok) throw new Error(`HTTP ${r.status}`);
     return r.json();
    });

    // // 간단한 JSON 요청(DELETE 등)
    // async function apiJSON(method, url, body){
    //  const r = await fetch(url, {
    //    method,
    //    headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
    //    body: body ? JSON.stringify(body) : undefined
    //   });
    //   // 서버가 항상 JSON을 주지 않을 수 있으니 예외 처리
    //   try { return await r.json(); } catch { return { ok: r.ok }; }
    // }

    // --- region_code → region_name 매핑 캐시 ---
    let REGION_MAP = null;
    async function getRegionMap(){
      if (REGION_MAP) return REGION_MAP;
      const regions = await fetchJson('mock/regions_20.json'); // [{code,name,province}]
     REGION_MAP = Object.fromEntries(
      regions.map(r => [ String(r.code), (r.province ? `${r.province} ${r.name}` : r.name) ])
     );
    return REGION_MAP;
    }

    /** 날짜 YYYY-MM-DD → YYYY/MM/DD */
    function fmt(dateStr){
     return has(dateStr) ? String(dateStr).replaceAll('-','/') : '';
    }

    // 렌더링
    function renderList(rows){
     const $list  = qs('#bmList');
     const $empty = qs('#empty');
     if (!$list) return;

     if (!rows || rows.length === 0) {
       $list.innerHTML = '';
      if ($empty) $empty.hidden = false;
      return;
    }
    if ($empty) $empty.hidden = true;

    $list.innerHTML = rows.map((r, idx)=>`
        <li class="bm-item"
            data-id="${r.bookmark_id ?? ''}"
            data-region="${r.region_code}"
            data-start="${r.start_date}"
            data-end="${r.end_date}">
        <div class="bm-rank">${idx+1}.</div>
        <div class="bm-region">${r.region_name ?? r.region_code}</div>
        <div class="bm-range">${fmt(r.start_date)} &nbsp;~&nbsp; ${fmt(r.end_date)}</div>
        <div><button class="btn-del">삭제</button></div>
        </li>
  ` ).join('');
    }

    // 메인 로드
    async function loadBookmarks(){
    const $list  = qs('#bmList');
    const $empty = qs('#empty');

    // 1) 서버 API 우선
    let data = [];
    try {
        // 계약: GET /api/bookmarks → { ok:true, data:[...] }
        const res = await fetchJson(`/team09/api/bookmarks`);
        if (res && (res.ok ?? true) && Array.isArray(res.data)) {
           data = res.data;
        } else {
        throw new Error('Invalid API response');
        }
    } catch {
        // 2) 실패 시 mock
        try {
        const mock = await fetchJson('mock/bookmarks.json'); // {ok:true,data:[...]} or [...]
        data = Array.isArray(mock) ? mock : (mock.data || []);
        } catch {
        data = [];
        }
    }

    // region_name 보강
    try {
        const map = await getRegionMap();
        data = data.map(r => ({
        ...r,
        region_name: map[String(r.region_code)] || String(r.region_code)
        }));
    } catch {
        // 이름 매핑 실패 시 코드 그대로 노출
    }

    renderList(data);

    /** 삭제 후 남은 즐겨찾기 항목들의 번호를 1부터 다시 매겨주는 함수 */
    function renumberBookmarks() {
        const items = document.querySelectorAll('#bmList .bm-item');
        items.forEach((li, idx) => {
            const rankDiv = li.querySelector('.bm-rank');
            if (rankDiv) {
            rankDiv.textContent = `${idx + 1}.`;
            }
        });
    }

    // 삭제 이벤트(위임)
    if ($list) {
        $list.addEventListener('click', async (e)=>{
        const btn = e.target.closest('.btn-del');
        if (!btn) return;

        const li = btn.closest('.bm-item');
        if (!li) return;

        const id     = li.dataset.id;    // 서버 bookmark_id (없을 수도 있음: mock)
        // const region = li.dataset.region;
        // const start  = li.dataset.start;
        // const end    = li.dataset.end;

        // 1) UI에서 먼저 제거(낙관적)
        const parent = li.parentElement;
        const savedNextSibling = li.nextElementSibling; // 실패시 복구용
        li.remove();
        if (!parent.children.length && $empty) $empty.hidden = false;
        
        // ★ 삭제 성공 → 번호 재정렬
        renumberBookmarks();

        // 2) 서버에 삭제 요청 (id가 있을 때만)
        try {
            if (has(id)) {
            // 계약: DELETE /api/bookmarks/:id  → {ok:true}
            const res = await fetch(`/team09/api/bookmarks/${id}`, {
                method: 'DELETE',
                credentials: 'include'
            });
            if (res.status !== 200 ) throw new Error('delete failed');
            } 
            // else {
            // // mock/로컬스토리지 삭제 시도(선택)
            // const key = 'bookmarks';
            // const list = JSON.parse(localStorage.getItem(key) || '[]');
            // const next = list.filter(x =>
            //     !(String(x.region_code)===String(region) &&
            //     x.start_date===start &&
            //     x.end_date===end)
            // );
            // localStorage.setItem(key, JSON.stringify(next));
            // }
        } catch (err) {
            // 실패 시 복구
            if (savedNextSibling) parent.insertBefore(li, savedNextSibling);
            else parent.appendChild(li);
            if ($empty) $empty.hidden = true;
            alert('삭제에 실패했습니다. 잠시 후 다시 시도해주세요.');
        }
        });
    }
    }

    // 초기화
    loadBookmarks();
})();  // ← IIFE 끝


