# Kabarak Blockchain Voting System

Modern, end-to-end web application that delivers secure, transparent, and auditable university elections powered by a simulated blockchain written in PHP.

## Features

- **Role-based portals**: dedicated admin analytics console and voter experience.
- **Blockchain-backed voting**: every ballot is sealed in a tamper-evident chain with proof-of-work.
- **Election management**: create elections, onboard candidates, toggle availability, export CSV summaries.
- **Real-time insights**: live vote charts, turnout stats, and blockchain ledger views without external libraries.
- **Security toolbox**: hashed voter identities, CSRF protection, prepared statements, password hashing (bcrypt), and audit logging for overrides.

## Project Structure

```text
kabarak-voting-system/
??? assets/
?   ??? css/            # Global, admin, and user-specific styling
?   ??? js/             # Vanilla JS controllers for auth, admin, and user flows
?   ??? images/         # Place logos and candidate images here
??? backend/
?   ??? api/            # PHP endpoints (auth, elections, votes, blockchain)
?   ??? classes/        # Database, Blockchain, Election, and User services
?   ??? config/         # Environment bootstrap and session/CSRF helpers
??? storage/            # SQLite database lives here (auto-created)
??? templates/          # PHP templates for admin/user portals and shared layout
??? index.php           # Entry point with lightweight routing
??? .htaccess           # API routing helper for Apache/`php -S`
```

## Prerequisites

- PHP 8.1+
- SQLite 3 (bundled with PHP)
- Composer not required (no external dependencies)

## Getting Started

1. **Install PHP extensions** (if not already available): ensure `pdo_sqlite` is enabled.
2. **Serve the app** from the repository root:

   ```bash
   php -S localhost:8080 -t kabarak-voting-system
   ```

3. Visit `http://localhost:8080`.
4. **Default admin credentials**:
   - Email: `admin@kabarak.ac.ke`
   - Password: `Admin@123`
   Update this password immediately from the database or by extending the admin UI.

The database file (`storage/database.sqlite`) and genesis block are created automatically during the first request.

## API Overview

All endpoints live under `/backend/api`. CSRF tokens are required for state-changing POST requests (available via `/backend/api/auth.php?action=status`).

| Endpoint | Method | Description |
| --- | --- | --- |
| `/backend/api/auth.php?action=login` | POST | Authenticate admin or student. |
| `/backend/api/auth.php?action=register` | POST | Register a new student account. |
| `/backend/api/auth.php?action=logout` | POST | Destroy the active session. |
| `/backend/api/election.php?action=list` | GET | **Admin only** ? election catalogue, analytics, and audit log. |
| `/backend/api/election.php?action=public_active` | GET | **Student** ? active elections with candidate lists. |
| `/backend/api/election.php` | POST | **Admin** ? actions `create`, `update`, `delete`, `add_candidate`, `delete_candidate`, `toggle_active`. |
| `/backend/api/election.php?action=export&election_id=ID` | GET | **Admin** ? CSV export for an election. |
| `/backend/api/vote.php?action=stats&election_id=ID` | GET | Election stats for charts (admin + student access). |
| `/backend/api/vote.php?action=history` | GET | **Student** ? personal voting history without revealing ballot choices. |
| `/backend/api/vote.php` | POST | **Student** ? `action=vote` adds a new vote block. |
| `/backend/api/blockchain.php?action=chain` | GET | **Admin** ? full blockchain ledger. |
| `/backend/api/blockchain.php?action=verify` | GET | **Admin** ? chain integrity check. |

All POST payloads should be JSON objects; responses are JSON with `success` and `message` fields plus any data payload.

## Blockchain Simulation

- **Block content**: vote metadata (hashed voter ID, election ID, candidate ID, timestamp) or system events.
- **Hashing**: SHA?256 over index, timestamp, payload JSON, previous hash, and nonce.
- **Proof-of-work**: adjustable difficulty (default `3` leading zeros) configured in `backend/config/config.php`.
- **Persistence**: each block is saved to the `blocks` table inside SQLite; the chain rebuilds from storage on every request.
- **Validation**: admins can run full-chain verification from the blockchain page or via the `/blockchain.php?action=verify` API.

## Security Notes

- Sessions are locked down with `SameSite=Strict` cookies.
- Passwords use PHP?s `password_hash` (bcrypt by default).
- All SQL uses prepared statements to prevent injection.
- CSRF protection enforced for every sensitive action; tokens generated server-side per session.
- Voter anonymity: ballots store a salted SHA-256 of the student identifier (no plain IDs).

## Styling & UX

- Fully responsive layouts driven by CSS Grid/Flexbox and custom animations.
- No UI frameworks ? all interactivity handled with vanilla ES6 modules.
- Modern typography via Google Fonts (`Poppins`).

## Development Tips

- To reset the system, delete `storage/database.sqlite` and reload the site to trigger a fresh schema + genesis block.
- Candidate images can be placed inside `assets/images` and referenced by URL from the admin UI.
- Difficulty or session settings can be adjusted in `backend/config/config.php`.

## Roadmap Ideas

- Add password reset flows and admin password management UI.
- Implement multi-factor authentication for admins.
- Build export/import utilities for candidate bulk uploads.
- Integrate email/web push notifications for important election milestones.

Enjoy building secure, transparent elections for Kabarak University!
