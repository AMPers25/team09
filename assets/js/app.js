// Author: Rihyun Kang

// ---------------------------------------------------------
// 공통 fetchJson + 버튼 활성화 규칙
// ---------------------------------------------------------
async function fetchJson(url, options = {}){
  const res = await fetch(url, options);
  if(!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}
function qs(sel, root=document){ return root.querySelector(sel); }
function has(v){ return v !== undefined && v !== null && String(v).trim() !== ''; }