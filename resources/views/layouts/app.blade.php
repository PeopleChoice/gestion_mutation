<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Gestion des Mutations') - Impôts & Domaines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --sidebar-collapsed: 70px; --primary: #C8A951; --primary-dark: #5D4E37; --primary-darker: #3A2F1E; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f3ef; }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #5D4E37 0%, #3A2F1E 100%);
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            padding-top: 0;
            transition: width .3s ease;
            overflow-x: hidden;
        }
        .sidebar .brand {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar .brand img { width: 180px; margin-bottom: 8px; transition: width .3s; }
        .sidebar .brand h5 { color: #fff; margin: 0; font-size: 12px; transition: opacity .2s; }
        .sidebar .brand small { color: #C8A951; font-size: 11px; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 10px 20px;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: all .2s;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(200,169,81,0.15);
            border-left-color: #C8A951;
        }
        .sidebar .nav-link i { width: 24px; text-align: center; }
        .sidebar .nav-link span { transition: opacity .2s; }
        .sidebar .toggle-btn {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            padding: 8px;
        }
        .sidebar .toggle-btn button {
            background: rgba(255,255,255,0.1);
            border: none;
            color: rgba(255,255,255,0.6);
            border-radius: 8px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all .2s;
            font-size: 16px;
        }
        .sidebar .toggle-btn button:hover { background: rgba(200,169,81,0.3); color: #fff; }

        /* Sidebar réduite */
        body.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed); }
        body.sidebar-collapsed .sidebar .brand img { width: 36px; }
        body.sidebar-collapsed .sidebar .brand h5 { opacity: 0; height: 0; margin: 0; overflow: hidden; }
        body.sidebar-collapsed .sidebar .nav-link { padding: 10px 0; text-align: center; border-left: none; }
        body.sidebar-collapsed .sidebar .nav-link span { opacity: 0; width: 0; display: inline-block; }
        body.sidebar-collapsed .sidebar .nav-link i { width: 100%; font-size: 18px; }
        body.sidebar-collapsed .sidebar .nav-link .badge { display: none; }
        body.sidebar-collapsed .sidebar hr { margin: 5px 10px; }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed); }
        body.sidebar-collapsed .sidebar .toggle-btn button i::before { content: "\F285"; }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px 30px;
            transition: margin-left .3s ease;
        }
        .topbar {
            background: #fff;
            margin: -20px -30px 20px;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid;
        }
        .stat-card.green { border-left-color: #C8A951; }
        .stat-card.blue { border-left-color: #5D4E37; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card.red { border-left-color: #dc3545; }
        .badge-validee { background: #C8A951; }
        .badge-refusee { background: #dc3545; }
        .badge-en_attente { background: #fd7e14; }
        .badge-annulee { background: #6c757d; }
        .table th { background: #f8f9fa; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Recherche globale */
        .search-global { position: relative; }
        .search-global input {
            width: 320px;
            border-radius: 20px;
            padding-left: 36px;
            border: 2px solid #e0e0e0;
            transition: all .3s;
            font-size: 13px;
            height: 38px;
        }
        .search-global input:focus {
            border-color: #C8A951;
            box-shadow: 0 0 0 3px rgba(40,167,69,0.15);
            width: 380px;
        }
        .search-global .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 14px;
            z-index: 5;
        }
        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            margin-top: 5px;
            z-index: 1000;
            max-height: 420px;
            overflow-y: auto;
            display: none;
        }
        .search-dropdown.show { display: block; }
        .search-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #f5f5f5;
            transition: background .15s;
        }
        .search-item:hover { background: #f0faf0; color: #333; }
        .search-item .si-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 15px;
            flex-shrink: 0;
        }
        .search-item .si-icon.mutation { background: #fdf6e3; color: #C8A951; }
        .search-item .si-icon.parcelle { background: #eee8d5; color: #5D4E37; }
        .search-item .si-icon.proprietaire { background: #fff3e0; color: #e67e22; }
        .search-item .si-icon.projet { background: #f3e5f5; color: #8e44ad; }
        .search-item .si-info { flex: 1; min-width: 0; }
        .search-item .si-titre { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .search-item .si-sub { font-size: 11px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .search-footer {
            padding: 10px 15px;
            text-align: center;
            font-size: 12px;
            background: #fafafa;
            border-radius: 0 0 10px 10px;
        }
        .search-footer a { color: #C8A951; font-weight: 600; }
        .search-loading { padding: 20px; text-align: center; color: #999; font-size: 13px; }
        .search-empty { padding: 20px; text-align: center; color: #999; font-size: 13px; }
        .search-kbd { font-size: 10px; color: #bbb; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); }
        .search-kbd kbd { background: #f0f0f0; border: 1px solid #ddd; border-radius: 3px; padding: 1px 5px; font-size: 10px; }

        /* ===== TOAST SYSTEM ===== */
        #toast-container {
            position: fixed; top: 20px; right: 20px;
            z-index: 9999; display: flex; flex-direction: column; gap: 10px;
            pointer-events: none;
        }
        .app-toast {
            pointer-events: all;
            display: flex; align-items: flex-start; gap: 12px;
            min-width: 300px; max-width: 420px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.08);
            padding: 14px 16px 12px;
            border-left: 4px solid #ccc;
            animation: toastIn .3s cubic-bezier(.22,1,.36,1) both;
            position: relative; overflow: hidden;
        }
        .app-toast.removing { animation: toastOut .3s ease forwards; }
        .app-toast-icon {
            font-size: 18px; flex-shrink: 0; margin-top: 1px;
        }
        .app-toast-body { flex: 1; }
        .app-toast-title { font-weight: 700; font-size: 13px; margin-bottom: 2px; }
        .app-toast-msg { font-size: 13px; color: #374151; line-height: 1.4; }
        .app-toast-close {
            background: none; border: none; cursor: pointer;
            color: #9ca3af; font-size: 14px; padding: 0; flex-shrink: 0;
            line-height: 1; margin-top: 1px;
        }
        .app-toast-close:hover { color: #374151; }
        .app-toast-bar {
            position: absolute; bottom: 0; left: 0; height: 3px;
            border-radius: 0 0 0 8px;
            animation: toastBar linear forwards;
        }
        /* Types */
        .app-toast.success { border-left-color: #16a34a; }
        .app-toast.success .app-toast-icon { color: #16a34a; }
        .app-toast.success .app-toast-title { color: #14532d; }
        .app-toast.success .app-toast-bar { background: #16a34a; }
        .app-toast.danger { border-left-color: #dc2626; }
        .app-toast.danger .app-toast-icon { color: #dc2626; }
        .app-toast.danger .app-toast-title { color: #7f1d1d; }
        .app-toast.danger .app-toast-bar { background: #dc2626; }
        .app-toast.warning { border-left-color: #d97706; }
        .app-toast.warning .app-toast-icon { color: #d97706; }
        .app-toast.warning .app-toast-title { color: #78350f; }
        .app-toast.warning .app-toast-bar { background: #d97706; }
        .app-toast.info { border-left-color: #2563eb; }
        .app-toast.info .app-toast-icon { color: #2563eb; }
        .app-toast.info .app-toast-title { color: #1e3a8a; }
        .app-toast.info .app-toast-bar { background: #2563eb; }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(60px) scale(.96); }
            to   { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0); max-height: 200px; margin-bottom: 0; }
            to   { opacity: 0; transform: translateX(60px); max-height: 0; padding: 0; margin: 0; }
        }
        @keyframes toastBar {
            from { width: 100%; }
            to   { width: 0%; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <img src="{{ asset('images/dgid-logo.png') }}" alt="DGID">
            <h5>Gestion des Mutations</h5>
        </div>
        <nav class="mt-3">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Tableau de bord">
                <i class="bi bi-grid-1x2-fill"></i> <span>Tableau de bord</span>
            </a>
            <a href="{{ route('recherche') }}" class="nav-link {{ request()->routeIs('recherche') ? 'active' : '' }}" title="Recherche">
                <i class="bi bi-search"></i> <span>Recherche</span>
            </a>
            <a href="{{ route('imports.index') }}" class="nav-link {{ request()->routeIs('imports.*') ? 'active' : '' }}" title="Imports Excel">
                <i class="bi bi-file-earmark-excel"></i> <span>Imports Excel</span>
            </a>
            <a href="{{ route('parcelles.index') }}" class="nav-link {{ request()->routeIs('parcelles.*') ? 'active' : '' }}" title="Parcelles">
                <i class="bi bi-geo-alt-fill"></i> <span>Parcelles</span>
            </a>
            <a href="{{ route('mutations.index') }}" class="nav-link {{ request()->routeIs('mutations.*') ? 'active' : '' }}" title="Mutations">
                <i class="bi bi-arrow-left-right"></i> <span>Mutations</span>
            </a>
            @if(auth()->user()->hasAnyRole(['admin', 'gestionnaire']))
            <a href="{{ route('annulations.index') }}" class="nav-link {{ request()->routeIs('annulations.*') ? 'active' : '' }}" title="Annulations">
                <i class="bi bi-x-circle"></i> <span>Annulations</span>
                @php $pendingCount = \App\Models\MutationAnnulation::where('statut', 'en_attente')->count(); @endphp
                @if($pendingCount > 0)
                    <span class="badge bg-danger ms-2">{{ $pendingCount }}</span>
                @endif
            </a>
            @endif
            <a href="{{ route('rapports.index') }}" class="nav-link {{ request()->routeIs('rapports.*') ? 'active' : '' }}" title="Rapports">
                <i class="bi bi-file-earmark-bar-graph"></i> <span>Rapports</span>
            </a>
            <a href="{{ route('scanner') }}" class="nav-link {{ request()->routeIs('scanner') ? 'active' : '' }}" title="Scanner QR">
                <i class="bi bi-qr-code-scan"></i> <span>Scanner QR</span>
            </a>
            <hr class="mx-3" style="border-color: rgba(255,255,255,0.2);">
            <a href="{{ route('projets.index') }}" class="nav-link {{ request()->routeIs('projets.*') && !request()->routeIs('projets.carte') ? 'active' : '' }}" title="Projets">
                <i class="bi bi-building"></i> <span>Projets</span>
            </a>
            <a href="{{ route('projets.carte') }}" class="nav-link {{ request()->routeIs('projets.carte') ? 'active' : '' }}" title="Carte">
                <i class="bi bi-map"></i> <span>Carte</span>
            </a>
            <a href="{{ route('communes.index') }}" class="nav-link {{ request()->routeIs('communes.*') ? 'active' : '' }}" title="Communes">
                <i class="bi bi-pin-map"></i> <span>Communes</span>
            </a>
            @if(auth()->user()->hasAnyRole(['admin', 'gestionnaire']))
            <a href="{{ route('templates.index') }}" class="nav-link {{ request()->routeIs('templates.*') ? 'active' : '' }}" title="Templates">
                <i class="bi bi-file-earmark-text"></i> <span>Templates</span>
            </a>
            @endif
            @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('admin.export-import') }}" class="nav-link {{ request()->routeIs('admin.export-import') ? 'active' : '' }}" title="Export / Import">
                <i class="bi bi-database-gear"></i> <span>Export / Import</span>
            </a>
            @endif
        </nav>
        <div class="toggle-btn">
            <button onclick="toggleSidebar()" id="toggleBtn" title="Réduire le menu">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0">@yield('title', 'Tableau de bord')</h5>

            <!-- Barre de recherche globale -->
            <div class="search-global" id="searchGlobal">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Rechercher lot, propriétaire, CNI, projet..." autocomplete="off">
                <span class="search-kbd"><kbd>Ctrl</kbd>+<kbd>K</kbd></span>
                <div class="search-dropdown" id="searchDropdown">
                    <div class="search-loading" id="searchLoading" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-success me-2"></div>
                        Recherche en cours...
                    </div>
                    <div class="search-empty" id="searchEmpty" style="display:none;">
                        <i class="bi bi-search me-1"></i> Aucun résultat trouvé
                    </div>
                    <div id="searchResults"></div>
                    <div class="search-footer" id="searchFooter" style="display:none;">
                        <a href="#" id="searchViewAll">
                            <i class="bi bi-arrow-right-circle"></i> Voir tous les résultats
                        </a>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="text-muted">{{ auth()->user()->name }}</span>
                <span class="badge" style="background:#C8A951;">{{ auth()->user()->roles->first()?->name ?? 'utilisateur' }}</span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toggle sidebar -->
    <script>
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-collapsed');
        const btn = document.getElementById('toggleBtn');
        const icon = btn.querySelector('i');
        if (document.body.classList.contains('sidebar-collapsed')) {
            icon.className = 'bi bi-chevron-double-right';
            btn.title = 'Agrandir le menu';
            localStorage.setItem('sidebar-collapsed', '1');
        } else {
            icon.className = 'bi bi-chevron-double-left';
            btn.title = 'Réduire le menu';
            localStorage.setItem('sidebar-collapsed', '0');
        }
    }
    // Restaurer l'état
    if (localStorage.getItem('sidebar-collapsed') === '1') {
        document.body.classList.add('sidebar-collapsed');
        document.getElementById('toggleBtn').querySelector('i').className = 'bi bi-chevron-double-right';
    }
    </script>

    <!-- Recherche globale JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('searchInput');
        const dropdown = document.getElementById('searchDropdown');
        const results = document.getElementById('searchResults');
        const loading = document.getElementById('searchLoading');
        const empty = document.getElementById('searchEmpty');
        const footer = document.getElementById('searchFooter');
        const viewAll = document.getElementById('searchViewAll');

        let debounceTimer = null;
        let currentQuery = '';

        // Raccourci clavier Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
                input.select();
            }
            if (e.key === 'Escape') {
                dropdown.classList.remove('show');
                input.blur();
            }
        });

        // Navigation clavier dans les résultats
        input.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.search-item');
            const active = dropdown.querySelector('.search-item.active');
            let idx = Array.from(items).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                idx = (idx + 1) % items.length;
                items[idx]?.classList.add('active');
                items[idx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (active) active.classList.remove('active');
                idx = idx <= 0 ? items.length - 1 : idx - 1;
                items[idx]?.classList.add('active');
                items[idx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) {
                    window.location.href = active.getAttribute('href');
                } else if (currentQuery.length >= 1) {
                    window.location.href = '/recherche?q=' + encodeURIComponent(currentQuery);
                }
            }
        });

        input.addEventListener('input', function() {
            currentQuery = this.value.trim();
            clearTimeout(debounceTimer);

            if (currentQuery.length < 2) {
                dropdown.classList.remove('show');
                return;
            }

            debounceTimer = setTimeout(() => fetchResults(currentQuery), 250);
        });

        input.addEventListener('focus', function() {
            if (currentQuery.length >= 2 && results.children.length > 0) {
                dropdown.classList.add('show');
            }
        });

        // Fermer au clic à l'extérieur
        document.addEventListener('click', function(e) {
            if (!document.getElementById('searchGlobal').contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        function fetchResults(q) {
            loading.style.display = 'block';
            empty.style.display = 'none';
            results.innerHTML = '';
            footer.style.display = 'none';
            dropdown.classList.add('show');

            fetch('/recherche/rapide?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                results.innerHTML = '';

                if (data.resultats.length === 0) {
                    empty.style.display = 'block';
                    footer.style.display = 'block';
                    viewAll.href = '/recherche?q=' + encodeURIComponent(q);
                    return;
                }

                empty.style.display = 'none';

                data.resultats.forEach(item => {
                    const a = document.createElement('a');
                    a.className = 'search-item';
                    a.href = item.url;

                    let badgeHtml = '';
                    if (item.badge) {
                        const badgeColors = {
                            'validee': '#28a745',
                            'refusee': '#dc3545',
                            'en_attente': '#fd7e14',
                            'annulee': '#6c757d'
                        };
                        const color = badgeColors[item.badge] || '#6c757d';
                        badgeHtml = `<span style="background:${color};color:#fff;padding:2px 8px;border-radius:10px;font-size:10px;margin-left:8px;">${item.badge}</span>`;
                    }

                    a.innerHTML = `
                        <div class="si-icon ${item.type}"><i class="bi ${item.icon}"></i></div>
                        <div class="si-info">
                            <div class="si-titre">${item.titre}${badgeHtml}</div>
                            <div class="si-sub">${item.sous_titre}</div>
                        </div>
                    `;
                    results.appendChild(a);
                });

                footer.style.display = 'block';
                viewAll.href = '/recherche?q=' + encodeURIComponent(q);
            })
            .catch(() => {
                loading.style.display = 'none';
                empty.style.display = 'block';
                empty.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Erreur de connexion';
            });
        }
    });
    </script>

    @stack('scripts')

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
            <div class="modal-content" style="border-radius:18px; border:none; box-shadow:0 24px 64px rgba(0,0,0,0.18); overflow:hidden;">
                <div class="modal-body p-4 text-center">
                    <div id="confirmIconWrap" style="font-size:42px; margin-bottom:12px;"></div>
                    <p id="confirmMessage" style="font-size:14.5px; color:#374151; margin-bottom:0; line-height:1.5;"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px; min-width:100px;">Annuler</button>
                    <button type="button" id="confirmOkBtn" style="border-radius:10px; min-width:120px;"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container"></div>
    <script>
    // ===== CONFIRM MODAL =====
    window.showConfirm = function(msg, onOk, { label='Confirmer', type='primary', icon='bi-question-circle-fill', iconColor='#C8A951' } = {}) {
        document.getElementById('confirmMessage').innerHTML = msg;
        document.getElementById('confirmIconWrap').innerHTML = `<i class="bi ${icon}" style="color:${iconColor};"></i>`;
        const okBtn = document.getElementById('confirmOkBtn');
        const newBtn = okBtn.cloneNode(false);
        newBtn.id = 'confirmOkBtn';
        newBtn.className = `btn btn-${type}`;
        newBtn.style.cssText = okBtn.style.cssText;
        newBtn.textContent = label;
        okBtn.parentNode.replaceChild(newBtn, okBtn);
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        newBtn.addEventListener('click', () => { modal.hide(); onOk(); }, { once: true });
        modal.show();
    };
    // Intercept forms with data-confirm attribute
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.dataset.confirm || form._confirmed) { form._confirmed = false; return; }
        e.preventDefault();
        const type = form.dataset.confirmType || 'primary';
        const label = form.dataset.confirmLabel || 'Confirmer';
        const icon = type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-question-circle-fill';
        const iconColor = type === 'danger' ? '#dc2626' : '#C8A951';
        showConfirm(form.dataset.confirm, () => { form._confirmed = true; form.requestSubmit(); }, { label, type, icon, iconColor });
    });

    const _toastIcons = { success:'bi-check-circle-fill', danger:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
    const _toastTitles = { success:'Succès', danger:'Erreur', warning:'Attention', info:'Info' };
    window.showToast = function(msg, type = 'info', duration = 4500) {
        const c = document.getElementById('toast-container');
        const t = document.createElement('div');
        t.className = 'app-toast ' + type;
        t.innerHTML = `
            <i class="bi ${_toastIcons[type]||'bi-info-circle-fill'} app-toast-icon"></i>
            <div class="app-toast-body">
                <div class="app-toast-title">${_toastTitles[type]||type}</div>
                <div class="app-toast-msg">${msg}</div>
            </div>
            <button class="app-toast-close" onclick="this.closest('.app-toast').remove()"><i class="bi bi-x-lg"></i></button>
            <div class="app-toast-bar" style="animation-duration:${duration}ms"></div>`;
        c.appendChild(t);
        setTimeout(() => {
            t.classList.add('removing');
            t.addEventListener('animationend', () => t.remove(), { once: true });
        }, duration);
    };
    </script>
</body>
</html>
