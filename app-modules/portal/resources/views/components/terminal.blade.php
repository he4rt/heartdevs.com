@props (['stats' => []])

<div
    class="mx-auto flex w-full max-w-md flex-col lg:max-w-lg"
    x-data="{
        lines: [
            { type: 'cmd', text: '$ he4rt --init' },
            { type: 'info', text: 'Conectando ao servidor...' },
            { type: 'info', text: 'Carregando dados da comunidade...' },
            { type: 'success', text: '✓ Conexão estabelecida!' },
            { type: 'blank', text: '' },
            { type: 'cmd', text: '$ he4rt stats --community' },
            { type: 'header', text: '' },
            { type: 'header', text: ' He4rt Devs — Stats' },
            { type: 'header', text: '' },
            { type: 'blank', text: '' },
            { type: 'stat', text: '  👥 Membros:      {{ $stats['members'] ?? '0' }}' },
            { type: 'stat', text: '  💬 Mensagens:    {{ $stats['messages'] ?? '0' }}' },
            { type: 'stat', text: '  ⚡ XP Total:     {{ $stats['xp'] ?? '0' }}' },
            { type: 'blank', text: '' },
            { type: 'success', text: 'Junte-se a nós → discord.gg/he4rt 💜' },
        ],
        visible: 0,
        cursor: true,
        init() {
            this.typeNextLine();
            setInterval(() => this.cursor = !this.cursor, 530);
        },
        typeNextLine() {
            if (this.visible >= this.lines.length) return;

            this.visible++;

            let delay = 80;
            const type = this.lines[this.visible - 1]?.type;
            if (type === 'cmd') delay = 400;
            else if (type === 'blank') delay = 100;
            else if (type === 'header') delay = 60;
            else if (type === 'success') delay = 300;

            setTimeout(() => this.typeNextLine(), delay);
        },
        colorClass(type) {
            return {
                'cmd': 'text-gray-100',
                'info': 'text-gray-400',
                'success': 'text-green-400',
                'header': 'text-purple-400',
                'stat': 'text-cyan-300',
                'highlight': 'text-yellow-300',
                'blank': '',
            }[type] || 'text-gray-300';
        }
    }"
>
    <div class="overflow-hidden rounded-lg bg-gray-900 shadow-xl">
        <div class="flex items-center gap-2 bg-gray-800 px-4 py-3">
            <div class="h-3 w-3 rounded-full bg-red-500"></div>
            <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
            <div class="h-3 w-3 rounded-full bg-green-500"></div>
            <span class="ml-2 text-xs text-gray-500">he4rt@dev ~</span>
        </div>

        <div class="relative p-4 font-mono text-xs leading-tight sm:text-sm">
            <div class="pointer-events-none invisible flex w-full flex-col gap-0.5" aria-hidden="true">
                <template x-for="(line, i) in lines" :key="`ghost-${i}`">
                    <div :class="[line.type === 'blank' ? 'h-2' : '']">
                        <span x-text="line.text || ' '"></span>
                    </div>
                </template>
            </div>

            <div class="absolute inset-4 flex w-[calc(100%-2rem)] flex-col gap-0.5">
                <template x-for="(line, i) in lines.slice(0, visible)" :key="i">
                    <div
                        x-transition:enter="transition duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        :class="[colorClass(line.type), line.type === 'blank' ? 'h-2' : '']"
                    >
                        <span x-text="line.text"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
