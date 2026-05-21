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