<template>
    <transition name="fade">
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
        large: { type: Boolean, default: true },
        message: { type: String, default: 'Loading...' },
    }
}
</script>