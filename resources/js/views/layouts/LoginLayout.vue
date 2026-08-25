<template>
    <div id="login-layout" class="layout">
        <cz-loading :loading="$store.state.loading" message="Loading..."></cz-loading>
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