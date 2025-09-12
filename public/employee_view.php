<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
$user = $_SESSION['user'];
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: employees.php'); exit; }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Employee Details</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-screen bg-slate-50">
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="text-lg font-semibold">Luminosa HR</div>
    <div><a href="employees.php" class="text-sky-600">Back</a></div>
  </div>
</nav>
<main class="max-w-4xl mx-auto p-6">
  <div id="content" class="bg-white rounded-lg shadow p-6">Loading...</div>
</main>

<script>
const API = '../api/api.php';
const EMP_ID = <?=$id?>;
async function api(action, method='GET', body=null){ let url = API + '?action=' + encodeURIComponent(action); const opts = { method, credentials:'include', headers: {} }; if(body){ opts.headers['Content-Type']='application/json'; opts.body=JSON.stringify(body);} const r = await fetch(url, opts); return r.json(); }
function esc(s=''){ return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;'); }

async function init(){ const e = await api('get_employee&id='+EMP_ID); document.getElementById('content').innerHTML = `<div class="flex items-start gap-6"><div class="flex-1"><h2 class="text-xl font-semibold">${esc(e.first_name)} ${esc(e.last_name)}</h2><div class="text-sm text-slate-600">${esc(e.position)} — ${esc(e.email)}</div></div></div><div class="mt-6"><strong>Payroll</strong><div id="payrollList" class="mt-2">Loading...</div></div><div class="mt-6"><strong>Performance</strong><div id="perfList" class="mt-2">Loading...</div><div class="mt-4"><form id="perfForm" class="space-y-3"><div><label class="text-sm">Score (0-100)</label><input id="score" type="number" min="0" max="100" required class="w-full p-2 border rounded-md" /></div><div><label class="text-sm">Comments</label><textarea id="comments" class="w-full p-2 border rounded-md"></textarea></div><div class="flex justify-end"><button class="px-4 py-2 bg-sky-600 text-white rounded">Add review</button></div></form></div></div><div class="mt-6"><strong>Attendance (last 30 days)</strong><div id="attList" class="mt-2">Loading...</div></div>`; const pay = await api('payroll_for_employee&employee_id='+EMP_ID); document.getElementById('payrollList').innerHTML = pay.length ? '<ul class="list-disc ml-5">'+pay.map(p=>`<li>${esc(p.pay_period)} — ${esc(p.base_salary)} (paid: ${esc(p.paid_at||'not yet')})</li>`).join('')+'</ul>' : '<div class="text-slate-500">No payroll records.</div>'; const perf = await api('performance_for_employee&employee_id='+EMP_ID); document.getElementById('perfList').innerHTML = perf.length ? '<ul class="list-disc ml-5">'+perf.map(p=>`<li>${esc(p.review_date||p.created_at)} — Score: ${esc(p.score)} — ${esc(p.comments||'')}</li>`).join('')+'</ul>' : '<div class="text-slate-500">No performance reviews yet.</div>'; document.getElementById('perfForm').addEventListener('submit', async e=>{ e.preventDefault(); const body = { employee_id: EMP_ID, score: parseInt(document.getElementById('score').value,10), comments: document.getElementById('comments').value }; const res = await api('add_performance','POST', body); if(res.success){ alert('Saved'); init(); } else alert(res.error || 'Failed'); }); const to = new Date(); const from = new Date(); from.setDate(to.getDate()-29); const rep = await api('attendance_report&from='+from.toISOString().slice(0,10)+'&to='+to.toISOString().slice(0,10)); const my = rep.filter(r => r.employee_id == EMP_ID); document.getElementById('attList').innerHTML = my.length ? '<ul class="list-disc ml-5">'+my.map(a=>`<li>${esc(a.date)} — ${esc(a.status)} ${a.check_in ? ' (in: '+esc(a.check_in)+')':''}${a.check_out? ' (out: '+esc(a.check_out)+')':''}</li>`).join('')+'</ul>' : '<div class="text-slate-500">No attendance records.</div>'; }
init();
</script>
</body></html>
