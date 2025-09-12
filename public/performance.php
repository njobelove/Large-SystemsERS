<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Performance</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head><body class="min-h-screen bg-slate-50">
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="text-lg font-semibold">Luminosa HR</div>
    <div><a href="dashboard.php" class="text-sky-600">Dashboard</a></div>
  </div>
</nav>

<main class="max-w-5xl mx-auto p-6">
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Add performance review</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <select id="empSelect" class="p-2 border rounded-md"></select>
      <input id="score" type="number" min="0" max="100" placeholder="Score (0-100)" class="p-2 border rounded-md" />
      <input id="review_date" type="date" value="<?=date('Y-m-d')?>" class="p-2 border rounded-md" />
    </div>
    <div class="mt-4">
      <textarea id="comments" class="w-full p-2 border rounded-md" placeholder="Comments"></textarea>
    </div>
    <div class="mt-4"><button onclick="saveReview()" class="px-4 py-2 bg-sky-600 text-white rounded">Save review</button></div>
  </div>

  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Statistics</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <canvas id="perfChart"></canvas>
      <div id="topList" class="p-4"></div>
    </div>
  </div>
</main>

<script>
const API = '../api/api.php';
async function api(action, method='GET', body=null){ let url = API + '?action=' + encodeURIComponent(action); const opts = { method, credentials:'include', headers: {} }; if(body){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(body);} const r = await fetch(url, opts); return r.json(); }
async function init(){ const emps = await api('list_employees'); const sel = document.getElementById('empSelect'); sel.innerHTML = '<option value="">Select employee</option>'; emps.forEach(e=> sel.innerHTML += `<option value="${e.id}">${e.first_name} ${e.last_name}</option>`); const stats = await api('performance_stats'); document.getElementById('topList').innerHTML = '<ol>'+stats.map(s=>`<li class="py-1">${s.first_name} ${s.last_name} — ${Math.round(s.avg_score)}</li>`).join('')+'</ol>'; const labels = stats.map(s=>s.first_name+' '+s.last_name); const data = stats.map(s=>Math.round(s.avg_score)); new Chart(document.getElementById('perfChart'),{type:'bar',data:{labels, datasets:[{label:'Avg score', data}]}}); }
async function saveReview(){ const emp = document.getElementById('empSelect').value; const score = parseInt(document.getElementById('score').value,10); if(!emp || isNaN(score)){ alert('Select employee and provide score'); return; } const body = { employee_id: emp, score: score, comments: document.getElementById('comments').value, review_date: document.getElementById('review_date').value }; const res = await api('add_performance','POST', body); if(res.success){ alert('Saved'); init(); } else alert(res.error || 'Failed'); }
init();
</script>
</body></html>
