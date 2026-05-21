import { reactive } from 'vue';

const DEFAULT_MESSAGE = 'Loading...';

const state = reactive({
    active: false,
    message: DEFAULT_MESSAGE,
});

let timer = null;

function clearTimer() {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
}

function normalizeOptions(input) {
    if (typeof input === 'number') {
        return { duration: input };
    }
    if (typeof input === 'string') {
        return { message: input };
    }
    return input ?? {};
}

function show(input = {}) {
    const options = normalizeOptions(input);
    if (typeof options.message !== 'undefined') {
        state.message = options.message || DEFAULT_MESSAGE;
    }
    if (!state.active) {
        state.active = true;
    }
    clearTimer();
    const duration = Number(options.duration ?? options.timeout);
    if (Number.isFinite(duration) && duration > 0) {
        timer = setTimeout(() => {
            hide();
        }, duration);
    }
}

function hide(resetMessage = true) {
    clearTimer();
    state.active = false;
    if (resetMessage) {
        state.message = DEFAULT_MESSAGE;
    }
}

function setMessage(message) {
    state.message = message || DEFAULT_MESSAGE;
}

function withMessage(message, input = {}) {
    const options = normalizeOptions(input);
    show({ ...options, message });
}

const loading = {
    state,
    show,
    start: show,
    hide,
    stop: hide,
    setMessage,
    set: setMessage,
    withMessage,
    resetTimer(duration) {
        if (!state.active) {
            return;
        }
        show({ duration });
    },
};

export default loading;
