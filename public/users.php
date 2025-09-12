<?php
session_start();
if (empty($_SESSION['user'])) header('Location: login.php');
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Users</title>
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
    <h2 class="text-lg font-semibold mb-4">Create user</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <input id="u_username" placeholder="Username" class="p-2 border rounded-md" />
      <input id="u_email" placeholder="Email" class="p-2 border rounded-md" />
      <input id="u_password" type="password" placeholder="Password" class="p-2 border rounded-md" />
      <select id="u_role" class="p-2 border rounded-md"><option value="staff">staff</option><option value="admin">admin</option></select>
    </div>
    <div class="mt-4"><button onclick="createUser()" class="px-4 py-2 bg-sky-600 text-white rounded">Create</button></div>
  </div>

  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold mb-4">All users</h2>
    <div id="usersList">Loading...</div>
  </div>
</main>

<script>
const API = '../api/api.php';
async function api(action, method='GET', body=null){ let url = API + '?action=' + encodeURIComponent(action); const opts = { method, credentials:'include', headers: {} }; if(body){ opts.headers['Content-Type']='application/json'; opts.body = JSON.stringify(body);} const r = await fetch(url, opts); return r.json(); }

async function init(){ loadUsers(); }
async function createUser(){ const body = { username: document.getElementById('u_username').value, email: document.getElementById('u_email').value, password: document.getElementById('u_password').value, role: document.getElementById('u_role').value }; const res = await api('add_user','POST', body); if(res.success){ alert('User created'); loadUsers(); } else alert(res.error||'Failed'); }
async function loadUsers(){ const list = await api('list_users'); document.getElementById('usersList').innerHTML = list.length ? '<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="text-slate-500"><tr><th class="py-2 text-left">#</th><th class="py-2 text-left">Username</th><th class="py-2 text-left">Email</th><th class="py-2 text-left">Role</th></tr></thead><tbody>'+list.map((u,i)=>`<tr class="border-t"><td class="py-2">${i+1}</td><td class="py-2">${u.username}</td><td class="py-2">${u.email}</td><td class="py-2">${u.role}</td></tr>`).join('')+'</tbody></table></div>' : '<div class="text-slate-500">No users</div>'; }

init();
</script>
</body></html>
