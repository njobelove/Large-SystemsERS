<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Employees — HR ERP</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="min-h-screen bg-slate-50">
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="text-lg font-semibold">Luminosa HR</div>
    <div class="flex items-center gap-3">
      <a href="dashboard.php" class="text-slate-600 hover:text-sky-600">Dashboard</a>
      <a href="logout.php" class="text-sm text-red-500 ml-4">Logout</a>
    </div>
  </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Employees</h1>
    <button onclick="openAddEmployee()" class="bg-sky-600 text-white px-4 py-2 rounded shadow">+ Add Employee</button>
  </div>

  <div class="bg-white rounded-lg shadow p-4">
    <div class="mb-4">
      <input id="searchInput" placeholder="Search employees..." oninput="searchEmployees()" class="w-full p-3 border rounded-md" />
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-slate-500">
          <tr><th class="text-left py-2">#</th><th class="text-left py-2">Name</th><th class="text-left py-2">Position</th><th class="text-left py-2">Email</th><th class="text-left py-2">Hired</th><th class="text-left py-2">Actions</th></tr>
        </thead>
        <tbody id="employeesTable" class="divide-y"></tbody>
      </table>
    </div>
  </div>
</main>

<div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/40 p-4">
  <div id="modalCard" class="bg-white rounded-lg shadow max-w-2xl w-full p-6"></div>
</div>

<script>
const API = '../api/api.php';
async function api(action, method='GET', body=null) {
  let url = API + '?action=' + encodeURIComponent(action);
  const opts = { method, credentials: 'include', headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const r = await fetch(url, opts);
  return r.json();
}

async function loadEmployees(){
  const list = await api('list_employees');
  const tbody = document.getElementById('employeesTable');
  tbody.innerHTML = '';
  list.forEach((e,i)=>{
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="py-3">${i+1}</td><td class="py-3 font-medium">${esc(e.first_name)} ${esc(e.last_name)}</td><td class="py-3">${esc(e.position)}</td><td class="py-3 text-slate-600">${esc(e.email)}</td><td class="py-3">${esc(e.hired_date||'')}</td><td class="py-3"><button class="text-sky-600 hover:underline mr-3" onclick="viewEmployee(${e.id})">View</button></td>`;
    tbody.appendChild(tr);
  });
}

function esc(s=''){ return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;'); }

function openAddEmployee(){
  openModal(`<h3 class="text-lg font-semibold mb-3">Add employee</h3>
    <div class="space-y-3">
      <div><label class="text-sm">First name</label><input id="e_first" class="w-full p-2 border rounded-md" /></div>
      <div><label class="text-sm">Last name</label><input id="e_last" class="w-full p-2 border rounded-md" /></div>
      <div><label class="text-sm">Email</label><input id="e_email" class="w-full p-2 border rounded-md" /></div>
      <div><label class="text-sm">Position</label><input id="e_pos" class="w-full p-2 border rounded-md" /></div>
      <div><label class="text-sm">Hired date</label><input id="e_hired" type="date" class="w-full p-2 border rounded-md" /></div>
      <div class="flex justify-end gap-3 pt-4"><button class="px-4 py-2 rounded border" onclick="closeModal()">Cancel</button><button class="px-4 py-2 bg-sky-600 text-white rounded" onclick="saveEmployee()">Save</button></div>
    </div>`);
}

async function saveEmployee(){
  const body = { first_name: document.getElementById('e_first').value, last_name: document.getElementById('e_last').value, email: document.getElementById('e_email').value, position: document.getElementById('e_pos').value, hired_date: document.getElementById('e_hired').value };
  const res = await api('add_employee','POST',body);
  if(res.success){ closeModal(); loadEmployees(); } else alert(res.error || 'Failed');
}

function openModal(html){ document.getElementById('modalCard').innerHTML = html; document.getElementById('modal').classList.remove('hidden'); }
function closeModal(){ document.getElementById('modal').classList.add('hidden'); document.getElementById('modalCard').innerHTML = ''; }

async function viewEmployee(id){
  openModal('<div class="p-6">Loading...</div>');
  const e = await api('get_employee&id='+id);
  const content = `<div><h3 class="text-lg font-semibold mb-2">${esc(e.first_name)} ${esc(e.last_name)}</h3><div class="text-sm text-slate-600 mb-4">${esc(e.position)} — ${esc(e.email)}</div><div class="text-sm"><strong>Payroll</strong><div id="payrollList" class="mt-2">Loading...</div></div><div class="mt-4"><strong>Performance</strong><div id="perfList" class="mt-2">Loading...</div></div><div class="mt-6 text-right"><button class="px-4 py-2 rounded border" onclick="closeModal()">Close</button></div></div>`;
  document.getElementById('modalCard').innerHTML = content;
  const pay = await api('payroll_for_employee&employee_id='+id);
  document.getElementById('payrollList').innerHTML = pay.length ? '<ul class="list-disc ml-5">'+pay.map(p=>`<li>${esc(p.pay_period)} — ${esc(p.base_salary)} (paid: ${esc(p.paid_at||'not yet')})</li>`).join('')+'</ul>' : '<div class="text-slate-500">No payroll records.</div>';
  const perf = await api('performance_for_employee&employee_id='+id);
  document.getElementById('perfList').innerHTML = perf.length ? '<ul class="list-disc ml-5">'+perf.map(p=>`<li>${esc(p.review_date||p.created_at)} — Score: ${esc(p.score)} — ${esc(p.comments||'')}</li>`).join('')+'</ul>' : '<div class="text-slate-500">No performance reviews.</div>';
}

async function searchEmployees(){ const q = document.getElementById('searchInput').value.trim(); if(!q){ loadEmployees(); return; } const list = await api('search_employees&q='+encodeURIComponent(q)); const tbody = document.getElementById('employeesTable'); tbody.innerHTML = ''; list.forEach((e,i)=>{ const tr = document.createElement('tr'); tr.innerHTML = `<td class="py-3">${i+1}</td><td class="py-3 font-medium">${esc(e.first_name)} ${esc(e.last_name)}</td><td class="py-3">${esc(e.position)}</td><td class="py-3 text-slate-600">${esc(e.email)}</td><td class="py-3">${esc(e.hired_date||'')}</td><td class="py-3"><button class="text-sky-600 hover:underline" onclick="viewEmployee(${e.id})">View</button></td>`; tbody.appendChild(tr); }); }

loadEmployees();
</script>
</body></html>
