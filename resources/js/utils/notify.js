import { reactive } from 'vue';

const DEFAULT_DURATION = 5000;
const DEFAULT_MAX_WIDTH = '400px';
const DEFAULT_MIN_WIDTH = '320px';

function calculateReadDuration(text) {
    const words = String(text ?? '').trim().split(/\s+/).filter(Boolean).length;
    return (Math.max(1, words) + 2) * 1000;
}

const state = reactive({
    items: [],
    settings: {
        minWidth: DEFAULT_MIN_WIDTH,
        maxWidth: DEFAULT_MAX_WIDTH,
    },
    nextId: 1,
});

const timers = new Map();

function normalizeNotification(input = {}, options = {}) {
    const payload = typeof input === 'string' ? { text: input } : (input ?? {});
    const merged = { ...payload, ...options };
    const text = String(merged.text ?? '').trim();

    if (!text) {
        return null;
    }

    const persistent = Boolean(merged.persistent ?? false);
    const hasExplicitDuration = typeof merged.duration !== 'undefined' || typeof merged.timeout_length !== 'undefined';
    const duration = hasExplicitDuration
        ? Number(merged.duration ?? merged.timeout_length)
        : calculateReadDuration(text);
    const color = String(merged.color ?? (merged.error ? 'danger' : 'success')).toLowerCase();

    return {
        id: state.nextId++,
        text,
        color,
        persistent,
        duration: Number.isFinite(duration) && duration > 0
            ? duration
            : (hasExplicitDuration ? DEFAULT_DURATION : calculateReadDuration(text)),
    };
}

function remove(id) {
    if (timers.has(id)) {
        clearTimeout(timers.get(id));
        timers.delete(id);
    }

    const index = state.items.findIndex((item) => item.id === id);
    if (index !== -1) {
        state.items.splice(index, 1);
    }
}

function push(input, options = {}) {
    const normalized = normalizeNotification(input, options);

    if (!normalized) {
        return null;
    }

    state.items.unshift(normalized);

    if (!normalized.persistent) {
        const timer = setTimeout(() => {
            remove(normalized.id);
        }, normalized.duration);
        timers.set(normalized.id, timer);
    }

    return normalized.id;
}

function clear() {
    Array.from(timers.keys()).forEach((id) => remove(id));
    state.items.splice(0, state.items.length);
}

function configure(settings = {}) {
    if (typeof settings.minWidth !== 'undefined') {
        state.settings.minWidth = settings.minWidth || DEFAULT_MIN_WIDTH;
    }

    if (typeof settings.maxWidth !== 'undefined') {
        state.settings.maxWidth = settings.maxWidth || DEFAULT_MAX_WIDTH;
    }
}

const notify = {
    state,
    push,
    show: push,
    remove,
    clear,
    configure,
    primary(text, options = {}) {
        return push(text, { ...options, color: 'primary' });
    },
    secondary(text, options = {}) {
        return push(text, { ...options, color: 'secondary' });
    },
    success(text, options = {}) {
        return push(text, { ...options, color: 'success' });
    },
    danger(text, options = {}) {
        return push(text, { ...options, color: 'danger' });
    },
    error(text, options = {}) {
        return push(text, { ...options, color: 'danger' });
    },
    info(text, options = {}) {
        return push(text, { ...options, color: 'info' });
    },
    warning(text, options = {}) {
        return push(text, { ...options, color: 'warning' });
    },
};

export default notify;
