<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuration — {{ config('app.name', 'Gestion Mutations') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); color: #0f172a; padding: 24px;
        }
        .card { background: #fff; width: 100%; max-width: 520px; border-radius: 16px; padding: 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .sub { color: #64748b; margin: 0 0 24px; font-size: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; }
        input[type=text], input[type=password] { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1;
            border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        .drivers { display: flex; gap: 12px; margin-bottom: 8px; }
        .driver { flex: 1; border: 2px solid #e2e8f0; border-radius: 10px; padding: 14px; cursor: pointer; text-align: center; }
        .driver.active { border-color: #2563eb; background: #eff6ff; }
        .driver strong { display: block; font-size: 15px; }
        .driver span { font-size: 12px; color: #64748b; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        .actions { display: flex; gap: 12px; margin-top: 24px; }
        button { border: none; border-radius: 8px; padding: 12px 16px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; flex: 1; }
        .btn-secondary { background: #e2e8f0; color: #0f172a; }
        .alert { padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-ok { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .hidden { display: none; }
        .hint { font-size: 12px; color: #94a3b8; margin-top: 6px; }
        .spinner { display:inline-block; width:14px; height:14px; border:2px solid #fff; border-top-color:transparent;
            border-radius:50%; animation: spin .6s linear infinite; vertical-align: middle; margin-right: 6px; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="card">
        <h1>Configuration de la base de données</h1>
        <p class="sub">Première utilisation : choisissez où l'application stocke ses données.</p>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error){{ $error }}<br>@endforeach
            </div>
        @endif
        <div id="testResult"></div>

        <form method="POST" action="{{ route('setup.store') }}" id="setupForm">
            @csrf
            <input type="hidden" name="driver" id="driver" value="{{ $defaults['driver'] }}">

            <div class="drivers">
                <div class="driver {{ $defaults['driver'] === 'mysql' ? 'active' : '' }}" data-driver="mysql">
                    <strong>MySQL</strong>
                    <span>Serveur existant (réseau)</span>
                </div>
                <div class="driver {{ $defaults['driver'] === 'sqlite' ? 'active' : '' }}" data-driver="sqlite">
                    <strong>SQLite</strong>
                    <span>Fichier local autonome</span>
                </div>
            </div>

            <div id="mysqlFields" class="{{ $defaults['driver'] === 'sqlite' ? 'hidden' : '' }}">
                <div class="row">
                    <div>
                        <label>Hôte</label>
                        <input type="text" name="host" value="{{ $defaults['host'] }}" placeholder="127.0.0.1">
                    </div>
                    <div style="max-width:120px">
                        <label>Port</label>
                        <input type="text" name="port" value="{{ $defaults['port'] }}" placeholder="3306">
                    </div>
                </div>
                <label>Nom de la base</label>
                <input type="text" name="database" value="{{ $defaults['database'] }}" placeholder="gestion_mutations">
                <div class="row">
                    <div>
                        <label>Utilisateur</label>
                        <input type="text" name="username" value="{{ $defaults['username'] }}" placeholder="root">
                    </div>
                    <div>
                        <label>Mot de passe</label>
                        <input type="password" name="password" value="{{ $defaults['password'] }}">
                    </div>
                </div>
                <p class="hint">La base doit exister sur le serveur MySQL et l'utilisateur doit pouvoir créer des tables.</p>
            </div>

            <div id="sqliteFields" class="{{ $defaults['driver'] === 'sqlite' ? '' : 'hidden' }}">
                <p class="hint">Un fichier <code>database.sqlite</code> sera créé automatiquement dans le dossier de données de l'application. Aucun serveur requis.</p>
            </div>

            <div class="actions">
                <button type="button" class="btn-secondary" id="testBtn">Tester la connexion</button>
                <button type="submit" class="btn-primary" id="submitBtn">Installer et démarrer</button>
            </div>
        </form>
    </div>

    <script>
        const driverInput = document.getElementById('driver');
        const mysqlFields = document.getElementById('mysqlFields');
        const sqliteFields = document.getElementById('sqliteFields');
        const result = document.getElementById('testResult');
        const token = document.querySelector('meta[name=csrf-token]').content;

        document.querySelectorAll('.driver').forEach(el => {
            el.addEventListener('click', () => {
                document.querySelectorAll('.driver').forEach(d => d.classList.remove('active'));
                el.classList.add('active');
                const driver = el.dataset.driver;
                driverInput.value = driver;
                mysqlFields.classList.toggle('hidden', driver !== 'mysql');
                sqliteFields.classList.toggle('hidden', driver === 'mysql');
                result.innerHTML = '';
            });
        });

        function formData() {
            const fd = new FormData(document.getElementById('setupForm'));
            return new URLSearchParams(fd);
        }

        document.getElementById('testBtn').addEventListener('click', async () => {
            result.innerHTML = '<div class="alert alert-ok"><span class="spinner"></span>Test en cours…</div>';
            try {
                const res = await fetch('{{ route('setup.test') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: formData(),
                });
                const json = await res.json();
                result.innerHTML = '<div class="alert ' + (json.ok ? 'alert-ok' : 'alert-error') + '">' + json.message + '</div>';
            } catch (e) {
                result.innerHTML = '<div class="alert alert-error">Erreur réseau : ' + e.message + '</div>';
            }
        });

        document.getElementById('setupForm').addEventListener('submit', () => {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>Installation…';
        });
    </script>
</body>
</html>
