<template>
    <div ref="root" class="cz-form-input cz-picker"
        :class="{ 'form-floating': hasLabel, 'cz-no-label': !hasLabel, 'cz-picker--open': open }">
        <input
            ref="input"
            type="text"
            readonly
            :id="id"
            class="form-control cz-form-control cz-picker-input"
            :class="[{ 'is-invalid': errorList.length > 0, 'empty': !hasValue && !open }, inputClass]"
            :value="displayValue"
            :placeholder="placeholder"
            :disabled="disabled"
            aria-haspopup="dialog"
            :aria-expanded="open ? 'true' : 'false'"
            @click="toggle"
            @keydown.enter.prevent="toggle"
            @keydown.space.prevent="toggle"
            @keydown.down.prevent="openPicker"
            @keydown.esc="close(false)">
        <div class="cz-picker-actions">
            <button
                v-if="clearable && hasValue && !disabled && !readonly"
                type="button"
                class="cz-picker-action"
                tabindex="-1"
                aria-label="Clear"
                @click="clear">
                <span class="material-icons">close</span>
            </button>
            <button
                type="button"
                class="cz-picker-action"
                tabindex="-1"
                :disabled="disabled"
                :aria-label="'Open ' + mode + ' picker'"
                @click="toggle">
                <span class="material-icons">{{ icon }}</span>
            </button>
        </div>
        <label v-if="hasLabel" :for="id" class="cz-form-label">{{ label }}</label>
        <div v-if="open" ref="panel"
            class="cz-picker-panel"
            :class="{ 'cz-picker-panel--up': dropUp, 'cz-picker-panel--right': alignRight }"
            role="dialog"
            :aria-label="label || 'Choose ' + mode"
            @keydown.esc.stop="close(true)">
            <div class="cz-picker-body">
                <div v-if="hasDate" class="cz-picker-calendar">
                    <div class="cz-picker-header">
                        <button type="button" class="cz-picker-nav" :disabled="!canPrev" aria-label="Previous month" @click="shiftMonth(-1)">
                            <span class="material-icons">chevron_left</span>
                        </button>
                        <select v-model.number="viewMonth" class="cz-picker-select" aria-label="Month">
                            <option v-for="(name, i) in monthNames" :key="id+'-month-'+i" :value="i">{{ name }}</option>
                        </select>
                        <select v-model.number="viewYear" class="cz-picker-select" aria-label="Year">
                            <option v-for="year in years" :key="id+'-year-'+year" :value="year">{{ year }}</option>
                        </select>
                        <button type="button" class="cz-picker-nav" :disabled="!canNext" aria-label="Next month" @click="shiftMonth(1)">
                            <span class="material-icons">chevron_right</span>
                        </button>
                    </div>
                    <div class="cz-picker-weekdays">
                        <span v-for="(name, i) in weekdayNames" :key="id+'-weekday-'+i">{{ name }}</span>
                    </div>
                    <div class="cz-picker-days" role="grid" @keydown="onGridKeydown">
                        <button
                            v-for="day in days"
                            :key="day.iso"
                            type="button"
                            class="cz-picker-day"
                            :class="{ 'is-outside': day.outside, 'is-today': day.iso === todayIso, 'is-selected': day.iso === parts.date }"
                            :disabled="day.disabled"
                            :tabindex="day.iso === focusedDate ? 0 : -1"
                            :data-date="day.iso"
                            :aria-label="day.label"
                            :aria-pressed="day.iso === parts.date ? 'true' : 'false'"
                            @click="selectDate(day.iso)">{{ day.day }}</button>
                    </div>
                </div>
                <div v-if="hasTime" class="cz-picker-time">
                    <div class="cz-picker-time-display">{{ timeDisplay }}</div>
                    <div class="cz-picker-columns">
                        <div ref="hourColumn" class="cz-picker-column" role="listbox" aria-label="Hour">
                            <button
                                v-for="option in hourOptions"
                                :key="id+'-hour-'+option.value"
                                type="button"
                                class="cz-picker-option"
                                :class="{ 'is-selected': option.value === currentTime.hour }"
                                :disabled="option.disabled"
                                role="option"
                                :aria-selected="option.value === currentTime.hour ? 'true' : 'false'"
                                @click="selectTime({ hour: option.value })">{{ option.text }}</button>
                        </div>
                        <div ref="minuteColumn" class="cz-picker-column" role="listbox" aria-label="Minute">
                            <button
                                v-for="option in minuteOptions"
                                :key="id+'-minute-'+option.value"
                                type="button"
                                class="cz-picker-option"
                                :class="{ 'is-selected': option.value === currentTime.minute }"
                                :disabled="option.disabled"
                                role="option"
                                :aria-selected="option.value === currentTime.minute ? 'true' : 'false'"
                                @click="selectTime({ minute: option.value })">{{ option.text }}</button>
                        </div>
                        <div v-if="seconds" ref="secondColumn" class="cz-picker-column" role="listbox" aria-label="Second">
                            <button
                                v-for="option in secondOptions"
                                :key="id+'-second-'+option.value"
                                type="button"
                                class="cz-picker-option"
                                :class="{ 'is-selected': option.value === currentTime.second }"
                                :disabled="option.disabled"
                                role="option"
                                :aria-selected="option.value === currentTime.second ? 'true' : 'false'"
                                @click="selectTime({ second: option.value })">{{ option.text }}</button>
                        </div>
                        <div v-if="use12Hour" class="cz-picker-column cz-picker-column--meridiem" role="listbox" aria-label="AM or PM">
                            <button
                                v-for="meridiem in ['AM', 'PM']"
                                :key="id+'-meridiem-'+meridiem"
                                type="button"
                                class="cz-picker-option"
                                :class="{ 'is-selected': currentMeridiem === meridiem }"
                                role="option"
                                :aria-selected="currentMeridiem === meridiem ? 'true' : 'false'"
                                @click="selectMeridiem(meridiem)">{{ meridiem }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="cz-picker-footer">
                <button type="button" class="cz-picker-link" @click="setNow">{{ mode === 'date' ? 'Today' : 'Now' }}</button>
                <button v-if="clearable && hasValue" type="button" class="cz-picker-link" @click="clear">Clear</button>
                <button type="button" class="button button--primary button--small cz-picker-done" @click="close(true)">Done</button>
            </div>
        </div>
        <ul v-if="!hideDetails" class="form-errors cz-form-errors mb-2">
            <li v-for="(error, i) in errorList" :key="id+'-error-'+i" class="form-error cz-form-error">{{ error }}</li>
        </ul>
    </div>
</template>
<script>
// Shared engine behind cz-datepicker, cz-timepicker and cz-datetimepicker.
// Values are plain local wall-clock strings (no timezone conversion) so they round-trip
// cleanly through Laravel validation/casts:
//   date      -> 'YYYY-MM-DD'
//   time      -> 'HH:mm' (or 'HH:mm:ss' with the seconds prop)
//   datetime  -> 'YYYY-MM-DD HH:mm' (or 'YYYY-MM-DD HH:mm:ss')
// Because every value is zero-padded and fixed-width, min/max checks are plain string compares.
// min/max also accept 'now', which is re-read each time the picker opens.
const pad = (n) => String(n).padStart(2, '0');
const toIso = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
const fromDate = (date) => toIso(date.getFullYear(), date.getMonth(), date.getDate());
const toDate = (iso) => {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
};

export default {
    emits: ['update:modelValue', 'change'],
    data: function() {
        return {
            id: 'cz-picker_'+Math.random().toString(16).slice(2),
            errorList: [],
            open: false,
            dropUp: false,
            alignRight: false,
            viewYear: new Date().getFullYear(),
            viewMonth: new Date().getMonth(),
            focusedDate: null,
            now: new Date(),
        }
    },
    methods: {
        parse: function(value) {
            const out = { date: null, hour: null, minute: null, second: 0 };
            if (value == null || value === '') {
                return out;
            }
            const str = String(value).trim();
            const date = str.match(/(\d{4})-(\d{2})-(\d{2})/);
            if (date) {
                out.date = `${date[1]}-${date[2]}-${date[3]}`;
            }
            const time = str.match(/(?:^|[T\s])(\d{1,2}):(\d{2})(?::(\d{2}))?\s*([AaPp][Mm])?/);
            if (time) {
                let hour = Number(time[1]) % 24;
                if (time[4]) {
                    hour = (hour % 12) + (time[4].toUpperCase() === 'PM' ? 12 : 0);
                }
                out.hour = hour;
                out.minute = Number(time[2]);
                out.second = time[3] ? Number(time[3]) : 0;
            }
            return out;
        },
        build: function(date, hour, minute, second) {
            const time = `${pad(hour)}:${pad(minute)}` + (this.seconds ? `:${pad(second)}` : '');
            if (this.mode === 'date') return date;
            if (this.mode === 'time') return time;
            return `${date} ${time}`;
        },
        resolveBound: function(value) {
            if (value !== 'now') return value;
            const now = this.now;
            return this.build(fromDate(now), now.getHours(), now.getMinutes(), now.getSeconds());
        },
        normalizeBound: function(value, isMax) {
            const parts = this.parse(this.resolveBound(value));
            if (this.hasDate && !parts.date) return null;
            if (this.hasTime && parts.hour == null) {
                if (this.mode === 'time') return null;
                // A date-only bound on a datetime picker covers that whole day
                parts.hour = isMax ? 23 : 0;
                parts.minute = isMax ? 59 : 0;
                parts.second = isMax ? 59 : 0;
            }
            return this.build(parts.date, parts.hour, parts.minute, parts.second);
        },
        clamp: function(value) {
            if (this.minValue && value < this.minValue) return this.minValue;
            if (this.maxValue && value > this.maxValue) return this.maxValue;
            return value;
        },
        emitValue: function(value) {
            value = value === '' ? '' : this.clamp(value);
            this.errorList = [];
            this.$emit('update:modelValue', value);
            this.$emit('change', value);
        },
        isDateDisabled: function(iso) {
            return (this.minDate != null && iso < this.minDate) || (this.maxDate != null && iso > this.maxDate);
        },
        isRangeDisabled: function(start, end) {
            // Disabled when the whole [start, end] span falls outside min/max
            return (this.minValue != null && end < this.minValue) || (this.maxValue != null && start > this.maxValue);
        },
        toggle: function() {
            this.open ? this.close(false) : this.openPicker();
        },
        openPicker: function() {
            if (this.disabled || this.readonly || this.open) {
                return;
            }
            // Keeps 'now' bounds and start-at-now defaults current
            this.now = new Date();
            const anchor = this.parts.date ?? this.clampDate(this.todayIso);
            const anchorDate = toDate(anchor);
            this.viewYear = anchorDate.getFullYear();
            this.viewMonth = anchorDate.getMonth();
            this.focusedDate = anchor;
            this.open = true;
            document.addEventListener('mousedown', this.onOutside);
            document.addEventListener('focusin', this.onOutside);
            this.$nextTick(() => {
                this.position();
                this.scrollColumns();
                const target = this.hasDate
                    ? this.$refs.panel?.querySelector(`[data-date="${this.focusedDate}"]`)
                    : this.$refs.panel?.querySelector('.cz-picker-option.is-selected, .cz-picker-option:not(:disabled)');
                target?.focus();
            });
        },
        close: function(returnFocus) {
            if (!this.open) {
                return;
            }
            this.open = false;
            document.removeEventListener('mousedown', this.onOutside);
            document.removeEventListener('focusin', this.onOutside);
            if (returnFocus) {
                this.$refs.input?.focus();
            }
        },
        onOutside: function(event) {
            if (this.$refs.root && !this.$refs.root.contains(event.target)) {
                this.close(false);
            }
        },
        position: function() {
            const input = this.$refs.input;
            const panel = this.$refs.panel;
            if (!input || !panel) return;
            const rect = input.getBoundingClientRect();
            const below = window.innerHeight - rect.bottom;
            this.dropUp = below < panel.offsetHeight + 8 && rect.top > below;
            this.alignRight = rect.left + panel.offsetWidth > window.innerWidth - 8 && rect.right - panel.offsetWidth >= 8;
        },
        scrollColumns: function() {
            ['hourColumn', 'minuteColumn', 'secondColumn'].forEach((ref) => {
                const column = this.$refs[ref];
                const selected = column?.querySelector('.is-selected');
                if (column && selected) {
                    column.scrollTop = selected.offsetTop - (column.clientHeight / 2) + (selected.offsetHeight / 2);
                }
            });
        },
        clampDate: function(iso) {
            if (this.minDate && iso < this.minDate) return this.minDate;
            if (this.maxDate && iso > this.maxDate) return this.maxDate;
            return iso;
        },
        shiftMonth: function(delta) {
            const date = new Date(this.viewYear, this.viewMonth + delta, 1);
            this.viewYear = date.getFullYear();
            this.viewMonth = date.getMonth();
        },
        focusDate: function(iso) {
            iso = this.clampDate(iso);
            const date = toDate(iso);
            this.focusedDate = iso;
            this.viewYear = date.getFullYear();
            this.viewMonth = date.getMonth();
            this.$nextTick(() => {
                this.$refs.panel?.querySelector(`[data-date="${iso}"]`)?.focus();
            });
        },
        onGridKeydown: function(event) {
            const date = toDate(this.focusedDate ?? this.todayIso);
            const steps = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
            if (event.key in steps) {
                date.setDate(date.getDate() + steps[event.key]);
            } else if (event.key === 'PageUp' || event.key === 'PageDown') {
                const day = date.getDate();
                date.setDate(1);
                date.setMonth(date.getMonth() + (event.key === 'PageUp' ? -1 : 1));
                date.setDate(Math.min(day, new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()));
            } else if (event.key === 'Home') {
                date.setDate(date.getDate() - ((date.getDay() - this.weekStart + 7) % 7));
            } else if (event.key === 'End') {
                date.setDate(date.getDate() + (6 - ((date.getDay() - this.weekStart + 7) % 7)));
            } else {
                return;
            }
            event.preventDefault();
            this.focusDate(fromDate(date));
        },
        selectDate: function(iso) {
            this.focusedDate = iso;
            const date = toDate(iso);
            if (date.getMonth() !== this.viewMonth) {
                this.viewYear = date.getFullYear();
                this.viewMonth = date.getMonth();
            }
            const time = this.currentTime;
            this.emitValue(this.build(iso, time.hour, time.minute, time.second));
            if (this.mode === 'date') {
                this.close(true);
            }
        },
        selectTime: function(changes) {
            const time = { ...this.currentTime, ...changes };
            const date = this.parts.date ?? this.clampDate(this.todayIso);
            this.emitValue(this.build(date, time.hour, time.minute, time.second));
        },
        selectMeridiem: function(meridiem) {
            if (meridiem === this.currentMeridiem) return;
            this.selectTime({ hour: (this.currentTime.hour + 12) % 24 });
        },
        setNow: function() {
            const now = this.now = new Date();
            const step = Math.max(1, Number(this.minuteStep) || 1);
            const minute = Math.floor(now.getMinutes() / step) * step;
            this.focusedDate = this.clampDate(fromDate(now));
            this.emitValue(this.build(fromDate(now), now.getHours(), minute, this.seconds ? now.getSeconds() : 0));
            if (this.mode === 'date') {
                this.close(true);
            } else {
                this.$nextTick(this.scrollColumns);
            }
        },
        clear: function() {
            this.emitValue('');
            this.close(true);
        },
    },
    computed: {
        hasLabel: function() {
            return this.label != null && this.label !== '';
        },
        hasDate: function() {
            return this.mode !== 'time';
        },
        hasTime: function() {
            return this.mode !== 'date';
        },
        icon: function() {
            return { date: 'calendar_today', time: 'schedule', datetime: 'event' }[this.mode];
        },
        parts: function() {
            return this.parse(this.modelValue);
        },
        hasValue: function() {
            return this.hasDate ? this.parts.date != null : this.parts.hour != null;
        },
        defaultTimeParts: function() {
            if (this.startAtNow) {
                const step = Math.max(1, Number(this.minuteStep) || 1);
                return {
                    hour: this.now.getHours(),
                    minute: Math.floor(this.now.getMinutes() / step) * step,
                    second: this.now.getSeconds(),
                };
            }
            const parts = this.parse(this.defaultTime);
            return parts.hour == null ? { hour: 0, minute: 0, second: 0 } : parts;
        },
        currentTime: function() {
            const source = this.parts.hour == null ? this.defaultTimeParts : this.parts;
            return { hour: source.hour, minute: source.minute, second: this.seconds ? source.second : 0 };
        },
        currentMeridiem: function() {
            return this.currentTime.hour >= 12 ? 'PM' : 'AM';
        },
        minValue: function() {
            return this.min ? this.normalizeBound(this.min, false) : null;
        },
        maxValue: function() {
            return this.max ? this.normalizeBound(this.max, true) : null;
        },
        minDate: function() {
            return this.hasDate && this.minValue ? this.minValue.slice(0, 10) : null;
        },
        maxDate: function() {
            return this.hasDate && this.maxValue ? this.maxValue.slice(0, 10) : null;
        },
        todayIso: function() {
            return fromDate(this.now);
        },
        displayValue: function() {
            if (!this.hasValue) {
                return '';
            }

            const [y, m, d] = (this.parts.date ?? '2000-01-01').split('-').map(Number);
            const time = this.currentTime;
            const date = new Date(y, m - 1, d, time.hour, time.minute, time.second);

            if (this.displayFormat) {
                const hour12 = (time.hour % 12) || 12;
                const replacements = {
                    YYYY: String(y),
                    YY: String(y).slice(-2),
                    MM: pad(m),
                    M: String(m),
                    DD: pad(d),
                    D: String(d),
                    HH: pad(time.hour),
                    H: String(time.hour),
                    hh: pad(hour12),
                    h: String(hour12),
                    mm: pad(time.minute),
                    m: String(time.minute),
                    ss: pad(time.second),
                    s: String(time.second),
                    A: time.hour >= 12 ? 'PM' : 'AM',
                    a: time.hour >= 12 ? 'pm' : 'am',
                };

                return this.displayFormat.replace(
                    /YYYY|YY|MM|DD|HH|hh|mm|ss|M|D|H|h|m|s|A|a/g,
                    (token) => replacements[token]
                );
            }

            const options = {};
            if (this.hasDate) {
                Object.assign(options, { year: 'numeric', month: 'short', day: 'numeric' });
            }
            if (this.hasTime) {
                Object.assign(
                    options,
                    { hour: 'numeric', minute: '2-digit', hour12: this.use12Hour },
                    this.seconds ? { second: '2-digit' } : {}
                );
            }

            return new Intl.DateTimeFormat(
                this.locale,
                { ...options, ...this.displayOptions }
            ).format(date);
        },
        timeDisplay: function() {
            const time = this.currentTime;
            const hour = this.use12Hour ? ((time.hour % 12) || 12) : pad(time.hour);
            return `${hour}:${pad(time.minute)}` + (this.seconds ? `:${pad(time.second)}` : '') + (this.use12Hour ? ` ${this.currentMeridiem}` : '');
        },
        monthNames: function() {
            const format = new Intl.DateTimeFormat(this.locale, { month: 'long' });
            return Array.from({ length: 12 }, (_, i) => format.format(new Date(2000, i, 1)));
        },
        weekdayNames: function() {
            const format = new Intl.DateTimeFormat(this.locale, { weekday: 'short' });
            // 2023-01-01 was a Sunday
            return Array.from({ length: 7 }, (_, i) => format.format(new Date(2023, 0, 1 + ((i + this.weekStart) % 7))).slice(0, 2));
        },
        years: function() {
            const current = new Date().getFullYear();
            let start = this.minDate ? Number(this.minDate.slice(0, 4)) : current - this.yearRange[0];
            let end = this.maxDate ? Number(this.maxDate.slice(0, 4)) : current + this.yearRange[1];
            start = Math.min(start, this.viewYear);
            end = Math.max(end, this.viewYear);
            return Array.from({ length: end - start + 1 }, (_, i) => end - i);
        },
        days: function() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const offset = (first.getDay() - this.weekStart + 7) % 7;
            const labelFormat = new Intl.DateTimeFormat(this.locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            return Array.from({ length: 42 }, (_, i) => {
                const date = new Date(this.viewYear, this.viewMonth, 1 - offset + i);
                const iso = fromDate(date);
                return {
                    iso,
                    day: date.getDate(),
                    label: labelFormat.format(date),
                    outside: date.getMonth() !== this.viewMonth,
                    disabled: this.isDateDisabled(iso),
                };
            });
        },
        canPrev: function() {
            return !this.minDate || fromDate(new Date(this.viewYear, this.viewMonth, 0)) >= this.minDate;
        },
        canNext: function() {
            return !this.maxDate || fromDate(new Date(this.viewYear, this.viewMonth + 1, 1)) <= this.maxDate;
        },
        // Time bounds only apply on the selected day (datetime) or always (time)
        timeDate: function() {
            return this.parts.date ?? this.clampDate(this.todayIso);
        },
        hourOptions: function() {
            let hours = Array.from({ length: 24 }, (_, i) => i);
            if (this.use12Hour) {
                const base = this.currentMeridiem === 'PM' ? 12 : 0;
                hours = Array.from({ length: 12 }, (_, i) => base + i);
            }
            return hours.map((hour) => ({
                value: hour,
                text: this.use12Hour ? String((hour % 12) || 12) : pad(hour),
                disabled: this.isRangeDisabled(this.build(this.timeDate, hour, 0, 0), this.build(this.timeDate, hour, 59, 59)),
            }));
        },
        minuteOptions: function() {
            const step = Math.max(1, Number(this.minuteStep) || 1);
            const hour = this.currentTime.hour;
            const minutes = [];
            for (let minute = 0; minute < 60; minute += step) {
                minutes.push(minute);
            }
            if (!minutes.includes(this.currentTime.minute)) {
                // Keep an off-step value (e.g. loaded from the server) visible and selected
                minutes.push(this.currentTime.minute);
                minutes.sort((a, b) => a - b);
            }
            return minutes.map((minute) => ({
                value: minute,
                text: pad(minute),
                disabled: this.isRangeDisabled(this.build(this.timeDate, hour, minute, 0), this.build(this.timeDate, hour, minute, 59)),
            }));
        },
        secondOptions: function() {
            const { hour, minute } = this.currentTime;
            return Array.from({ length: 60 }, (_, second) => ({
                value: second,
                text: pad(second),
                disabled: this.isRangeDisabled(this.build(this.timeDate, hour, minute, second), this.build(this.timeDate, hour, minute, second)),
            }));
        },
    },
    watch: {
        errors: {
            immediate: true,
            handler: function(errors) {
                this.errorList = errors;
            },
        },
    },
    beforeUnmount: function() {
        document.removeEventListener('mousedown', this.onOutside);
        document.removeEventListener('focusin', this.onOutside);
    },
    props: {
        modelValue: { type: [String, Number], default: '' },
        mode: { type: String, default: 'date', validator: (value) => ['date', 'time', 'datetime'].includes(value) },
        label: { type: String, default: null },
        placeholder: { type: String, default: '' },
        inputClass: { type: String, default: '' },
        errors: { type: [Array, Object], default: [] },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
        hideDetails: { type: Boolean, default: false },
        clearable: { type: Boolean, default: true },
        min: { type: String, default: null },
        max: { type: String, default: null },
        minuteStep: { type: [String, Number], default: 1 },
        seconds: { type: Boolean, default: false },
        use12Hour: { type: Boolean, default: true },
        defaultTime: { type: String, default: '00:00' },
        // An empty picker starts on the current date/time instead of defaultTime
        startAtNow: { type: Boolean, default: false },
        weekStart: { type: Number, default: 0 },
        yearRange: { type: Array, default: () => [100, 50] },
        locale: { type: String, default: 'en-US' },
        displayFormat: { type: String, default: null },
        displayOptions: { type: Object, default: () => ({}) },
    }
}
</script>
