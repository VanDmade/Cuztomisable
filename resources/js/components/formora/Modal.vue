<template>
    <div>
        <div v-if="isOpen" class="modal-backdrop fade show"></div>
        <div
            class="modal fm-modal fade"
            :class="{ show: isOpen }"
            :style="{ display: isOpen ? 'block' : 'none' }"
            :id="id+'-modal'"
            tabindex="-1"
            role="dialog"
            :aria-modal="isOpen ? 'true' : 'false'"
            :aria-hidden="isOpen ? 'false' : 'true'"
            @click.self="onBackdropClick">
            <button type="button" v-show="false" :id="'close-'+id" @click="close"></button>
            <button type="button" v-show="false" :id="'open-'+id" @click="open"></button>
            <div class="modal-dialog fm-modal-dialog" :style="{ 'max-width': modalWidth }">
                <div class="modal-content fm-modal-content">
                    <div class="modal-body fm-modal-body">
                        <slot></slot>
                    </div>
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
            isOpen: false,
        };
    },
    mounted: function() {
        window.addEventListener('keydown', this.onEscape);
    },
    beforeUnmount: function() {
        window.removeEventListener('keydown', this.onEscape);
        this.unlockBody();
    },
    methods: {
        open: function() {
            this.isOpen = true;
            document.body.classList.add('modal-open');
            this.$emit('open');
        },
        close: function() {
            this.isOpen = false;
            this.unlockBody();
            this.$emit('close');
        },
        onBackdropClick: function() {
            if (this.static) {
                return;
            }
            this.close();
        },
        onEscape: function(event) {
            if (!this.isOpen || this.static || event.key !== 'Escape') {
                return;
            }
            this.close();
        },
        unlockBody: function() {
            const hasOpenModal = document.querySelector('.modal.show');
            if (!hasOpenModal) {
                document.body.classList.remove('modal-open');
            }
        }
    },
    props: {
        modalWidth: { type: [Number, String], default: '500px' },
        static: { type: Boolean, default: false }
    }
}
</script>
<style>
    .modal-dialog {
        max-width: none;
    }
</style>