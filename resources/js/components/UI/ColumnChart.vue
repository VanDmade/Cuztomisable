<template>
    <figure class="cz-column-chart" :class="{ 'cz-column-chart--loading': loading }">
        <figcaption v-if="title || $slots.actions" class="cz-column-chart__header">
            <div>
                <h6 class="cz-column-chart__title mb-0">{{ title }}</h6>
                <p v-if="subtitle" class="cz-column-chart__subtitle notes mb-0">{{ subtitle }}</p>
            </div>
            <div class="cz-column-chart__actions">
                <slot name="actions"></slot>
                <button v-if="tableToggle" type="button" class="cz-column-chart__toggle" @click="tableView = !tableView">
                    {{ tableView ? 'Chart' : 'Table' }}
                </button>
            </div>
        </figcaption>
        <!-- Two or more series always get a legend, so identity is never colour alone -->
        <ul v-if="series.length > 1 && !tableView" class="cz-column-chart__legend">
            <li v-for="item in series" :key="item.name">
                <span class="cz-column-chart__swatch" :style="{ background: item.color }"></span>{{ item.name }}
            </li>
        </ul>
        <div v-show="!tableView" ref="frame" class="cz-column-chart__frame" @pointerleave="active = null">
            <svg v-if="width > 0" :width="width" :height="height" role="img" :aria-label="ariaLabel">
                <!-- Hairline gridlines with round-number ticks -->
                <g v-for="tick in ticks" :key="'t' + tick">
                    <line :x1="padLeft" :x2="width - padRight" :y1="y(tick)" :y2="y(tick)"
                        :class="tick === 0 ? 'cz-column-chart__baseline' : 'cz-column-chart__grid'" />
                    <text :x="padLeft - 8" :y="y(tick)" class="cz-column-chart__tick" text-anchor="end" dominant-baseline="middle">{{ compact(tick) }}</text>
                </g>
                <g v-for="(label, i) in labels" :key="'c' + i">
                    <!-- The hovered month gets a faint band behind it -->
                    <rect v-if="active === i" :x="bandX(i)" :y="padTop" :width="band" :height="plotHeight" class="cz-column-chart__hover" />
                    <path v-for="segment in segments(i)" :key="segment.name" :d="segment.d" :fill="segment.color" />
                    <text v-if="showLabel(i)" :x="bandX(i) + band / 2" :y="y(total(i)) - 6" class="cz-column-chart__value" text-anchor="middle">{{ compact(total(i)) }}</text>
                    <text v-if="i % labelStep === (labels.length - 1) % labelStep" :x="bandX(i) + band / 2" :y="height - 6" class="cz-column-chart__tick" text-anchor="middle">{{ label }}</text>
                    <!-- The whole month's column is the hit target, bigger than the bar itself -->
                    <rect :x="bandX(i)" :y="padTop" :width="band" :height="plotHeight" fill="transparent" tabindex="0"
                        class="cz-column-chart__hit" :aria-label="readout(i)"
                        @pointerenter="active = i" @focus="active = i" @blur="active = null" />
                </g>
            </svg>
            <div v-if="active !== null" class="cz-column-chart__tooltip" :style="tooltipStyle">
                <div class="cz-column-chart__tooltip-label">{{ labels[active] }}</div>
                <div v-for="item in series" :key="item.name" class="cz-column-chart__tooltip-row">
                    <span class="cz-column-chart__key" :style="{ background: item.color }"></span>
                    <strong>{{ (item.values[active] ?? 0).toLocaleString() }}</strong>
                    <span v-if="series.length > 1 || item.name" class="notes">{{ item.name }}</span>
                </div>
                <div v-if="series.length > 1" class="cz-column-chart__tooltip-row">
                    <span class="cz-column-chart__key cz-column-chart__key--total"></span>
                    <strong>{{ total(active).toLocaleString() }}</strong><span class="notes">Total</span>
                </div>
            </div>
        </div>
        <!-- The same numbers as a table, for screen readers and anyone who'd rather read them -->
        <table v-if="tableView" class="cz-column-chart__table">
            <thead>
                <tr><th>{{ labelHeading }}</th><th v-for="item in series" :key="item.name">{{ item.name || 'Value' }}</th></tr>
            </thead>
            <tbody>
                <tr v-for="(label, i) in labels" :key="'r' + i">
                    <td>{{ label }}</td>
                    <td v-for="item in series" :key="item.name">{{ (item.values[i] ?? 0).toLocaleString() }}</td>
                </tr>
            </tbody>
        </table>
    </figure>
</template>
<script>
// A column chart for a value per period (e.g. per month): one series, or several stacked. Thin columns, rounded at
// the top and square at the baseline, a 2px surface gap between stacked parts, hairline grid, the latest value
// labelled, a hover/focus tooltip per period and a table view.
const BAR_MAX = 24;
const GAP = 2;
const RADIUS = 4;

export default {
    data: function() {
        return {
            width: 0,
            active: null,
            tableView: false,
            observer: null,
        };
    },
    mounted: function() {
        this.observer = new ResizeObserver(([entry]) => {
            this.width = Math.floor(entry.contentRect.width);
        });
        this.observer.observe(this.$refs.frame);
    },
    beforeUnmount: function() {
        this.observer?.disconnect();
    },
    watch: {
        // A different set of periods (e.g. a new range) - whatever was hovered may not exist any more
        labels: function() {
            this.active = null;
        },
    },
    computed: {
        padTop: function() { return 18; },
        padRight: function() { return 4; },
        padBottom: function() { return 24; },
        padLeft: function() { return 8 + Math.max(2, this.compact(this.max).length) * 7; },
        plotHeight: function() { return this.height - this.padTop - this.padBottom; },
        band: function() { return (this.width - this.padLeft - this.padRight) / Math.max(1, this.labels.length); },
        barWidth: function() { return Math.max(2, Math.min(BAR_MAX, this.band * 0.6)); },
        // Round, readable top of the scale, never 0 (so an empty chart still has an axis)
        max: function() {
            const top = Math.max(0, ...this.labels.map((label, i) => this.total(i)));
            if (top <= 4) {
                return 4;
            }
            const magnitude = Math.pow(10, Math.floor(Math.log10(top)));
            const step = [1, 2, 2.5, 5, 10].find((n) => n * magnitude >= top / 4) * magnitude;
            return Math.ceil(top / step) * step;
        },
        ticks: function() { return [0, this.max / 2, this.max]; },
        // Month labels thin out when they'd collide
        labelStep: function() { return Math.max(1, Math.ceil((this.labels.length * 34) / Math.max(1, this.width - this.padLeft))); },
        tooltipStyle: function() {
            const x = this.bandX(this.active) + this.band / 2;
            const flip = x > this.width * 0.6;
            return flip ? { right: (this.width - x + 12) + 'px', top: '8px' } : { left: (x + 12) + 'px', top: '8px' };
        },
        ariaLabel: function() {
            return (this.title || 'Chart') + ': ' + this.labels.map((label, i) => label + ' ' + this.total(i)).join(', ');
        },
    },
    methods: {
        total: function(i) {
            return this.series.reduce((sum, item) => sum + (Number(item.values[i]) || 0), 0);
        },
        y: function(value) {
            return this.padTop + this.plotHeight - (value / this.max) * this.plotHeight;
        },
        bandX: function(i) {
            return this.padLeft + i * this.band;
        },
        // The stacked parts of one column, bottom up - only the top part gets the rounded end
        segments: function(i) {
            const x = this.bandX(i) + (this.band - this.barWidth) / 2;
            const parts = this.series.map((item) => ({ name: item.name, color: item.color, value: Number(item.values[i]) || 0 })).filter((part) => part.value > 0);
            let base = 0;
            return parts.map((part, index) => {
                const top = index === parts.length - 1;
                const y0 = this.y(base) - (index > 0 ? GAP : 0);
                const y1 = this.y(base + part.value);
                base += part.value;
                const h = Math.max(1, y0 - y1);
                const r = top ? Math.min(RADIUS, h, this.barWidth / 2) : 0;
                const w = this.barWidth, yTop = y0 - h;
                const d = `M${x},${y0} V${yTop + r} Q${x},${yTop} ${x + r},${yTop} H${x + w - r} Q${x + w},${yTop} ${x + w},${yTop + r} V${y0} Z`;
                return { name: part.name, color: part.color, d };
            });
        },
        // Only the latest period's value is written on the chart; the axis, tooltip and table carry the rest
        showLabel: function(i) {
            return i === this.labels.length - 1 && this.total(i) > 0;
        },
        readout: function(i) {
            return this.labels[i] + ': ' + this.series.map((item) => (item.name ? item.name + ' ' : '') + (item.values[i] ?? 0)).join(', ');
        },
        compact: function(value) {
            if (value >= 1000000) {
                return (value / 1000000).toFixed(value % 1000000 ? 1 : 0) + 'M';
            }
            if (value >= 1000) {
                return (value / 1000).toFixed(value % 1000 ? 1 : 0) + 'K';
            }
            return String(Math.round(value * 10) / 10);
        },
    },
    props: {
        title: { type: String, default: null },
        subtitle: { type: String, default: null },
        // One label per column, e.g. ['Nov', 'Dec', "Jan '26"]
        labels: { type: Array, required: true },
        // [{ name, color, values: [] }] - one series, or several stacked bottom-up in this order
        series: { type: Array, required: true },
        height: { type: Number, default: 180 },
        labelHeading: { type: String, default: 'Month' },
        // Dims the chart while new numbers load, keeping the old ones on screen
        loading: { type: Boolean, default: false },
        // The "Table" link that swaps the chart for the same numbers as a table
        tableToggle: { type: Boolean, default: true },
    },
}
</script>
<style scoped>
    .cz-column-chart { margin: 0; transition: opacity 0.2s; }
    .cz-column-chart--loading { opacity: 0.5; }
    .cz-column-chart__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 8px; }
    .cz-column-chart__title { font-size: 15px; font-weight: 600; color: var(--color-text); }
    .cz-column-chart__actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .cz-column-chart__toggle { border: 0; background: none; padding: 0; font-size: 12px; color: var(--color-link); cursor: pointer; }
    .cz-column-chart__toggle:hover { text-decoration: underline; }
    .cz-column-chart__legend { display: flex; flex-wrap: wrap; gap: 14px; list-style: none; padding: 0; margin: 0 0 6px; font-size: 12px; color: var(--color-muted); }
    .cz-column-chart__legend li { display: inline-flex; align-items: center; gap: 6px; }
    .cz-column-chart__swatch { width: 10px; height: 10px; border-radius: 2px; }
    .cz-column-chart__frame { position: relative; width: 100%; }
    .cz-column-chart__grid { stroke: var(--color-border); stroke-width: 1; }
    .cz-column-chart__baseline { stroke: #c3c2b7; stroke-width: 1; }
    .cz-column-chart__tick { font-size: 11px; fill: var(--color-muted); font-variant-numeric: tabular-nums; }
    .cz-column-chart__value { font-size: 11px; font-weight: 600; fill: var(--color-text); }
    .cz-column-chart__hover { fill: rgba(0, 0, 0, 0.04); }
    .cz-column-chart__hit { outline: none; cursor: default; }
    .cz-column-chart__tooltip { position: absolute; z-index: 5; pointer-events: none; min-width: 120px; padding: 8px 10px; border-radius: 8px; background: #fff; border: 1px solid rgba(11, 11, 11, 0.1); box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1); font-size: 12px; }
    .cz-column-chart__tooltip-label { color: var(--color-muted); margin-bottom: 4px; }
    .cz-column-chart__tooltip-row { display: flex; align-items: center; gap: 6px; line-height: 1.6; }
    .cz-column-chart__tooltip-row strong { color: var(--color-text); font-variant-numeric: tabular-nums; }
    .cz-column-chart__key { width: 10px; height: 2px; border-radius: 1px; }
    .cz-column-chart__key--total { background: transparent; }
    .cz-column-chart__table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .cz-column-chart__table th, .cz-column-chart__table td { padding: 4px 8px; border-bottom: 1px solid var(--color-border); text-align: right; font-variant-numeric: tabular-nums; }
    .cz-column-chart__table th:first-child, .cz-column-chart__table td:first-child { text-align: left; }
</style>
