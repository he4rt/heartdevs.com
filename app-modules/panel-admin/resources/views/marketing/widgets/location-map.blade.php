@assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-geo@4.3.4/build/index.umd.min.js"></script>
@endassets

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">{{ __('panel-admin::location.map.heading') }}</x-slot>
        <x-slot name="description">{{ __('panel-admin::location.map.hint') }}</x-slot>

        <style>
            #geo-tt {
                position: fixed; pointer-events: none; z-index: 60; opacity: 0;
                transition: opacity .12s; transform: translate(-50%, calc(-100% - 12px));
                background: #1c1a22; border: 1px solid rgba(167, 139, 250, .28); border-radius: 9px;
                padding: 7px 11px; font-size: 12px; line-height: 1.42; white-space: nowrap;
                box-shadow: 0 12px 34px -12px rgba(0, 0, 0, .7);
            }
            #geo-tt .tt-title { color: #fff; font-weight: 400; font-size: 12.5px; }
            #geo-tt .tt-title b { font-weight: 700; }
            #geo-tt .tt-body { color: #d8d3e0; margin-top: 2px; font-weight: 400; }
            #geo-tt .tt-body .num { color: #c4b5fd; font-weight: 700; }
        </style>

        <div
            wire:ignore
            x-data="locationMap(@js($byName), {{ $total }})"
            x-init="$nextTick(() => render())"
            class="relative w-full"
            style="height: 560px;"
        >
            <canvas x-ref="canvas"></canvas>
        </div>

        <script type="application/json" id="location-geojson">@json($geometry)</script>

        <script>
            function locationMap(byName, total) {
                return {
                    chart: null,

                    norm(value) {
                        return value
                            .normalize('NFD')
                            .replace(/[̀-ͯ]/g, '')
                            .toLowerCase()
                            .replace(/\s+/g, ' ')
                            .trim();
                    },

                    purple(t) {
                        t = Math.max(0, Math.min(1, t));
                        const c0 = [237, 233, 254], c1 = [124, 58, 237], c2 = [76, 29, 149];
                        let a, b, f;
                        if (t < 0.6) { a = c0; b = c1; f = t / 0.6; }
                        else { a = c1; b = c2; f = (t - 0.6) / 0.4; }
                        const ch = (i) => Math.round(a[i] + (b[i] - a[i]) * f);
                        return `rgb(${ch(0)},${ch(1)},${ch(2)})`;
                    },

                    tooltip(context) {
                        const { chart, tooltip } = context;
                        let el = document.getElementById('geo-tt');
                        if (!el) { el = document.createElement('div'); el.id = 'geo-tt'; document.body.appendChild(el); }
                        if (tooltip.opacity === 0) { el.style.opacity = '0'; return; }

                        const dp = tooltip.dataPoints && tooltip.dataPoints[0];
                        if (!dp || !dp.raw || !dp.raw.feature) { el.style.opacity = '0'; return; }

                        const p = dp.raw.feature.properties;
                        const v = dp.raw.value || 0;
                        const pct = total > 0 ? (v / total * 100).toFixed(1).replace('.', ',') : '0,0';
                        el.innerHTML =
                            `<div class="tt-title"><b>${p.name}</b> <span>(${p.uf})</span></div>` +
                            `<div class="tt-body"><span class="num">${v.toLocaleString('pt-BR')}</span> membros · ${pct}%</div>`;

                        const rect = chart.canvas.getBoundingClientRect();
                        el.style.opacity = '1';
                        el.style.left = (rect.left + tooltip.caretX) + 'px';
                        el.style.top = (rect.top + tooltip.caretY) + 'px';
                    },

                    render() {
                        const geojson = JSON.parse(document.getElementById('location-geojson').textContent);
                        const features = geojson.features;
                        const dark = document.documentElement.classList.contains('dark');
                        const self = this;

                        const ufLabels = {
                            id: 'ufLabels',
                            afterDatasetsDraw(chart) {
                                const meta = chart.getDatasetMeta(0);
                                const g = chart.ctx;
                                g.save();
                                g.textAlign = 'center';
                                g.textBaseline = 'middle';
                                g.font = '700 10px Inter, ui-sans-serif, system-ui, sans-serif';
                                g.fillStyle = '#2d2a36';
                                meta.data.forEach((element, i) => {
                                    const raw = chart.data.datasets[0].data[i];
                                    if (!raw || !raw.feature || !element.getCenterPoint) return;
                                    let point;
                                    try { point = element.getCenterPoint(); } catch (e) { return; }
                                    if (!point || !isFinite(point.x) || !isFinite(point.y)) return;
                                    g.fillText(raw.feature.properties.uf, point.x, point.y);
                                });
                                g.restore();
                            },
                        };

                        if (this.chart) this.chart.destroy();

                        this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                            type: 'choropleth',
                            plugins: [ufLabels],
                            data: {
                                labels: features.map((f) => f.properties.name),
                                datasets: [{
                                    outline: features,
                                    borderColor: dark ? 'rgba(24,24,27,.85)' : '#ffffff',
                                    borderWidth: 0.6,
                                    hoverBorderColor: '#a78bfa',
                                    hoverBorderWidth: 2.5,
                                    data: features.map((f) => ({
                                        feature: f,
                                        value: byName[self.norm(f.properties.name)] || 0,
                                    })),
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'nearest', intersect: true },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { enabled: false, external: (ctx) => self.tooltip(ctx) },
                                },
                                scales: {
                                    projection: { axis: 'x', projection: 'mercator' },
                                    color: {
                                        axis: 'x',
                                        min: 0,
                                        interpolate: (t) => self.purple(t),
                                        missing: dark ? '#2a2733' : '#ece9f1',
                                        legend: { position: 'bottom-right', align: 'bottom' },
                                    },
                                },
                            },
                        });

                        requestAnimationFrame(() => this.chart && this.chart.resize());
                        setTimeout(() => this.chart && this.chart.resize(), 150);
                    },
                };
            }
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
