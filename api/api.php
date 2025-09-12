<?php
// api/api.php
require_once 'config.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function bad($msg='Bad request') { http_response_code(400); json(['error'=>$msg]); }

// Public auth routes
switch ($action) {
    case 'login':
        if ($method !== 'POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['username']) || empty($b['password'])) bad('username & password required');
        if (login_user($b['username'], $b['password'])) json(['success'=>true,'user'=>$_SESSION['user']]);
        bad('invalid credentials');
        break;
    case 'logout':
        logout_user(); json(['success'=>true]);
        break;
}

// Protected routes
if (empty($_SESSION['user'])) { http_response_code(401); json(['error'=>'Unauthorized']); }
$user = $_SESSION['user'];

// helper for admin-only endpoints
function require_admin() {
    global $user;
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        json(['error' => 'Forbidden — admin only']);
    }
}

switch ($action) {
    case 'me': json($user); break;

    // USERS (admin only)
    case 'list_users':
        require_admin();
        $stmt = $pdo->query('SELECT id, username, email, role, first_name, last_name, created_at FROM users ORDER BY created_at DESC');
        json($stmt->fetchAll());
        break;

    case 'add_user':
        require_admin();
        if ($method !== 'POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['username']) || empty($b['password']) || empty($b['email'])) bad('username, email & password required');
        $hash = password_hash($b['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username,email,password,role,first_name,last_name) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$b['username'],$b['email'],$hash,$b['role'] ?? 'staff',$b['first_name'] ?? null,$b['last_name'] ?? null]);
        json(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    case 'update_user':
        require_admin();
        if ($method !== 'POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['id'])) bad('id required');
        $fields = [];
        $params = [];
        if (!empty($b['email'])) { $fields[]='email=?'; $params[]=$b['email']; }
        if (!empty($b['role'])) { $fields[]='role=?'; $params[]=$b['role']; }
        if (!empty($b['first_name'])) { $fields[]='first_name=?'; $params[]=$b['first_name']; }
        if (!empty($b['last_name'])) { $fields[]='last_name=?'; $params[]=$b['last_name']; }
        if (!empty($b['password'])) { $fields[]='password=?'; $params[]=password_hash($b['password'], PASSWORD_DEFAULT); }
        if (empty($fields)) bad('nothing to update');
        $params[] = $b['id'];
        $stmt = $pdo->prepare('UPDATE users SET '.implode(',', $fields).' WHERE id = ?');
        $stmt->execute($params);
        json(['success'=>true]);
        break;

    case 'delete_user':
        require_admin();
        if ($method !== 'POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['id'])) bad('id required');
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$b['id']]);
        json(['success'=>true]);
        break;

    // EMPLOYEES
    case 'list_employees':
        $stmt = $pdo->query('SELECT * FROM employees ORDER BY created_at DESC');
        json($stmt->fetchAll());
        break;

    case 'get_employee':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM employees WHERE id=?'); $stmt->execute([$id]); json($stmt->fetch());
        break;

    case 'search_employees':
        $term = '%'.($_GET['q'] ?? '').'%';
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE CONCAT(first_name,' ',last_name,email,position) LIKE ? LIMIT 100");
        $stmt->execute([$term]);
        json($stmt->fetchAll());
        break;

    case 'add_employee':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['first_name'])||empty($b['email'])) bad('first_name & email required');
        $stmt = $pdo->prepare('INSERT INTO employees (first_name,last_name,email,position,hired_date) VALUES (?,?,?,?,?)');
        $stmt->execute([$b['first_name'],$b['last_name'],$b['email'],$b['position'],$b['hired_date']]);
        json(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    // PAYROLL
    case 'payroll_for_employee':
        $emp = (int)($_GET['employee_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM payroll WHERE employee_id = ? ORDER BY id DESC');
        $stmt->execute([$emp]);
        json($stmt->fetchAll());
        break;

    // ATTENDANCE
    case 'mark_attendance':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['employee_id'])||empty($b['date'])) bad('employee_id & date required');
        $stmt = $pdo->prepare('INSERT INTO attendance (employee_id,date,status,check_in,check_out) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status), check_in=VALUES(check_in), check_out=VALUES(check_out)');
        $stmt->execute([$b['employee_id'],$b['date'],$b['status'] ?? 'present',$b['check_in'] ?? null,$b['check_out'] ?? null]);
        json(['success'=>true]);
        break;

    case 'attendance_report':
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $stmt = $pdo->prepare('SELECT a.*, e.first_name, e.last_name FROM attendance a JOIN employees e ON e.id = a.employee_id WHERE date BETWEEN ? AND ? ORDER BY date DESC');
        $stmt->execute([$from,$to]); json($stmt->fetchAll());
        break;

    // PERFORMANCE
    case 'add_performance':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['employee_id'])||!isset($b['score'])) bad('employee_id & score required');
        $stmt = $pdo->prepare('INSERT INTO performance_reviews (employee_id, reviewer_id, score, comments, review_date) VALUES (?,?,?,?,?)');
        $stmt->execute([$b['employee_id'],$user['id'],$b['score'],$b['comments'] ?? null,$b['review_date'] ?? date('Y-m-d')]);
        json(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    case 'performance_for_employee':
        $id = (int)($_GET['employee_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM performance_reviews WHERE employee_id = ? ORDER BY review_date DESC'); $stmt->execute([$id]); json($stmt->fetchAll());
        break;

    case 'performance_stats':
        $stmt = $pdo->query('SELECT p.employee_id, AVG(p.score) as avg_score, e.first_name, e.last_name FROM performance_reviews p JOIN employees e ON e.id = p.employee_id GROUP BY p.employee_id ORDER BY avg_score DESC');
        json($stmt->fetchAll());
        break;

    // LEAVES
    case 'request_leave':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO leaves (employee_id,leave_type,start_date,end_date,days,reason) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$b['employee_id'],$b['leave_type'],$b['start_date'],$b['end_date'],$b['days'],$b['reason']]);
        $id = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO notifications (type,message,reference_type,reference_id) VALUES (?,?,?,?)')
            ->execute(['leave_request',"New leave request for employee #{$b['employee_id']}", 'leave', $id]);
        json(['success'=>true,'id'=>$id]);
        break;

    case 'list_leaves':
        $stmt = $pdo->query('SELECT l.*, e.first_name, e.last_name FROM leaves l JOIN employees e ON e.id = l.employee_id ORDER BY l.created_at DESC'); json($stmt->fetchAll());
        break;

    case 'approve_leave':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('UPDATE leaves SET status = ? WHERE id = ?'); $stmt->execute([$b['status'],$b['id']]);
        $pdo->prepare('INSERT INTO notifications (type,message,reference_type,reference_id) VALUES (?,?,?,?)')
            ->execute(['leave_update', "Leave #{$b['id']} has been {$b['status']}", 'leave', $b['id']]);
        json(['success'=>true]);
        break;

    // ASSETS
    case 'list_assets':
        $stmt = $pdo->query("SELECT a.*, CONCAT(e.first_name,' ',e.last_name) as assigned_name FROM assets a LEFT JOIN employees e ON e.id = a.assigned_to ORDER BY a.created_at DESC");
        json($stmt->fetchAll());
        break;

    case 'add_asset':
        require_admin();
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO assets (name,category,quantity,assigned_to,`condition`) VALUES (?,?,?,?,?)');
        $stmt->execute([$b['name'],$b['category'],$b['quantity'],$b['assigned_to'],$b['condition']]);
        json(['success'=>true,'id'=>$pdo->lastInsertId()]);
        break;

    case 'update_asset':
        require_admin();
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['id'])) bad('id required');
        $fields = []; $params = [];
        if (isset($b['name'])){ $fields[]='name=?'; $params[]=$b['name']; }
        if (isset($b['category'])){ $fields[]='category=?'; $params[]=$b['category']; }
        if (isset($b['quantity'])){ $fields[]='quantity=?'; $params[]=$b['quantity']; }
        if (array_key_exists('assigned_to',$b)){ $fields[]='assigned_to=?'; $params[]=$b['assigned_to']; }
        if (isset($b['condition'])){ $fields[]='`condition`=?'; $params[]=$b['condition']; }
        if (empty($fields)) bad('nothing to update');
        $params[] = $b['id'];
        $stmt = $pdo->prepare('UPDATE assets SET '.implode(',', $fields).' WHERE id = ?');
        $stmt->execute($params);
        json(['success'=>true]);
        break;

    case 'delete_asset':
        require_admin();
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        if (empty($b['id'])) bad('id required');
        $pdo->prepare('DELETE FROM assets WHERE id = ?')->execute([$b['id']]);
        json(['success'=>true]);
        break;

    // NOTIFICATIONS
    case 'notifications': $stmt = $pdo->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 100'); json($stmt->fetchAll()); break;
    case 'mark_notification_read':
        if ($method!=='POST') bad('POST required');
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$b['id']]);
        json(['success'=>true]);
        break;

    // DASHBOARD
    case 'dashboard':
        $data = [];
        $data['total_employees'] = (int)$pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
        $data['active_employees'] = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
        $data['pending_leaves'] = (int)$pdo->query("SELECT COUNT(*) FROM leaves WHERE status='pending'")->fetchColumn();
        $data['assets_count'] = (int)$pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn();
        $data['top_performers'] = $pdo->query('SELECT p.employee_id, AVG(p.score) avg_score, e.first_name, e.last_name FROM performance_reviews p JOIN employees e ON e.id = p.employee_id GROUP BY p.employee_id ORDER BY avg_score DESC LIMIT 5')->fetchAll();
        json($data);
        break;

    default: http_response_code(404); json(['error'=>'Unknown action']); 
}
