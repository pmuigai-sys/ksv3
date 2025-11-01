<?php
declare(strict_types=1);

require __DIR__ . '/../shared/header.php';
?>
<section class="page-header">
    <div>
        <h2>Admin Analytics</h2>
        <p>Monitor every election in real time, track voter turnout, and audit the blockchain ledger without leaving this hub.</p>
    </div>
    <button class="primary-button" id="refreshDashboard" type="button">Refresh insights</button>
</section>

<section class="dashboard-grid" id="adminAnalytics">
    <article class="stat-card" data-stat="total-elections">
        <h3>Total Elections</h3>
        <p class="value">0</p>
        <p class="meta">Active &amp; archived elections</p>
    </article>
    <article class="stat-card" data-stat="votes-cast">
        <h3>Votes Cast</h3>
        <p class="value">0</p>
        <p class="meta">Recorded on blockchain</p>
    </article>
    <article class="stat-card" data-stat="turnout">
        <h3>Turnout</h3>
        <p class="value">0%</p>
        <p class="meta">Registered students vs votes</p>
    </article>
    <article class="stat-card" data-stat="chain-status">
        <h3>Blockchain Integrity</h3>
        <p class="value status-pill">Checking...</p>
        <p class="meta">Verification runs automatically</p>
    </article>
</section>

<section class="cards-row">
    <article class="panel">
        <header>
            <h3>Votes by Candidate</h3>
            <select id="electionSelector" class="select">
                <option value="" disabled selected>Choose election</option>
            </select>
        </header>
        <canvas id="votesChart" height="320" aria-label="Election vote distribution chart"></canvas>
    </article>

    <article class="panel">
        <header>
            <h3>Blockchain Ledger Snapshot</h3>
            <button class="ghost-button" id="viewFullLedger" type="button">Open full ledger</button>
        </header>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Hash</th>
                    <th>Prev Hash</th>
                    <th>Timestamp</th>
                </tr>
                </thead>
                <tbody id="ledgerPreview">
                <tr><td colspan="4">Loading ledger snapshot...</td></tr>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Recent Admin Activity</h3>
            <p>Real-time audit trail for overrides, candidate updates, and emergency actions.</p>
        </div>
        <button class="ghost-button" id="exportCsv" type="button">Export vote data</button>
    </header>
    <div class="table-wrapper">
        <table class="data-table" id="auditTable">
            <thead>
            <tr>
                <th>Election</th>
                <th>Action</th>
                <th>Performed by</th>
                <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            <tr><td colspan="4">Awaiting activity...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/footer.php'; ?>

