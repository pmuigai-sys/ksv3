(() => {
    const role = document.body.dataset.userRole;
    if (role !== 'student') return;

    const endpoints = KVS.endpoints;

    const createElement = (tag, className, text) => {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    };

    const renderBarChart = (canvas, labels, values) => {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const dpi = window.devicePixelRatio || 1;
        const width = canvas.clientWidth || 560;
        const height = canvas.clientHeight || 320;
        canvas.width = width * dpi;
        canvas.height = height * dpi;
        ctx.scale(dpi, dpi);

        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, width, height);

        const padding = 28;
        const barHeight = 24;
        const gap = 16;
        const maxValue = Math.max(...values, 1);

        ctx.font = '13px Poppins';
        ctx.fillStyle = '#1a1a1f';

        labels.forEach((label, index) => {
            const value = values[index];
            const y = padding + index * (barHeight + gap);
            const barWidth = ((width - padding * 2) * value) / maxValue;

            ctx.fillStyle = '#6b7280';
            ctx.fillText(label, padding, y - 4);

            ctx.fillStyle = '#0056ff';
            if (ctx.roundRect) {
                ctx.beginPath();
                ctx.roundRect(padding, y, Math.max(barWidth, 6), barHeight, 10);
                ctx.fill();
            } else {
                ctx.fillRect(padding, y, Math.max(barWidth, 6), barHeight);
            }

            ctx.fillStyle = '#0f172a';
            ctx.fillText(String(value), padding + Math.max(barWidth, 6) + 10, y + barHeight - 6);
        });
    };

    const formatDate = (dateTime) => new Date(dateTime).toLocaleString();

    /** Home page **/
    const initHome = () => {
        const activeElectionsContainer = document.getElementById('activeElections');
        const historyTable = document.querySelector('#votingHistory tbody');
        if (!activeElectionsContainer || !historyTable) return;

        const renderElections = (elections) => {
            activeElectionsContainer.innerHTML = '';
            if (!elections.length) {
                activeElectionsContainer.innerHTML = '<p class="empty-state">We will notify you once a new election opens.</p>';
                return;
            }

            elections.forEach((election) => {
                const card = createElement('article', 'card vote-election');
                const header = createElement('header');
                header.appendChild(createElement('h4', null, election.title));
                header.appendChild(createElement('span', 'status-pill', `${new Date(election.start_at).toLocaleDateString()} - ${new Date(election.end_at).toLocaleDateString()}`));
                const description = createElement('p', null, election.description || 'Election details coming soon.');
                const button = createElement('a', 'primary-button', 'Vote now');
                button.href = KVS.getIndexPath() + '?page=vote';
                header.appendChild(button);

                card.append(header, description);
                activeElectionsContainer.appendChild(card);
            });
        };

        const renderHistory = (items) => {
            historyTable.innerHTML = '';
            if (!items.length) {
                const row = createElement('tr');
                const cell = createElement('td', null, 'No votes recorded yet.');
                cell.colSpan = 2;
                row.appendChild(cell);
                historyTable.appendChild(row);
                return;
            }

            items.forEach((entry) => {
                const row = createElement('tr');
                row.appendChild(createElement('td', null, entry.title));
                row.appendChild(createElement('td', null, formatDate(entry.created_at)));
                historyTable.appendChild(row);
            });
        };

        const loadData = async () => {
            try {
                const [electionsResponse, historyResponse] = await Promise.all([
                    KVS.getJson(endpoints.elections, { action: 'public_active' }),
                    KVS.getJson(endpoints.vote, { action: 'history' }),
                ]);
                renderElections(electionsResponse.data || []);
                renderHistory(historyResponse.data || []);
            } catch (error) {
                console.error(error);
            }
        };

        loadData();
    };

    /** Vote page **/
    const initVotePage = () => {
        const container = document.getElementById('voteElections');
        const statusLabel = document.getElementById('voteStatus');
        const confirmDialog = document.getElementById('voteConfirmation');
        const confirmName = document.getElementById('confirmCandidateName');
        const confirmElection = document.getElementById('confirmElectionTitle');
        const voteFeedback = document.getElementById('voteFeedback');
        const castVoteForm = document.getElementById('castVoteForm');
        const selectedElectionInput = document.getElementById('selectedElectionId');
        const selectedCandidateInput = document.getElementById('selectedCandidateId');
        if (!container || !castVoteForm) return;

        let electionsCache = [];
        let pendingPayload = null;

        const buildCandidateCard = (candidate, election) => {
            const tile = createElement('div', 'candidate-tile');
            const img = createElement('img');
            img.src = candidate.photo || 'https://via.placeholder.com/300x200.png?text=Candidate';
            img.alt = `${candidate.name} headshot`;
            const name = createElement('h4', null, candidate.name);
            const bio = createElement('p', null, candidate.bio || 'Bio coming soon.');
            const manifesto = createElement('p', 'card-meta', candidate.manifesto || 'Manifesto not provided.');
            tile.append(img, name, bio, manifesto);
            tile.addEventListener('click', () => {
                container.querySelectorAll('.candidate-tile').forEach((node) => node.classList.remove('selected'));
                tile.classList.add('selected');
                statusLabel.textContent = `Selected ${candidate.name}`;
                selectedElectionInput.value = election.id;
                selectedCandidateInput.value = candidate.id;
                pendingPayload = { election, candidate };
                confirmName.textContent = candidate.name;
                confirmElection.textContent = election.title;
                confirmDialog.showModal();
            });
            return tile;
        };

        const renderElections = (elections) => {
            container.innerHTML = '';
            if (!elections.length) {
                container.innerHTML = '<p class="empty-state">No open elections right now. Check back soon.</p>';
                return;
            }

            elections.forEach((election) => {
                const wrapper = createElement('article', 'vote-election');
                const header = createElement('header');
                header.appendChild(createElement('h3', null, election.title));
                const meta = createElement('span', 'status-pill', `${new Date(election.end_at).toLocaleString()} deadline`);
                header.append(meta);
                const description = createElement('p', null, election.description || 'Election details coming soon.');
                const grid = createElement('div', 'candidate-grid');
                election.candidates.forEach((candidate) => {
                    grid.appendChild(buildCandidateCard(candidate, election));
                });
                wrapper.append(header, description, grid);
                container.appendChild(wrapper);
            });
        };

        const submitVote = async () => {
            if (!pendingPayload) return;
            voteFeedback.textContent = 'Submitting vote...';
            voteFeedback.classList.remove('error', 'success');
            confirmDialog.close();
            try {
                const payload = KVS.serializeForm(castVoteForm);
                payload.action = 'vote';
                const response = await KVS.postJson(endpoints.vote, payload);
                voteFeedback.textContent = `Vote secured. Block hash: ${response.block_hash.slice(0, 12)}...`;
                voteFeedback.classList.add('success');
                statusLabel.textContent = 'Vote recorded';
                selectedCandidateInput.value = '';
                selectedElectionInput.value = '';
                pendingPayload = null;
                // Reload elections to show updated vote counts
                await loadElections();
            } catch (error) {
                voteFeedback.textContent = error.payload?.message || error.message;
                voteFeedback.classList.add('error');
                statusLabel.textContent = 'Vote failed';
            }
        };

        confirmDialog?.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            confirmDialog.close();
        });

        confirmDialog?.querySelector('[data-action="confirm"]').addEventListener('click', submitVote);

        const loadElections = async () => {
            try {
                const response = await KVS.getJson(endpoints.elections, { action: 'public_active' });
                electionsCache = response.data || [];
                renderElections(electionsCache);
            } catch (error) {
                container.innerHTML = `<p class="empty-state">${error.message}</p>`;
            }
        };

        loadElections();
    };

    /** Stats page **/
    const initStatsPage = () => {
        const selector = document.getElementById('statsElectionSelector');
        const chartCanvas = document.getElementById('userStatsChart');
        const ledgerTable = document.querySelector('#userLedgerPreview tbody');
        const refreshButton = document.getElementById('refreshStats');
        if (!selector || !chartCanvas || !ledgerTable) return;

        const totalVotesStat = document.querySelector('[data-stat="user-total-votes"] .value');
        const turnoutStat = document.querySelector('[data-stat="user-turnout"] .value');
        const chainStat = document.querySelector('[data-stat="user-chain"] .value');

        let electionsCache = [];

        const loadLedger = async () => {
            try {
                const response = await KVS.getJson(`${endpoints.blockchain}?action=chain`);
                const chain = response.data || [];
                ledgerTable.innerHTML = '';
                chain.slice(-8).reverse().forEach((block) => {
                    const row = createElement('tr');
                    row.appendChild(createElement('td', null, block.block_index));
                    row.appendChild(createElement('td', null, block.hash.slice(0, 18) + '?'));
                    row.appendChild(createElement('td', null, block.data.type || 'N/A'));
                    row.appendChild(createElement('td', null, formatDate(block.timestamp)));
                    ledgerTable.appendChild(row);
                });
            } catch (error) {
                ledgerTable.innerHTML = `<tr><td colspan="4">${error.message}</td></tr>`;
            }
        };

        const renderElectionStats = async (electionId) => {
            if (!electionId) return;
            try {
                const response = await KVS.getJson(endpoints.vote, { action: 'stats', election_id: electionId });
                const labels = response.data.map((entry) => entry.name);
                const values = response.data.map((entry) => Number(entry.votes));
                renderBarChart(chartCanvas, labels, values);
                if (totalVotesStat) totalVotesStat.textContent = response.total_votes;
                if (turnoutStat) turnoutStat.textContent = `${response.turnout_percentage}%`;
                if (chainStat) {
                    chainStat.textContent = response.chain_valid ? 'Chain valid' : 'Integrity risk';
                    chainStat.classList.toggle('valid', response.chain_valid);
                    chainStat.classList.toggle('invalid', !response.chain_valid);
                }
            } catch (error) {
                console.error(error);
            }
        };

        const populateSelector = (elections) => {
            selector.innerHTML = '<option value="" disabled selected>Choose election</option>';
            elections.forEach((election) => {
                const option = createElement('option');
                option.value = election.id;
                option.textContent = election.title;
                selector.appendChild(option);
            });
        };

        const loadElections = async () => {
            try {
                const response = await KVS.getJson(endpoints.elections, { action: 'public_active' });
                electionsCache = response.data || [];
                populateSelector(electionsCache);
                const first = electionsCache[0];
                if (first) {
                    selector.value = first.id;
                    renderElectionStats(first.id);
                }
                loadLedger();
            } catch (error) {
                console.error(error);
            }
        };

        selector.addEventListener('change', (event) => {
            renderElectionStats(event.target.value);
        });

        refreshButton?.addEventListener('click', () => {
            renderElectionStats(selector.value);
            loadLedger();
        });

        loadElections();
    };

    initHome();
    initVotePage();
    initStatsPage();
})();

