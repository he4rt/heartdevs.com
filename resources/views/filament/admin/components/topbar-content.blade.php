<div class="flex items-center h-full">
    {{-- Brand Block: Fixed width to match sidebar & Acts as Sidebar Toggle --}}
    <button 
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        class="flex items-center gap-3 h-full px-4 border-r border-slate-200/70 dark:border-white/10 shrink-0 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors text-left" 
        style="width: var(--sidebar-width); margin-left: -24px;"
    >
        <div class="flex items-center gap-3 shrink-0">
            <x-heroicon-m-bars-3 class="w-6 h-6 text-slate-400 dark:text-slate-500" />
            
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 dark:bg-white/5 overflow-hidden">
                <svg
                    class="h-6 w-auto drop-shadow-[0_0_15px_rgba(120,43,241,0.3)] animate-heartbeat"
                    viewBox="0 0 600 513"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <defs>
                        <linearGradient id="topbarHeartGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#9b59f5;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#782bf1;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path
                        d="M445.237 0.00033551C424.91 -0.0347431 404.777 3.89304 385.996 11.5576C367.216 19.2221 350.159 30.4719 335.808 44.6594L153.391 224.398L116.915 188.45C111.983 183.761 108.048 178.15 105.341 171.946C102.633 165.741 101.207 159.067 101.145 152.314C101.084 145.56 102.388 138.862 104.983 132.611C107.577 126.359 111.409 120.68 116.255 115.904C121.101 111.128 126.864 107.352 133.207 104.795C139.55 102.239 146.347 100.953 153.2 101.014C160.052 101.074 166.825 102.48 173.12 105.148C179.416 107.816 185.109 111.694 189.867 116.555L262.856 44.6594C233.71 16.6424 194.537 1.07109 153.824 1.31914C113.11 1.56719 74.1349 17.6146 45.3431 45.9846C16.5513 74.3546 0.261527 112.762 0.0031216 152.886C-0.255283 193.01 15.5385 231.618 43.9626 260.346L153.391 368.189L511.948 14.8274C491.12 5.01981 468.32 -0.0474973 445.237 0.00033551Z"
                        fill="url(#topbarHeartGradient)"
                    />
                    <path
                        d="M584.9 86.7579L445.237 224.433L408.76 188.45L335.808 260.345L372.284 296.293L226.379 440.084L299.332 512.015L554.665 260.381C577.296 238.07 592.355 209.395 597.769 178.303C603.183 147.21 598.687 115.228 584.9 86.7579Z"
                        fill="url(#topbarHeartGradient)"
                    />
                </svg>
            </div>
        </div>
        <div class="flex flex-col min-w-0">
            <div class="text-[13px] font-bold text-slate-900 dark:text-white leading-tight truncate">He4rt Hub</div>
            <div class="text-[10px] text-slate-500 leading-tight uppercase tracking-wider font-semibold truncate">Admin Console</div>
        </div>
    </button>

    {{-- Pickers Block --}}
    <div class="flex items-center gap-4 px-6 h-full">
        @include('filament.admin.components.topbar-tenant-menu')
    </div>
</div>
