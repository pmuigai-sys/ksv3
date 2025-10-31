<?php
declare(strict_types=1);

require __DIR__ . '/../shared/header.php';

$csrfToken = kvs_csrf_token();
?>
<section class="page-header">
    <div>
        <h2>Election Management</h2>
        <p>Create, schedule, and manage elections. Add candidates with rich profiles and control emergency overrides.</p>
    </div>
    <button class="ghost-button" id="refreshElections" type="button">Sync latest data</button>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Launch a new election</h3>
            <p>Set the time window and provide a compelling description to drive participation.</p>
        </div>
    </header>
    <form id="createElectionForm" class="grid-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <label>
            <span>Election title</span>
            <input type="text" name="title" required placeholder="2025 Student Council Elections">
        </label>
        <label>
            <span>Description</span>
            <textarea name="description" rows="3" placeholder="Highlight what this election is about"></textarea>
        </label>
        <label>
            <span>Start date &amp; time</span>
            <input type="datetime-local" name="start_at" required>
        </label>
        <label>
            <span>End date &amp; time</span>
            <input type="datetime-local" name="end_at" required>
        </label>
        <div class="form-actions">
            <button type="submit" class="primary-button">Create election</button>
            <p class="form-feedback" role="alert"></p>
        </div>
    </form>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Active &amp; upcoming elections</h3>
            <p>Click any election card to view candidates, live stats, and override settings.</p>
        </div>
    </header>
    <div id="electionList" class="card-collection">
        <p class="empty-state">No elections yet. Create one above to get started.</p>
    </div>
</section>

<section class="panel" id="candidateManager" hidden>
    <header>
        <div>
            <h3>Candidate roster</h3>
            <p>Manage candidate biographies, manifestos, and images for <span id="activeElectionTitle"></span>.</p>
        </div>
        <div class="tab-buttons">
            <button class="ghost-button" id="closeCandidateManager" type="button">Close</button>
        </div>
    </header>
    <div class="candidate-layout">
        <form id="candidateForm" class="candidate-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="election_id" id="candidateElectionId">
            <label>
                <span>Name</span>
                <input type="text" name="name" required placeholder="Candidate name">
            </label>
            <label>
                <span>Photo URL</span>
                <input type="url" name="photo" placeholder="https://example.com/photo.jpg">
            </label>
            <label>
                <span>Short bio</span>
                <textarea name="bio" rows="3" placeholder="Background, leadership highlights, etc."></textarea>
            </label>
            <label>
                <span>Manifesto</span>
                <textarea name="manifesto" rows="4" placeholder="Key promises and agenda"></textarea>
            </label>
            <button type="submit" class="primary-button">Add candidate</button>
            <p class="form-feedback" role="alert"></p>
        </form>
        <div class="candidate-list" id="candidateList">
            <p class="empty-state">Add your first candidate to populate this list.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../shared/footer.php'; ?>

