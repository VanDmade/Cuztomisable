<template>
    <div id="login-layout" class="layout">
        <fm-loading :loading="$store.state.loading" message="Loading..."></fm-loading>
        <div class="center-container">
            <slot></slot>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {

        }
    },
    created: function() {
        
    },
    methods: {

    },
    
}
</script>