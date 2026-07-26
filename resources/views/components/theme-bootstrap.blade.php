@php
    $savedTheme = auth()->user()?->theme_name ?? 'default-violet';
    $savedMode = auth()->user()?->theme_mode ?? 'dark';
@endphp
<script>
(()=>{const themes=['default-violet','forest-moss','cocoa-dust','twilight-blue','moonstone'],modes=['light','dark','system'],r=document.documentElement,legacy=localStorage.getItem('basha-palette'),serverTheme=@json($savedTheme),serverMode=@json($savedMode),theme=themes.includes(legacy)?legacy:(legacy==='violet'?'default-violet':(themes.includes(serverTheme)?serverTheme:'default-violet')),storedMode=localStorage.getItem('basha-theme-mode')||localStorage.getItem('basha-theme'),mode=modes.includes(storedMode)?storedMode:(modes.includes(serverMode)?serverMode:'dark');r.dataset.themeName=theme;r.dataset.palette=theme==='default-violet'?'violet':theme;r.dataset.mode=mode;r.dataset.theme=mode==='system'?(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'):mode})();
</script>
@auth
<meta name="theme-preference-url" content="{{ route('theme-preference.store') }}">
@endauth
