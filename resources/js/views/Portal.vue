<template>
    <div class="page">
        <h4>Portal</h4>
        {{ $store.state.user }}
    </div>
</template>
<script>
export default {
    data: function() {
        return {
        }
    },
    created: function() {
        this.$emit('layout', 'portal-layout');
    },
    methods: {

    },
}
</script>