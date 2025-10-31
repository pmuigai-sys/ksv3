(() => {
    const role = document.body.dataset.userRole;
    if (role !== 'admin') return;

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
        const width = canvas.clientWidth || 600;
        const height = canvas.clientHeight || 320;
        canvas.width = width * dpi;
        canvas.height = height * dpi;
        ctx.scale(dpi, dpi);

        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = '#f3f4f6';
        ctx.fillRect(0, 0, width, height);

        const padding = 32;
        const barHeight = 28;
        const barGap = 18;
        const maxValue = Math.max(...values, 1);

        ctx.font = '14px Poppins';
        ctx.fillStyle = '#1a1a1f';

        labels.forEach((label, index) => {
            const value = values[index];
            const top = padding + index * (barHeight + barGap);
            const barWidth = ((width - padding * 2) * value) / maxValue;

            ctx.fillStyle = '#6b7280';
            ctx.fillText(label, padding, top - 6);

            const gradient = ctx.createLinearGradient(padding, top, padding + barWidth, top + barHeight);
            gradient.addColorStop(0, '#0056ff');
            gradient.addColorStop(1, '#00c6ff');
            ctx.fillStyle = gradient;
            if (ctx.roundRect) {
                ctx.beginPath();
                ctx.roundRect(padding, top, Math.max(barWidth, 4), barHeight, 12);
                ctx.fill();
            } else {
                ctx.fillRect(padding, top, Math.max(barWidth, 4), barHeight);
            }

            ctx.fillStyle = '#0f172a';
            ctx.fillText(String(value), padding + Math.max(barWidth, 4) + 12, top + barHeight - 6);
        });
    };

    const formatDate = (dateTime) => new Date(dateTime).toLocaleString();

    const updateStatCard = (selector, value, formatter = (v) => v) => {
        const card = document.querySelector(selector);
        if (!card) return;
        const valueEl = card.querySelector('.value');
        if (valueEl) valueEl.textContent = formatter(value);
    };

    /** Dashboard **/
    const initDashboard = () => {
        const analytics = document.getElementById('adminAnalytics');
        if (!analytics) return;

        const electionSelector = document.getElementById('electionSelector');
        const votesChart = document.getElementById('votesChart');
        const ledgerPreview = document.getElementById('ledgerPreview');
        const chainStat = analytics.querySelector('[data-stat="chain-status"] .value');
        const exportButton = document.getElementById('exportCsv');
        const refreshButton = document.getElementById('refreshDashboard');
        const viewLedgerButton = document.getElementById('viewFullLedger');
        const auditTableBody = document.querySelector('#auditTable tbody');

        let electionsCache = [];

        const populateAudit = (logs = []) => {
            if (!auditTableBody) return;
            auditTableBody.innerHTML = '';
            if (logs.length === 0) {
                const row = createElement('tr');
                const cell = createElement('td');
                cell.colSpan = 4;
                cell.textContent = 'No recent admin activity.';
                row.appendChild(cell);
                auditTableBody.appendChild(row);
                return;
            }

            logs.forEach((log) => {
                const row = createElement('tr');
                const payloadSummary = log.payload?.election?.title || log.payload?.candidate?.name || JSON.stringify(log.payload || {});
                row.appendChild(createElement('td', null, payloadSummary));
                row.appendChild(createElement('td', null, log.action));
                row.appendChild(createElement('td', null, log.user_name || 'System'));
                row.appendChild(createElement('td', null, formatDate(log.created_at)));
                auditTableBody.appendChild(row);
            });
        };

        const populateSelector = (items) => {
            if (!electionSelector) return;
            electionSelector.innerHTML = '<option value="" disabled selected>Choose election</option>';
            items.forEach((election) => {
                const option = createElement('option');
                option.value = election.id;
                option.textContent = `${election.title} (${new Date(election.start_at).getFullYear()})`;
                electionSelector.appendChild(option);
            });
        };

        const loadLedgerPreview = async () => {
            try {
                const response = await KVS.getJson(`${endpoints.blockchain}?action=chain`);
                const chain = response.data || [];
                ledgerPreview.innerHTML = '';
                chain.slice(-6).reverse().forEach((block) => {
                    const row = createElement('tr');
                    row.appendChild(createElement('td', null, block.block_index));
                    row.appendChild(createElement('td', null, block.hash.slice(0, 18) + '?'));
                    row.appendChild(createElement('td', null, block.previous_hash.slice(0, 12) + '?'));
                    row.appendChild(createElement('td', null, formatDate(block.timestamp)));
                    ledgerPreview.appendChild(row);
                });
            } catch (error) {
                ledgerPreview.innerHTML = `<tr><td colspan="4">${error.message}</td></tr>`;
            }
        };

        const renderElectionStats = async (electionId) => {
            if (!electionId) return;
            try {
                const response = await KVS.getJson(`${endpoints.vote}`, { action: 'stats', election_id: electionId });
                const labels = response.data.map((entry) => entry.name);
                const values = response.data.map((entry) => Number(entry.votes));
                renderBarChart(votesChart, labels, values);
                const chainValid = response.chain_valid;
                if (chainStat) {
                    chainStat.textContent = chainValid ? 'Chain valid' : 'Integrity risk';
                    chainStat.classList.toggle('valid', chainValid);
                    chainStat.classList.toggle('invalid', !chainValid);
                }
                updateStatCard('[data-stat="turnout"]', response.turnout_percentage, (v) => `${v}%`);
                updateStatCard('[data-stat="votes-cast"]', response.total_votes);
            } catch (error) {
                console.error(error);
            }
        };

        const loadDashboard = async () => {
            try {
                refreshButton?.classList.add('loading');
                const response = await KVS.getJson(`${endpoints.elections}`, { action: 'list' });
                electionsCache = response.data || [];
                populateSelector(electionsCache);
                updateStatCard('[data-stat="total-elections"]', response.meta?.total_elections || 0);
                updateStatCard('[data-stat="votes-cast"]', response.meta?.total_votes || 0);
                updateStatCard('[data-stat="turnout"]', response.meta?.registered_voters ? Math.round((response.meta.total_votes / Math.max(response.meta.registered_voters, 1)) * 100) : 0, (v) => `${v}%`);
                if (chainStat) {
                    chainStat.textContent = response.chain_valid ? 'Chain valid' : 'Integrity risk';
                    chainStat.classList.toggle('valid', response.chain_valid);
                    chainStat.classList.toggle('invalid', !response.chain_valid);
                }
                populateAudit(response.audit_logs || []);
                loadLedgerPreview();
                const firstElection = electionsCache[0];
                if (firstElection) {
                    electionSelector.value = firstElection.id;
                    await renderElectionStats(firstElection.id);
                }
            } catch (error) {
                console.error(error);
            } finally {
                refreshButton?.classList.remove('loading');
            }
        };

        electionSelector?.addEventListener('change', (event) => {
            renderElectionStats(event.target.value);
        });

        exportButton?.addEventListener('click', async () => {
            const electionId = electionSelector?.value;
            if (!electionId) {
                alert('Select an election to export results.');
                return;
            }
            try {
                const response = await KVS.getJson(`${endpoints.elections}`, { action: 'export', election_id: electionId });
                const link = document.createElement('a');
                link.href = response.data_url;
                link.download = response.filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                alert(error.message);
            }
        });

        viewLedgerButton?.addEventListener('click', () => {
            window.location.href = '/index.php?page=blockchain';
        });

        refreshButton?.addEventListener('click', loadDashboard);

        loadDashboard();
    };

    /** Election management **/
    const initElectionManagement = () => {
        const electionList = document.getElementById('electionList');
        if (!electionList) return;

        const createForm = document.getElementById('createElectionForm');
        const refreshButton = document.getElementById('refreshElections');
        const candidatePanel = document.getElementById('candidateManager');
        const candidateForm = document.getElementById('candidateForm');
        const candidateList = document.getElementById('candidateList');
        const candidateElectionId = document.getElementById('candidateElectionId');
        const activeElectionTitle = document.getElementById('activeElectionTitle');
        const closeCandidateManager = document.getElementById('closeCandidateManager');

        let electionsCache = [];
        let currentElection = null;

        const renderCandidates = (election) => {
            if (!candidateList) return;
            candidateList.innerHTML = '';
            if (!election || !election.candidates?.length) {
                candidateList.innerHTML = '<p class="empty-state">Add your first candidate to populate this list.</p>';
                return;
            }

            election.candidates.forEach((candidate) => {
                const card = createElement('div', 'candidate-card');
                const img = createElement('img');
                img.src = candidate.photo || 'https://via.placeholder.com/80x80.png?text=KVS';
                img.alt = `${candidate.name} photo`;
                const details = createElement('div');
                const name = createElement('h4', null, candidate.name);
                const bio = createElement('p', null, candidate.bio || 'Bio coming soon');
                const manifesto = createElement('p', 'card-meta', candidate.manifesto || 'Manifesto not provided.');
                details.append(name, bio, manifesto);
                const actions = createElement('div', 'candidate-actions');
                const removeBtn = createElement('button', 'ghost-button', 'Remove');
                removeBtn.type = 'button';
                removeBtn.addEventListener('click', async () => {
                    if (!confirm('Remove this candidate?')) return;
                    try {
                        const payload = {
                            action: 'delete_candidate',
                            candidate_id: candidate.id,
                            csrf_token: createForm.querySelector('[name="csrf_token"]').value,
                        };
                        await KVS.postJson(endpoints.elections, payload);
                        await loadElections();
                        openCandidateManager(election.id);
                    } catch (error) {
                        alert(error.message);
                    }
                });
                actions.append(removeBtn);
                card.append(img, details, actions);
                candidateList.appendChild(card);
            });
        };

        const buildElectionCard = (election) => {
            const card = createElement('article', 'card');
            const title = createElement('h4', null, election.title);
            const meta = createElement('p', 'card-meta', `${formatDate(election.start_at)} to ${formatDate(election.end_at)}`);
            const stats = createElement('p', null, `${election.total_votes} votes - ${election.candidates.length} candidates`);
            const controls = createElement('div');
            controls.style.display = 'flex';
            controls.style.gap = '0.75rem';

            const viewCandidatesBtn = createElement('button', 'ghost-button', 'Manage candidates');
            viewCandidatesBtn.type = 'button';
            viewCandidatesBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                openCandidateManager(election.id);
            });

            const toggleButton = createElement('button', 'ghost-button', election.is_active ? 'Deactivate' : 'Activate');
            toggleButton.type = 'button';
            toggleButton.addEventListener('click', async (event) => {
                event.stopPropagation();
                try {
                    const payload = {
                        action: 'toggle_active',
                        id: election.id,
                        title: election.title,
                        description: election.description,
                        start_at: election.start_at,
                        end_at: election.end_at,
                        is_active: election.is_active ? 0 : 1,
                        csrf_token: createForm.querySelector('[name="csrf_token"]').value,
                    };
                    await KVS.postJson(endpoints.elections, payload);
                    await loadElections();
                } catch (error) {
                    alert(error.message);
                }
            });

            controls.append(viewCandidatesBtn, toggleButton);
            card.append(title, meta, stats, controls);
            card.addEventListener('click', () => openCandidateManager(election.id));
            if (!election.is_active) {
                const banner = createElement('div', 'override-banner', 'Inactive - votes will not be accepted');
                card.insertBefore(banner, title);
            }
            return card;
        };

        const populateElectionCards = () => {
            electionList.innerHTML = '';
            if (!electionsCache.length) {
                electionList.innerHTML = '<p class="empty-state">No elections yet. Create one above to get started.</p>';
                return;
            }

            electionsCache.forEach((election) => {
                electionList.appendChild(buildElectionCard(election));
            });
        };

        const openCandidateManager = (electionId) => {
            const election = electionsCache.find((item) => String(item.id) === String(electionId));
            if (!election) return;
            currentElection = election;
            candidatePanel.hidden = false;
            candidateElectionId.value = election.id;
            activeElectionTitle.textContent = election.title;
            renderCandidates(election);
        };

        closeCandidateManager?.addEventListener('click', () => {
            candidatePanel.hidden = true;
        });

        const loadElections = async () => {
            try {
                const response = await KVS.getJson(endpoints.elections, { action: 'list' });
                electionsCache = response.data || [];
                populateElectionCards();
                if (currentElection) {
                    openCandidateManager(currentElection.id);
                }
            } catch (error) {
                console.error(error);
            }
        };

        createForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const feedback = createForm.querySelector('.form-feedback');
            feedback.textContent = 'Creating election...';
            feedback.classList.remove('error', 'success');
            try {
                const payload = KVS.serializeForm(createForm);
                payload.action = 'create';
                await KVS.postJson(endpoints.elections, payload);
                feedback.textContent = 'Election created successfully.';
                feedback.classList.add('success');
                createForm.reset();
                await loadElections();
            } catch (error) {
                feedback.textContent = error.payload?.message || error.message;
                feedback.classList.add('error');
            }
        });

        candidateForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!candidateElectionId.value) return;
            const feedback = candidateForm.querySelector('.form-feedback');
            feedback.textContent = 'Adding candidate...';
            feedback.classList.remove('error', 'success');
            try {
                const payload = KVS.serializeForm(candidateForm);
                payload.action = 'add_candidate';
                await KVS.postJson(endpoints.elections, payload);
                feedback.textContent = 'Candidate added.';
                feedback.classList.add('success');
                candidateForm.reset();
                await loadElections();
                openCandidateManager(candidateElectionId.value);
            } catch (error) {
                feedback.textContent = error.payload?.message || error.message;
                feedback.classList.add('error');
            }
        });

        refreshButton?.addEventListener('click', loadElections);

        loadElections();
    };

    /** Blockchain page **/
    const initBlockchainPage = () => {
        const tableBody = document.querySelector('#blockchainTable tbody');
        if (!tableBody) return;

        const statusLabel = document.getElementById('chainStatus');
        const verifyButton = document.getElementById('verifyChain');
        const payloadView = document.getElementById('blockPayload');
        const filterButtons = document.querySelectorAll('[data-filter]');

        let chainCache = [];

        const renderTable = (blocks) => {
            tableBody.innerHTML = '';
            blocks.forEach((block) => {
                const row = createElement('tr');
                row.dataset.index = block.block_index;
                row.appendChild(createElement('td', null, block.block_index));
                row.appendChild(createElement('td', null, block.hash.slice(0, 24) + '?'));
                row.appendChild(createElement('td', null, block.previous_hash.slice(0, 16) + '?'));
                row.appendChild(createElement('td', null, block.nonce));
                row.appendChild(createElement('td', null, block.data.type || 'N/A'));
                row.appendChild(createElement('td', null, formatDate(block.timestamp)));
                row.addEventListener('click', () => {
                    tableBody.querySelectorAll('tr').forEach((tr) => tr.classList.remove('selected'));
                    row.classList.add('selected');
                    payloadView.textContent = JSON.stringify(block, null, 2);
                });
                tableBody.appendChild(row);
            });
        };

        const applyFilter = (filter) => {
            if (!chainCache.length) return;
            if (filter === 'vote') {
                renderTable(chainCache.filter((block) => block.data.type === 'vote'));
            } else if (filter === 'genesis') {
                renderTable(chainCache.filter((block) => block.data.type !== 'vote'));
            } else {
                renderTable(chainCache);
            }
        };

        const loadChain = async () => {
            try {
                const response = await KVS.getJson(`${endpoints.blockchain}?action=chain`);
                chainCache = response.data || [];
                renderTable(chainCache);
                payloadView.textContent = 'Choose a block to view the raw JSON payload.';
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="6">${error.message}</td></tr>`;
            }
        };

        verifyButton?.addEventListener('click', async () => {
            statusLabel.textContent = 'Verifying...';
            try {
                const response = await KVS.getJson(`${endpoints.blockchain}?action=verify`);
                statusLabel.textContent = response.valid ? 'Chain valid' : 'Integrity risk';
                statusLabel.classList.toggle('valid', response.valid);
                statusLabel.classList.toggle('invalid', !response.valid);
            } catch (error) {
                statusLabel.textContent = error.message;
            }
        });

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                filterButtons.forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');
                applyFilter(button.dataset.filter);
            });
        });

        loadChain();
        verifyButton?.click();
    };

    initDashboard();
    initElectionManagement();
    initBlockchainPage();
})();

