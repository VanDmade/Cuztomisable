<template>
    <transition name="fade">
        <div v-if="loading" :class="['fm-loading', { large, 'fm-loading-full': full }]">
            <div class="fm-spinner spinner-border fm-bootstrap-spinner text-primary" :class="{ 'spinner-border-sm': !large }" role="status" aria-live="polite">
                <span class="visually-hidden">{{ message }}</span>
            </div>
            <p class="fm-loading-text">{{ message }}</p>
        </div>
    </transition>
</template>
<script>
export default {
    data: function() {
        return {
            
        }
    },
    methods: {

    },
    props: {
        loading: { type: Boolean, required: true },
        full: { type: Boolean, default: true },
        large: { type: Boolean, default: true },
        message: { type: String, default: 'Loading...' },
    }
}
</script>
<style scoped>
.fm-loading {
    inset: 0;
    background: white;
    display: flex;
    flex-direction: row;
    gap: 1.5rem;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    font-family: sans-serif;
}

.fm-loading.fm-loading-full {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}

.fm-bootstrap-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: -0.125em;
    border: 0.2em solid currentColor;
    border-right-color: transparent;
    border-radius: 9999px;
    animation: fm-spinner-border 0.75s linear infinite;
    margin-bottom: 0;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.2em;
}

.fm-loading.large .fm-bootstrap-spinner {
    width: 90px;
    height: 90px;
    border-width: 10px;
}

.fm-loading-text {
    font-size: 1.2rem;
    color: #555;
    margin-bottom: 0;
}

.fm-loading.large .fm-loading-text {
    font-size: 3rem;
}

.text-primary {
    color: #f97316;
}

.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes fm-spinner-border {
    to {
        transform: rotate(360deg);
    }
}
</style>