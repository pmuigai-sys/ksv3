<?php
declare(strict_types=1);

require __DIR__ . '/../shared/header.php';
?>
<section class="page-header">
    <div>
        <h2>Live Election Insights</h2>
        <p>Track vote counts and turnout as they update. Data refreshes automatically while elections are active.</p>
    </div>
    <button class="ghost-button" id="refreshStats" type="button">Refresh</button>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Select an election</h3>
            <p>Compare candidate performance and overall voter engagement.</p>
        </div>
        <select id="statsElectionSelector" class="select">
            <option value="" disabled selected>Choose election</option>
        </select>
    </header>
    <div class="stats-layout">
        <div class="stats-chart">
            <canvas id="userStatsChart" height="320" aria-label="Live vote chart"></canvas>
        </div>
        <div class="stats-cards">
            <article class="stat-card" data-stat="user-total-votes">
                <h3>Total votes</h3>
                <p class="value">0</p>
                <p class="meta">Across all candidates</p>
            </article>
            <article class="stat-card" data-stat="user-turnout">
                <h3>Voter turnout</h3>
                <p class="value">0%</p>
                <p class="meta">Registered vs actual voters</p>
            </article>
            <article class="stat-card" data-stat="user-chain">
                <h3>Ledger integrity</h3>
                <p class="value status-pill">Awaiting check</p>
                <p class="meta">Ensures tamper-proof results</p>
            </article>
        </div>
    </div>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Recent blockchain entries</h3>
            <p>Transparency first: the latest blocks that include vote transactions.</p>
        </div>
    </header>
    <div class="table-wrapper">
        <table class="data-table" id="userLedgerPreview">
            <thead>
            <tr>
                <th>#</th>
                <th>Hash</th>
                <th>Type</th>
                <th>Timestamp</th>
            </tr>
            </thead>
            <tbody>
            <tr><td colspan="4">Loading data...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/footer.php'; ?>

