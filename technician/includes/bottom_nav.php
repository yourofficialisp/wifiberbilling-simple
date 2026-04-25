<?php
// Get current page context
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', str_replace('\\', '/', dirname($current_path)));
$current_dir = end($path_parts); // 'technician', 'tasks', 'map', 'devices'

// Determine relative path to technician root
$rel_path = '';
// List of subdirectories inside technician
$subdirs = ['tasks', 'map', 'devices', 'includes'];

if (in_array($current_dir, $subdirs)) {
    $rel_path = '../';
}

// Normalize active state check
$current_file = basename($current_path);
$is_home = ($current_file == 'dashboard.php');
// Active if in tasks folder OR filename contains 'task'
$is_tasks = ($current_dir == 'tasks' || strpos($current_file, 'task') !== false);
// Active if in map folder OR filename contains 'map'
$is_map = ($current_dir == 'map' || strpos($current_file, 'map') !== false);
// Active if in devices folder OR filename contains 'search' or 'manage'
$is_devices = ($current_dir == 'devices' || strpos($current_file, 'search') !== false || strpos($current_file, 'manage') !== false);
$is_profile = ($current_file == 'profile.php');
?>
<style>
    /* Bottom Navigation */
    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: var(--bg-card);
        display: flex;
        justify-content: space-around;
        padding: 12px 0;
        border-top: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 -5px 20px rgba(0,0,0,0.5);
        z-index: 9999;
    }
    
    .nav-item {
        color: var(--text-secondary);
        text-decoration: none;
        text-align: center;
        font-size: 0.75rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        transition: 0.2s;
    }
    
    .nav-item.active {
        color: var(--primary);
    }
    
    .nav-item i {
        font-size: 1.2rem;
        margin-bottom: 2px;
    }
    
    /* Add padding to body to prevent content being hidden behind nav */
    body {
        padding-bottom: 70px !important;
    }

    body.light-theme {
        --bg-dark: #f6f7fb;
        --bg-card: #ffffff;
        --text-primary: #0b1220;
        --text-secondary: rgba(11, 18, 32, 0.7);
        --primary: #0b5fff;
    }

    body.light-theme .bottom-nav {
        border-top: 1px solid rgba(11, 18, 32, 0.12);
        box-shadow: 0 -5px 20px rgba(17,24,39,0.08);
    }

    .theme-toggle-fab {
        position: fixed;
        right: 14px;
        bottom: 84px;
        width: 44px;
        height: 44px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.10);
        background: var(--bg-card);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        z-index: 10000;
        cursor: pointer;
    }

    body.light-theme .theme-toggle-fab {
        border: 1px solid rgba(11, 18, 32, 0.12);
        box-shadow: 0 10px 30px rgba(17,24,39,0.10);
    }
</style>

<button type="button" class="theme-toggle-fab" onclick="toggleTheme()" title="Mode Siang/Malam">
    <i class="fas fa-moon" id="techThemeIcon"></i>
</button>

<div class="bottom-nav">
    <a href="<?php echo $rel_path; ?>dashboard.php" class="nav-item <?php echo $is_home ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        Home
    </a>
    <a href="<?php echo $rel_path; ?>tasks/index.php" class="nav-item <?php echo $is_tasks ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-list"></i>
        Tasks
    </a>
    <a href="<?php echo $rel_path; ?>devices/search.php" class="nav-item <?php echo $is_devices ? 'active' : ''; ?>">
        <i class="fas fa-search"></i>
        Check Device
    </a>
    <a href="<?php echo $rel_path; ?>map/index.php" class="nav-item <?php echo $is_map ? 'active' : ''; ?>">
        <i class="fas fa-map-marked-alt"></i>
        Map
    </a>
    <a href="<?php echo $rel_path; ?>profile.php" class="nav-item <?php echo $is_profile ? 'active' : ''; ?>">
        <i class="fas fa-user-circle"></i>
        Profile
    </a>
</div>

<script>
    function applyTheme(theme) {
        const body = document.body;
        const icon = document.getElementById('techThemeIcon');
        if (theme === 'light') {
            body.classList.add('light-theme');
            if (icon) icon.className = 'fas fa-sun';
        } else {
            body.classList.remove('light-theme');
            if (icon) icon.className = 'fas fa-moon';
        }
    }

    function toggleTheme() {
        const isLight = document.body.classList.contains('light-theme');
        const next = isLight ? 'dark' : 'light';
        localStorage.setItem('theme', next);
        applyTheme(next);
    }

    (function initTheme() {
        const saved = localStorage.getItem('theme');
        const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        const theme = saved ? saved : (prefersLight ? 'light' : 'dark');
        applyTheme(theme);
    })();
</script>
