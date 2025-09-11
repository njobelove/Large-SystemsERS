// ... existing code
                <td><?= htmlspecialchars($row['student_name']) ?></td>
                <td><?= (int)$row['invoice_id'] ?></td>
                <td><?= number_format((float)$row['invoice_amount'], 2) ?></td>
                <td><a href="/yes/yes/school/<?= htmlspecialchars($row['receipt_path']) ?>" target="_blank"><img src="/yes/yes/school/<?= htmlspecialchars($row['receipt_path']) ?>" alt="Receipt" class="receipt-thumbnail"></a></td>
                <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                <td><span class="status-<?= htmlspecialchars($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></td>
                <td>
                    <?php if ($row['status'] === 'pending'): ?>
                        <a href="review_submission.php?id=<?= (int)$row['id'] ?>">Review</a>
                    <?php else: ?>
                        No actions
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
// ... existing code
