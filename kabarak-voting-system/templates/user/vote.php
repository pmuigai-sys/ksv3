<?php
declare(strict_types=1);

require __DIR__ . '/../shared/header.php';

$csrfToken = kvs_csrf_token();
?>
<section class="page-header">
    <div>
        <h2>Cast Your Vote</h2>
        <p>Select your preferred candidate. Once submitted, your vote is sealed on the blockchain and cannot be altered.</p>
    </div>
    <span class="status-pill" id="voteStatus">Awaiting selection</span>
</section>

<section class="panel">
    <header>
        <div>
            <h3>Available ballots</h3>
            <p>Only elections that are currently accepting votes appear here. Review candidate manifestos before submitting.</p>
        </div>
    </header>
    <div id="voteElections" class="vote-layout">
        <p class="empty-state">No open elections right now. Check back soon.</p>
    </div>
</section>

<form id="castVoteForm" class="hidden-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="election_id" id="selectedElectionId">
    <input type="hidden" name="candidate_id" id="selectedCandidateId">
</form>

<dialog id="voteConfirmation" class="modal">
    <div class="modal-content">
        <h3>Confirm your vote</h3>
        <p>You're about to submit your ballot for <span id="confirmCandidateName"></span> in <span id="confirmElectionTitle"></span>.</p>
        <p class="modal-note">This action is irreversible. Once confirmed, your vote is cryptographically sealed.</p>
        <div class="modal-actions">
            <button type="button" class="ghost-button" data-action="cancel">Cancel</button>
            <button type="button" class="primary-button" data-action="confirm">Cast vote</button>
        </div>
        <p class="form-feedback" id="voteFeedback" role="alert"></p>
    </div>
</dialog>

<?php require __DIR__ . '/../shared/footer.php'; ?>

