<?php
declare(strict_types=1);

require __DIR__ . '/../shared/header.php';
?>
<section class="hero">
    <div>
        <h2>Empowered, Transparent Voting</h2>
        <p>Cast your ballot with confidence. Every vote is secured on a blockchain ledger and counted in real time.</p>
        <div class="hero-actions">
            <a class="primary-button" href="/index.php?page=vote">Vote now</a>
            <a class="ghost-button" href="/index.php?page=stats">Live stats</a>
        </div>
    </div>
    <div class="hero-visual" aria-hidden="true">
        <span class="pulse"></span>
        <span class="node"></span>
    </div>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Active elections</h3>
            <p>Explore candidates, their manifestos, and make an informed decision.</p>
        </div>
    </header>
    <div id="activeElections" class="card-collection">
        <p class="empty-state">We will notify you once a new election opens.</p>
    </div>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Your participation</h3>
            <p>View elections you have contributed to. Your vote remains anonymous, but your civic impact is clear.</p>
        </div>
    </header>
    <div class="table-wrapper">
        <table class="data-table" id="votingHistory">
            <thead>
            <tr>
                <th>Election</th>
                <th>Voted on</th>
            </tr>
            </thead>
            <tbody>
            <tr><td colspan="2">No votes recorded yet.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/../shared/footer.php'; ?>

