<?php
/**
 * Expiry Notifier
 * Sends email notices for burial plots nearing/past their 5-year expiration:
 *  - 'reminder' 30 days before expiration_date -> plot owner (visitor)
 *  - 'expired'  once expiration_date has passed -> plot owner (visitor)
 *  - 'digest'   once per day -> all admin_users emails (+ ADMIN_NOTIFY_EMAIL)
 * Safe to call on every admin page load — each notice is deduplicated via the
 * expiration_notices table so it is only ever sent once.
 */

require_once __DIR__ . '/mailer.php';

function runExpiryNotices(PDO $pdo) {
    $mailCfg = mail_settings($pdo);
    if (!$mailCfg['is_enabled']) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expiration_notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            record_id INT NOT NULL DEFAULT 0,
            notice_type VARCHAR(20) NOT NULL,
            for_date DATE NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_notice (record_id, notice_type, for_date),
            INDEX idx_record (record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Records expiring within 30 days (not yet reminded for this expiration_date)
    $reminders = $pdo->query("
        SELECT br.id, br.decedent_name, br.plot_number, br.expiration_date,
               v.full_name AS owner_name, v.email AS owner_email
        FROM burial_records br
        JOIN visitors v ON v.id = br.visitor_id AND v.is_active = 1
        LEFT JOIN expiration_notices en
               ON en.record_id = br.id AND en.notice_type = 'reminder' AND en.for_date = br.expiration_date
        WHERE br.expiration_date IS NOT NULL
          AND br.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND en.id IS NULL
    ")->fetchAll();

    // Records already expired (final notice — plot becomes eligible for relocation)
    $expired = $pdo->query("
        SELECT br.id, br.decedent_name, br.plot_number, br.expiration_date,
               v.full_name AS owner_name, v.email AS owner_email
        FROM burial_records br
        JOIN visitors v ON v.id = br.visitor_id AND v.is_active = 1
        LEFT JOIN expiration_notices en
               ON en.record_id = br.id AND en.notice_type = 'expired' AND en.for_date = br.expiration_date
        WHERE br.expiration_date IS NOT NULL
          AND br.expiration_date < CURDATE()
          AND en.id IS NULL
    ")->fetchAll();

    $insert = $pdo->prepare("
        INSERT IGNORE INTO expiration_notices (record_id, notice_type, for_date) VALUES (?, ?, ?)
    ");

    foreach ($reminders as $r) {
        $daysLeft = (int)((strtotime($r['expiration_date']) - strtotime('today')) / 86400);
        $ok = send_mail(
            $pdo,
            $r['owner_email'],
            $r['owner_name'],
            'Plot Renewal Reminder — ' . ($r['plot_number'] ?: 'your plot'),
            expiryEmailHtml($r, $daysLeft, false)
        );
        if ($ok) $insert->execute([$r['id'], 'reminder', $r['expiration_date']]);
    }

    foreach ($expired as $r) {
        $ok = send_mail(
            $pdo,
            $r['owner_email'],
            $r['owner_name'],
            'Plot Expired — Action Required (' . ($r['plot_number'] ?: 'plot') . ')',
            expiryEmailHtml($r, 0, true)
        );
        if ($ok) $insert->execute([$r['id'], 'expired', $r['expiration_date']]);
    }

    // Daily admin digest — sent once per day if anything is pending
    $pending = $pdo->query("
        SELECT br.decedent_name, br.plot_number, br.expiration_date,
               v.full_name AS owner_name,
               DATEDIFF(br.expiration_date, CURDATE()) AS days_left
        FROM burial_records br
        LEFT JOIN visitors v ON v.id = br.visitor_id
        WHERE br.expiration_date IS NOT NULL
          AND br.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY br.expiration_date ASC
    ")->fetchAll();

    if ($pending) {
        $emails = $pdo->query("SELECT DISTINCT email FROM admin_users WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($mailCfg['admin_notify_email'])) $emails[] = $mailCfg['admin_notify_email'];
        $emails = array_unique($emails);
        if ($emails) {
            $today = date('Y-m-d');
            // Only send the digest once per day
            $already = $pdo->prepare("SELECT id FROM expiration_notices WHERE record_id = 0 AND notice_type = 'digest' AND for_date = ?");
            $already->execute([$today]);
            if (!$already->fetch()) {
                $allOk = true;
                foreach ($emails as $email) {
                    if (!send_mail($pdo, $email, 'Admin', 'Expiring Plots Digest — ' . count($pending) . ' pending', digestEmailHtml($pending))) {
                        $allOk = false;
                    }
                }
                if ($allOk) $insert->execute([0, 'digest', $today]);
            }
        }
    }
}

function expiryEmailHtml(array $r, $daysLeft, $isExpired) {
    $plot = htmlspecialchars($r['plot_number'] ?: 'N/A', ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($r['decedent_name'], ENT_QUOTES, 'UTF-8');
    $exp = date('F j, Y', strtotime($r['expiration_date']));
    $owner = htmlspecialchars($r['owner_name'] ?? 'there', ENT_QUOTES, 'UTF-8');

    if ($isExpired) {
        $headline = 'Plot Expired — Renewal Required';
        $color = '#b91c1c';
        $body = "The 5-year lease for plot <strong>{$plot}</strong> (decedent: <strong>{$name}</strong>) expired on <strong>{$exp}</strong>. "
              . "Please renew at the cemetery office as soon as possible. Unrenewed plots may be cleared and relocated.";
    } else {
        $headline = 'Plot Renewal Reminder';
        $color = '#b45309';
        $body = "The 5-year lease for plot <strong>{$plot}</strong> (decedent: <strong>{$name}</strong>) expires on <strong>{$exp}</strong> "
              . "— <strong>{$daysLeft} day" . ($daysLeft === 1 ? '' : 's') . " remaining</strong>. Please renew at the cemetery office before this date.";
    }

    return '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">'
        . '<div style="background:#059669;color:#fff;padding:16px 20px;font-size:16px;font-weight:bold;">Matinao Memorial Cemetery</div>'
        . '<div style="padding:20px;">'
        . '<p style="margin:0 0 4px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Notice</p>'
        . '<h2 style="margin:0 0 14px;color:' . $color . ';font-size:19px;">' . $headline . '</h2>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">Hello ' . $owner . ',</p>'
        . '<p style="color:#334155;font-size:14px;line-height:1.6;">' . $body . '</p>'
        . '</div>'
        . '<div style="padding:14px 20px;background:#f8fafc;color:#94a3b8;font-size:11px;">This is an automated notice from the Matinao Memorial Cemetery Management System.</div>'
        . '</div>';
}

function digestEmailHtml(array $pending) {
    $rows = '';
    foreach ($pending as $p) {
        $days = (int)$p['days_left'];
        $status = $days < 0 ? '<span style="color:#b91c1c;font-weight:600;">Expired ' . abs($days) . 'd ago</span>'
                : ($days === 0 ? '<span style="color:#b45309;font-weight:600;">Expires today</span>'
                : $days . ' days left');
        $rows .= '<tr>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">' . htmlspecialchars($p['decedent_name'], ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">' . htmlspecialchars($p['plot_number'] ?: 'N/A', ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">' . htmlspecialchars($p['owner_name'] ?? '—', ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">' . date('M j, Y', strtotime($p['expiration_date'])) . '</td>'
            . '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;">' . $status . '</td>'
            . '</tr>';
    }

    return '<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">'
        . '<div style="background:#059669;color:#fff;padding:16px 20px;font-size:16px;font-weight:bold;">Matinao Memorial Cemetery — Daily Digest</div>'
        . '<div style="padding:20px;">'
        . '<p style="color:#334155;font-size:14px;">' . count($pending) . ' plot(s) are expired or expire within 30 days:</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:13px;color:#334155;">'
        . '<thead><tr style="background:#f8fafc;text-align:left;">'
        . '<th style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">Decedent</th>'
        . '<th style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">Plot</th>'
        . '<th style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">Owner</th>'
        . '<th style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">Expires</th>'
        . '<th style="padding:8px 10px;border-bottom:1px solid #e5e7eb;">Status</th>'
        . '</tr></thead><tbody>' . $rows . '</tbody></table>'
        . '</div></div>';
}
