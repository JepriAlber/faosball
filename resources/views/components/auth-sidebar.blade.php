<div class="auth-panel">

    {{-- Pattern dekoratif --}}
    <div class="auth-panel-decoration">
        <div class="auth-panel-blob-tl"></div>
        <div class="auth-panel-blob-br"></div>
        <div class="auth-panel-blob-center"></div>
    </div>

    <div class="auth-panel-content">

        {{-- Logo Icon --}}
        <div class="auth-panel-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                class="text-white">
                <circle cx="12" cy="12" r="10" />
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                <path d="M2 12h20" />
            </svg>
        </div>

        {{-- Nama Aplikasi --}}
        <h2 class="auth-panel-title">FAoSBall</h2>
        <p class="auth-panel-tagline">Football Academy Operating System</p>
        <p class="auth-panel-description">
            Platform manajemen akademi sepak bola yang terintegrasi — dari pemain hingga operasional, semua dalam
            satu sistem.
        </p>

        {{-- Stats --}}
        <div class="auth-panel-stats">
            <div class="auth-panel-stat">
                <p class="auth-panel-stat-value">{{ $totalActivePlayers }}</p>
                <p class="auth-panel-stat-label">Pemain</p>
            </div>
            <div class="auth-panel-stat">
                <p class="auth-panel-stat-value">{{ $totalActiveAcademies }}</p>
                <p class="auth-panel-stat-label">Akademi</p>
            </div>
            <div class="auth-panel-stat">
                <p class="auth-panel-stat-value">100%</p>
                <p class="auth-panel-stat-label">Aman</p>
            </div>
        </div>

    </div>
</div>
