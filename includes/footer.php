        </section>
    </main>
</div>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= rtrim(APP_BASE, '/') ?>/sw.js').catch(() => {});
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= rtrim(APP_BASE, '/') ?>/assets/js/main.js?v=2"></script>
</body>
</html>
