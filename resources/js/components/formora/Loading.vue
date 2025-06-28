<template>
    <transition name="fade" class="fm-loading-transition" :class="{ 'fm-loading-full': full }">
        <div v-if="loading" :class="['fm-loading', { large }]">
            <div class="fm-spinner"></div>
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