<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Attendance</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-screen bg-slate-50">
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="text-lg font-semibold">Luminosa HR</div>
    <div><a href="dashboard.php" class="text-sky-600">Dashboard</a></div>
  </div>
</nav>

<main class="max-w-5xl mx-auto p-6">
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Mark attendance</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <input id="att_date" type="date" value="<?=date('Y-m-d')?>" class="p-2 border rounded-md" />
      <select id="empSelect" class="p-2 border rounded-md"></select>
      <select id="status" class="p-2 border rounded-md"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="leave">Leave</option></select>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
      <input id="check_in" type="time" class="p-2 border rounded-md" />
      <input id="check_out" type="time" class="p-2 border rounded-md" />
    </div>
    <div class="mt-4"><button onclick="mark()" class="px-4 py-2 bg-sky-600 text-white rounded">Save</button></div>
  </div>

  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Attendance report</h2>
    <div class="flex gap-2 mb-4">
      <input id="from" type="date" value="<?=date('Y-m-01')?>" class="p-2 border rounded-md" />
      <input id="to" type="date" value="<?=date('Y-m-t')?>" class="p-2 border rounded-md" />
      <button onclick="loadReport()" class="px-4 py-2 bg-sky-600 text-white rounded">Load</button>
    </div>
    <div id="report">Loading...</div>
  </div>
</main>

<script>
const API = '../api/api.php';
async function api(action, method='GET', body=null){ let url = API + '?action=' + encodeURIComponent(action); const opts = { method, credentials:'include', headers: {} }; if(body){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(body);} const r = await fetch(url, opts); return r.json(); }
async function init(){ const emps = await api('list_employees'); const sel = document.getElementById('empSelect'); sel.innerHTML = '<option value="">Select employee</option>'; emps.forEach(e=> sel.innerHTML += `<option value="${e.id}">${e.first_name} ${e.last_name} — ${e.position||''}</option>`); }
async function mark(){ const emp = document.getElementById('empSelect').value; const date = document.getElementById('att_date').value; const status = document.getElementById('status').value; const ci = document.getElementById('check_in').value || null; const co = document.getElementById('check_out').value || null; if(!emp){ alert('Select employee'); return; } const res = await api('mark_attendance','POST',{ employee_id: emp, date: date, status: status, check_in: ci, check_out: co }); if(res.success){ alert('Saved'); } else alert(res.error||'Failed'); }
async function loadReport(){ const from = document.getElementById('from').value; const to = document.getElementById('to').value; const rep = await api('attendance_report&from='+from+'&to='+to); document.getElementById('report').innerHTML = rep.length ? '<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-slate-500"><tr><th class="py-2 text-left">Date</th><th class="py-2 text-left">Employee</th><th class="py-2 text-left">Status</th><th class="py-2 text-left">In</th><th class="py-2 text-left">Out</th></tr></thead><tbody>'+rep.map(r=>`<tr class="border-t"><td class="py-2">${r.date}</td><td class="py-2">${r.first_name} ${r.last_name}</td><td class="py-2">${r.status}</td><td class="py-2">${r.check_in||''}</td><td class="py-2">${r.check_out||''}</td></tr>`).join('')+'</tbody></table></div>' : '<div class="text-slate-500">No records</div>'; }
init();
</script>
</body></html>
