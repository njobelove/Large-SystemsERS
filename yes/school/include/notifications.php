<?php
// This component should be included in a page where $conn is already available
// and a user session is active.

if (isset($_SESSION['user_id'])) {
    $current_user_id = (int)$_SESSION['user_id'];

    // Fetch unread notifications for the current user
    $notify_sql = "SELECT id, message, link, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
    $notify_stmt = $conn->prepare($notify_sql);
    $notify_stmt->bind_param('i', $current_user_id);
    $notify_stmt->execute();
    $notifications_result = $notify_stmt->get_result();
    $unread_count = $notifications_result->num_rows;
    $notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
    $notify_stmt->close();
}
?>

<?php if (isset($unread_count) && $unread_count > 0): ?>
<style>
    .notifications-container {
        position: relative;
        display: inline-block;
    }
    .notification-bell {
        cursor: pointer;
        font-size: 1.5em;
        position: relative;
    }
    .notification-count {
        position: absolute;
        top: -5px;
        right: -10px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7em;
        font-weight: bold;
    }
    .notifications-dropdown {
        display: none;
        position: absolute;
        right: 0;
        background-color: #f9f9f9;
        min-width: 300px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        border: 1px solid #ddd;
    }
    .notifications-dropdown a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        border-bottom: 1px solid #eee;
    }
    .notifications-dropdown a:hover { background-color: #f1f1f1; }
    .notification-item small { color: #777; font-size: 0.8em; }
</style>

<div class="notifications-container">
    <span id="notification-bell" class="notification-bell">
        🔔
        <span id="notification-count" class="notification-count"><?php echo $unread_count; ?></span>
    </span>
    <div id="notifications-dropdown" class="notifications-dropdown">
        <?php foreach ($notifications as $notification): ?>
            <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="notification-link" data-id="<?php echo (int)$notification['id']; ?>">
                <?php echo htmlspecialchars($notification['message']); ?>
                <div class="notification-item">
                    <small><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.notifications-container');
        const bell = document.getElementById('notification-bell');
        const dropdown = document.getElementById('notifications-dropdown');
        const countSpan = document.getElementById('notification-count');

        bell.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (container && !container.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        const notificationLinks = document.querySelectorAll('.notification-link');
        notificationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const notificationId = this.dataset.id;
                const targetUrl = this.href;

                fetch(`/actions/mark_notification_read.php?id=${notificationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let currentCount = parseInt(countSpan.textContent, 10);
                            currentCount--;
                            countSpan.textContent = currentCount;
                            if (currentCount <= 0) {
                                container.style.display = 'none';
                            }
                            this.remove();
                        }
                        // Always redirect
                        window.location.href = targetUrl;
                    })
                    .catch(err => {
                        console.error('Failed to mark notification as read:', err);
                        // Redirect even if fetch fails
                        window.location.href = targetUrl;
                    });
            });
        });
    });
</script>
<?php endif; ?>