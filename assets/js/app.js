
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

window.REGION_OPTIONS = window.REGION_OPTIONS || [
  { code: '108', name: '서울',   province: null },
  { code: '159', name: '부산',   province: null },
  { code: '112', name: '인천',   province: null },
  { code: '104', name: '북강릉', province: '강원도' },
  { code: '090', name: '속초',   province: '강원도' },
  { code: '093', name: '북춘천', province: '강원도' },
  { code: '168', name: '여수',   province: '전라남도' },
  { code: '146', name: '전주',   province: '전라북도' },
  { code: '283', name: '경주시', province: '경상북도' },
  { code: '115', name: '울릉도', province: '경상북도' },
  { code: '162', name: '통영',   province: '경상남도' },
  { code: '232', name: '천안',   province: '충청남도' },
  { code: '131', name: '청주',   province: '충청북도' },
  { code: '119', name: '수원',   province: '경기도' },
  { code: '143', name: '대구',   province: null },
  { code: '156', name: '광주',   province: null },
  { code: '133', name: '대전',   province: null },
  { code: '152', name: '울산',   province: null },
  { code: '239', name: '세종',   province: null },
  { code: '184', name: '제주',   province: null },
];