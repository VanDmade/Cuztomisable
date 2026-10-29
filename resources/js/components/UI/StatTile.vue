<template>
    <component :is="to ? 'router-link' : 'div'" :to="to" class="cz-stat-tile" :class="{ 'cz-stat-tile--link': to }">
        <div class="cz-stat-tile__label">{{ label }}</div>
        <div class="cz-stat-tile__value">{{ display }}</div>
        <div class="cz-stat-tile__foot">
            <!-- The change carries an arrow and words, never colour alone -->
            <span v-if="change !== null" class="cz-stat-tile__delta" :class="deltaClass">
                <span class="material-icons" aria-hidden="true">{{ change > 0 ? 'arrow_upward' : change < 0 ? 'arrow_downward' : 'remove' }}</span>
                {{ changeText }}
            </span>
            <span v-if="change !== null" class="notes">{{ deltaLabel }}</span>
            <span v-else-if="note" class="notes">{{ note }}</span>
            <svg v-if="trend.length > 1" class="cz-stat-tile__spark" :viewBox="`0 0 ${SPARK_W} ${SPARK_H}`" aria-hidden="true">
                <polyline :points="sparkPoints" class="cz-stat-tile__spark-line" />
                <circle :cx="sparkX(trend.length - 1)" :cy="sparkY(trend[trend.length - 1])" r="3" class="cz-stat-tile__spark-dot" />
            </svg>
        </div>
    </component>
</template>
<script>
// A headline number: label, value, the change against the previous period (with an arrow and text, coloured by
// whether that direction is good) and an optional sparkline of recent periods with the current one marked.
// Pass `to` to make the whole tile a link into the area it summarises.
const SPARK_W = 72;
const SPARK_H = 22;

export default {
    data: function() {
        return { SPARK_W, SPARK_H };
    },
    computed: {
        display: function() {
            // Text that isn't a number (e.g. "10.9s") shows as it is
            if (typeof this.value === 'string' && this.value.trim() !== '' && isNaN(Number(this.value))) {
                return this.value;
            }
            const value = Number(this.value) || 0;
            if (value >= 1000000) {
                return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            }
            if (value >= 10000) {
                return (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
            }
            return value.toLocaleString();
        },
        change: function() {
            return this.previous === null || this.previous === undefined ? null : (Number(this.value) || 0) - Number(this.previous);
        },
        changeText: function() {
            if (this.change === 0) {
                return 'No change';
            }
            const sign = this.change > 0 ? '+' : '−';
            const amount = Math.abs(this.change).toLocaleString();
            // A percentage only means something when there was something to compare against
            const percent = Number(this.previous) > 0 ? ` (${sign}${Math.round(Math.abs(this.change) / Number(this.previous) * 100)}%)` : '';
            return `${sign}${amount}${percent}`;
        },
        deltaClass: function() {
            if (!this.change) {
                return 'cz-stat-tile__delta--flat';
            }
            return (this.change > 0) === this.upIsGood ? 'cz-stat-tile__delta--good' : 'cz-stat-tile__delta--bad';
        },
        sparkMax: function() {
            return Math.max(1, ...this.trend.map(Number));
        },
        sparkPoints: function() {
            return this.trend.map((value, i) => `${this.sparkX(i)},${this.sparkY(value)}`).join(' ');
        },
    },
    methods: {
        sparkX: function(i) {
            return 3 + (i / Math.max(1, this.trend.length - 1)) * (SPARK_W - 6);
        },
        sparkY: function(value) {
            return SPARK_H - 3 - (Number(value) / this.sparkMax) * (SPARK_H - 6);
        },
    },
    props: {
        label: { type: String, required: true },
        // A number (compacted for display), or ready-made text such as "10.9s"
        value: { type: [Number, String], default: 0 },
        // The previous period's value - shows the change when given
        previous: { type: [Number, String], default: null },
        deltaLabel: { type: String, default: 'vs last month' },
        // false when a rise is bad news (e.g. errors)
        upIsGood: { type: Boolean, default: true },
        // Shown under the value when there's no change to show
        note: { type: String, default: null },
        trend: { type: Array, default: () => [] },
        to: { type: [String, Object], default: null },
    },
}
</script>
<style scoped>
    .cz-stat-tile { display: flex; flex-direction: column; gap: 2px; height: 100%; padding: 16px 18px; border: 1px solid var(--color-border); border-radius: 12px; background: var(--color-surface, #fff); color: inherit; text-decoration: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .cz-stat-tile--link:hover, .cz-stat-tile--link:focus-visible { border-color: var(--color-primary); box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06); }
    .cz-stat-tile__label { font-size: 13px; color: var(--color-muted); }
    .cz-stat-tile__value { font-size: 28px; font-weight: 600; line-height: 1.2; color: var(--color-text); }
    .cz-stat-tile__foot { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-top: auto; padding-top: 4px; font-size: 12px; }
    .cz-stat-tile__delta { display: inline-flex; align-items: center; gap: 2px; font-weight: 600; }
    .cz-stat-tile__delta .material-icons { font-size: 14px; }
    .cz-stat-tile__delta--good { color: #006300; }
    .cz-stat-tile__delta--bad { color: #b42318; }
    .cz-stat-tile__delta--flat { color: var(--color-muted); }
    .cz-stat-tile__spark { width: 72px; height: 22px; margin-left: auto; }
    .cz-stat-tile__spark-line { fill: none; stroke: #b9b8b1; stroke-width: 1.5; stroke-linejoin: round; stroke-linecap: round; }
    .cz-stat-tile__spark-dot { fill: var(--color-primary); stroke: #fff; stroke-width: 1.5; }
</style>
