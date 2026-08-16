<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>&#128176; Moi Register</h1>
  <a href="/index.php?route=admin/export-moi-csv" class="btn btn-outline-secondary btn-sm btn-pill">&#8659; Export All CSV</a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>Function / Event</th>
          <th>Date</th>
          <th class="text-end">Entries</th>
          <th class="text-end">Total Collected</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($summary)): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            No moi entries yet. Open a Family Event and add entries via the Moi button.
          </td>
        </tr>
        <?php endif; ?>
        <?php foreach (($summary ?? []) as $row): ?>
        <?php
          $evId    = (int)($row['event_id'] ?? 0);
          $label   = htmlspecialchars((string)($row['event_label'] ?? '—'), ENT_QUOTES, 'UTF-8');
          $dt      = !empty($row['event_date']) ? date('d M Y', strtotime((string)$row['event_date'])) : '—';
          $count   = (int)($row['entry_count'] ?? 0);
          $total   = format_inr((float)($row['total_amount'] ?? 0));
        ?>
        <tr>
          <td class="fw-600"><?= $label ?></td>
          <td><?= htmlspecialchars($dt, ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-end"><?= $count ?></td>
          <td class="text-end fw-600 text-success"><?= htmlspecialchars($total, ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <div class="d-flex gap-1 justify-content-end">
              <?php if ($evId > 0): ?>
              <a href="/index.php?route=admin/moi-event&event_id=<?= $evId ?>"
                 class="btn btn-sm btn-outline-primary btn-pill">View</a>
              <a href="/index.php?route=admin/export-moi-csv&event_id=<?= $evId ?>"
                 class="btn btn-sm btn-outline-secondary btn-pill">&#8659; CSV</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
