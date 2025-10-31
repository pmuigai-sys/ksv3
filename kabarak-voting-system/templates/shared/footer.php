    </main>
    <footer class="app-footer">
        <div>
            <p>&copy; <?= date('Y') ?> Kabarak University. All rights reserved.</p>
            <p class="footnote">Empowering secure, transparent student elections.</p>
        </div>
        <div class="badge-group">
            <span class="badge">Blockchain Secured</span>
            <span class="badge">Real-time Analytics</span>
        </div>
    </footer>
    <script src="/assets/js/main.js" defer></script>
    <?php if (($role ?? '') === 'admin'): ?>
        <script src="/assets/js/admin.js" defer></script>
    <?php elseif (($role ?? '') === 'student'): ?>
        <script src="/assets/js/user.js" defer></script>
    <?php endif; ?>
</body>
</html>

