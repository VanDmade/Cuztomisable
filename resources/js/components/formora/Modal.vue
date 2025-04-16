<template>
    <div class="modal fm-modal fade" :id="id+'-modal'" tabindex="-1" aria-hidden="true">
        <button type="button" v-show="false" :id="'close-'+id" data-bs-dismiss="modal"></button>
        <button type="button" v-show="false" :id="'open-'+id" data-bs-toggle="modal" :data-bs-target="'#'+id+'-modal'" data-bs-dismiss="modal"></button>
        <div class="modal-dialog fm-modal-dialog" :style="{ 'max-width': modalWidth }">
            <div class="modal-content fm-modal-content">
                <div class="modal-body fm-modal-body">
                    <slot></slot>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: Math.random().toString(16).slice(2),
        };
    },
    methods: {
        open: function() {
            var modal = document.getElementById('open-'+this.id);
            modal.click();
        },
        close: function() {
            var modal = document.getElementById('close-'+this.id);
            modal.click();
        }
    },
    props: {
        modalWidth: { type: [Number, String], default: '500px' },
    }
}
</script>
<style>
    .modal-dialog {
        max-width: none;
    }
</style>