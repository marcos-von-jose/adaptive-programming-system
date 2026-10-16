<script>
    try {
        document.documentElement.dataset.theme = localStorage.getItem('aps-theme') === 'dark' ? 'dark' : 'light';
    } catch {
        document.documentElement.dataset.theme = 'light';
    }
</script>
