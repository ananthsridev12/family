<?php include __DIR__ . '/../../layouts/app_start.php'; ?>

<div class="container py-4">
    <div class="d-flex align-items-center mb-4 gap-3">
        <h1 class="h3 mb-0 fw-semibold" style="color: var(--ft-primary);">Bulk Import</h1>
        <a href="/index.php?route=admin/members" class="btn btn-sm btn-outline-secondary ms-auto">
            &larr; Back to Members
        </a>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Instructions Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header fw-semibold" style="background: rgba(79,70,229,.08); color: var(--ft-primary);">
            <i class="bi bi-info-circle-fill me-2"></i>How to use Bulk Import
        </div>
        <div class="card-body alert-info" style="background: rgba(13,202,240,.07);">
            <ol class="mb-3">
                <li class="mb-1">
                    <a href="/index.php?route=admin/import-template" class="fw-semibold">
                        Download the CSV template
                    </a>
                </li>
                <li class="mb-1">Fill it in with your family members&rsquo; details</li>
                <li class="mb-1">Upload it using the form below</li>
            </ol>

            <p class="mb-1 fw-semibold">CSV columns:</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size: .875rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Column</th>
                            <th>Required</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>full_name</code></td>
                            <td><span class="badge bg-danger">Yes</span></td>
                            <td>Full name of the person</td>
                        </tr>
                        <tr>
                            <td><code>gender</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td><code>Male</code> or <code>Female</code></td>
                        </tr>
                        <tr>
                            <td><code>birth_year</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>4-digit year, e.g. <code>1985</code></td>
                        </tr>
                        <tr>
                            <td><code>date_of_birth</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Format: <code>YYYY-MM-DD</code></td>
                        </tr>
                        <tr>
                            <td><code>native_location</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Place of origin</td>
                        </tr>
                        <tr>
                            <td><code>current_location</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Current city / address</td>
                        </tr>
                        <tr>
                            <td><code>occupation</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Job or profession</td>
                        </tr>
                        <tr>
                            <td><code>mobile</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Mobile phone number</td>
                        </tr>
                        <tr>
                            <td><code>email</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>Email address</td>
                        </tr>
                        <tr>
                            <td><code>blood_group</code></td>
                            <td><span class="badge bg-secondary">No</span></td>
                            <td>e.g. <code>O+</code>, <code>AB-</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-2 mb-0 text-muted" style="font-size:.8125rem;">
                <span class="badge bg-danger">Yes</span> columns are required; all others are optional.
            </p>
        </div>
    </div>

    <!-- Upload Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold" style="background: rgba(79,70,229,.08); color: var(--ft-primary);">
            <i class="bi bi-upload me-2"></i>Upload CSV File
        </div>
        <div class="card-body">
            <form method="POST"
                  action="/index.php?route=admin/bulk-import-process"
                  enctype="multipart/form-data"
                  novalidate>

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="mb-3">
                    <label for="csv_file" class="form-label fw-semibold">
                        Select CSV file <span class="text-danger">*</span>
                    </label>
                    <input class="form-control"
                           type="file"
                           id="csv_file"
                           name="csv_file"
                           accept=".csv"
                           required>
                    <div class="form-text" style="color: var(--ft-muted);">
                        Only <code>.csv</code> files are accepted. Maximum upload size follows your server&rsquo;s <code>upload_max_filesize</code>.
                    </div>
                </div>

                <button type="submit"
                        class="btn btn-primary px-4"
                        style="background: var(--ft-primary); border-color: var(--ft-primary);">
                    <i class="bi bi-cloud-upload me-2"></i>Import
                </button>
            </form>
        </div>
    </div>

    <!-- Results Card (shown only when $result is set) -->
    <?php if (isset($result)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold" style="background: rgba(79,70,229,.08); color: var(--ft-primary);">
                <i class="bi bi-clipboard-check me-2"></i>Import Results
            </div>
            <div class="card-body">

                <!-- Imported count -->
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-success fs-6" style="min-width:2.5rem;">
                        <?= (int) $result['imported'] ?>
                    </span>
                    <span class="text-success fw-semibold">
                        &#10003; <?= (int) $result['imported'] ?> person<?= $result['imported'] !== 1 ? 's' : '' ?> imported successfully
                    </span>
                </div>

                <!-- Skipped count -->
                <?php if ($result['skipped'] > 0): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge rounded-pill bg-warning text-dark fs-6" style="min-width:2.5rem;">
                            <?= (int) $result['skipped'] ?>
                        </span>
                        <span class="text-warning fw-semibold">
                            &#9888; <?= (int) $result['skipped'] ?> skipped (duplicates)
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Errors list -->
                <?php if (!empty($result['errors'])): ?>
                    <hr style="border-color: var(--ft-border);">
                    <p class="fw-semibold text-danger mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Errors encountered:
                    </p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($result['errors'] as $err): ?>
                            <li class="list-group-item list-group-item-danger px-2 py-1" style="font-size:.875rem;">
                                <?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../layouts/app_end.php'; ?>
