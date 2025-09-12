<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Assets</title>
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
    <h2 class="text-lg font-semibold mb-4">Add asset</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input id="a_name" placeholder="Name" class="p-2 border rounded-md" />
      <input id="a_cat" placeholder="Category" class="p-2 border rounded-md" />
      <input id="a_qty" type="number" value="1" class="p-2 border rounded-md" />
      <select id="a_assign" class="p-2 border rounded-md"><option value="">None</option></select>
      <input id="a_cond" placeholder="Condition" class="p-2 border rounded-md" value="good" />
    </div>
    <div class="mt-4"><button onclick="saveAsset()" class="px-4 py-2 bg-sky-600 text-white rounded">Save asset</button></div>
  </div>

  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">Assets list</h2>
    <div id="assetList">Loading...</div>
  </div>
</main>

<script>
const API = '../api/api.php';
async function api(action, method='GET', body=null){ let url = API + '?action=' + encodeURIComponent(action); const opts = { method, credentials:'include', headers: {} }; if(body){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(body);} const r = await fetch(url, opts); return r.json(); }
async function init(){ const emps = await api('list_employees'); const sel = document.getElementById('a_assign'); emps.forEach(e=> sel.innerHTML += `<option value="${e.id}">${e.first_name} ${e.last_name}</option>`); loadAssets(); }
async function saveAsset(){ const body = { name: document.getElementById('a_name').value, category: document.getElementById('a_cat').value, quantity: parseInt(document.getElementById('a_qty').value||1,10), assigned_to: document.getElementById('a_assign').value||null, condition: document.getElementById('a_cond').value }; const res = await api('add_asset','POST',body); if(res.success){ alert('Saved'); loadAssets(); } else alert(res.error||'Failed'); }
async function loadAssets(){ const list = await api('list_assets'); document.getElementById('assetList').innerHTML = list.length ? '<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-slate-500"><tr><th class="py-2 text-left">Name</th><th class="py-2 text-left">Category</th><th class="py-2 text-left">Qty</th><th class="py-2 text-left">Assigned</th><th class="py-2 text-left">Condition</th></tr></thead><tbody>'+list.map(a=>`<tr class="border-t"><td class="py-2">${a.name}</td><td class="py-2">${a.category}</td><td class="py-2">${a.quantity}</td><td class="py-2">${a.assigned_name||''}</td><td class="py-2">${a.condition}</td></tr>`).join('')+'</tbody></table></div>' : '<div class="text-slate-500">No assets</div>'; }
init();
</script>
</body></html>
