{{-- Fixed visual identity; saved account/device theme preferences are intentionally ignored. --}}
<script>
(() => {
    const root = document.documentElement;
    root.dataset.themeName = 'premium-glass';
    root.dataset.theme = 'unified';
})();
</script>
<style>:root { --theme-background-image: url(@json(asset('images/theme/main-bg.png'), JSON_UNESCAPED_SLASHES)); }</style>
