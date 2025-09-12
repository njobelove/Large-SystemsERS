<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head><body class="min-h-screen bg-slate-50">
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <div class="text-xl font-semibold text-slate-700">Luminosa HR</div>
      <div class="text-sm text-slate-500">Admin Dashboard</div>
    </div>
    <div class="flex items-center gap-3">
      <a href="employees.php" class="text-slate-600 hover:text-sky-600">Employees</a>
      <a href="performance.php" class="text-slate-600 hover:text-sky-600">Performance</a>
      <a href="attendance.php" class="text-slate-600 hover:text-sky-600">Attendance</a>
      <a href="assets.php" class="text-slate-600 hover:text-sky-600">Assets</a>
      <a href="users.php" class="text-slate-600 hover:text-sky-600">Users</a>
      <a href="logout.php" class="ml-4 text-sm text-red-500">Logout</a>
    </div>
  </div>
</nav>

<header class="max-w-7xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Welcome, <?=htmlspecialchars($user['first_name'] ?? $user['username'])?></h1>
      <p class="text-sm text-slate-500">Overview of HR operations</p>
    </div>
    <div class="flex items-center gap-3">
      <div class="text-sm text-slate-600">Today: <?=date('F j, Y')?></div>
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 pb-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
  <section class="lg:col-span-2 space-y-6">
    <div class="bg-white p-5 rounded-lg shadow">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-700">Key metrics</h2>
        <button onclick="loadDashboard()" class="text-sm text-sky-600">Refresh</button>
      </div>
      <div id="metricsGrid" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4"></div>
    </div>

    <div class="bg-white p-5 rounded-lg shadow">
      <h2 class="text-lg font-semibold text-slate-700 mb-3">Recent activity</h2>
      <div class="text-sm text-slate-500">Notifications and recent leave approvals will appear here.</div>
    </div>
  </section>

  <aside class="space-y-6">
    <div class="bg-white p-5 rounded-lg shadow">
      <h3 class="text-sm font-medium text-slate-600">Attendance (last 30 days)</h3>
      <canvas id="attChart" class="mt-4"></canvas>
    </div>

    <div class="bg-white p-5 rounded-lg shadow">
      <h3 class="text-sm font-medium text-slate-600">Top performers</h3>
      <canvas id="perfChart" class="mt-4"></canvas>
    </div>
  </aside>
</main>

<script>
const API = '../api/api.php';
async function api(action){ const r = await fetch(API + '?action='+action, {credentials:'include'}); return r.json(); }
async function loadDashboard(){
  const d = await api('dashboard');
  const grid = document.getElementById('metricsGrid');
  grid.innerHTML = `
    <div class="p-4 rounded-lg border border-slate-100">
      <div class="text-sm text-slate-500">Employees</div>
      <div class="text-2xl font-bold text-slate-800">${d.total_employees}</div>
    </div>
    <div class="p-4 rounded-lg border border-slate-100">
      <div class="text-sm text-slate-500">Pending leaves</div>
      <div class="text-2xl font-bold text-slate-800">${d.pending_leaves}</div>
    </div>
    <div class="p-4 rounded-lg border border-slate-100">
      <div class="text-sm text-slate-500">Assets</div>
      <div class="text-2xl font-bold text-slate-800">${d.assets_count}</div>
    </div>
  `;
  const labels = []; for(let i=29;i>=0;i--){ const dt = new Date(); dt.setDate(dt.getDate()-i); labels.push(dt.toISOString().slice(0,10)); }
  const present = labels.map(()=> Math.floor(10 + Math.random()*10));
  new Chart(document.getElementById('attChart'),{type:'line',data:{labels, datasets:[{label:'Present', data:present, borderColor:'#0ea5a4', backgroundColor:'rgba(14,165,164,0.08)'}]}, options:{responsive:true}});
  const perf = d.top_performers || [];
  new Chart(document.getElementById('perfChart'),{type:'bar',data:{labels: perf.map(p=>p.first_name+' '+p.last_name), datasets:[{label:'Avg score', data: perf.map(p=>Math.round(p.avg_score)), backgroundColor:'#60a5fa'}]}, options:{responsive:true}});
}
loadDashboard();
</script>
</body></html>
