<template>
    <div id="login-layout" class="layout">
        <div class="center-container">
            <slot @loading="$emit('loading')" @layout="$emit('layout')"></slot>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {

        }
    },
    methods: {

    },
    
}
</script>