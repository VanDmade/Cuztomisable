<template>
    <div class="fm-delete">
        <loading v-if="loading" :full="false"></loading>
        <div v-else>
            <div class="alert"
                v-if="message.text != ''"
                :class="{ 'alert-danger': message.type == 'danger', 'alert-success': message.type }"
                role="alert">{{ message.text }}</div>
            <h4 class="mb-6" :class="{ 'text-center': breakpoint('sm') }">{{ title }}</h4>
            <div :class="{ 'd-grid': breakpoint('sm') }">
                <button type="button" :disabled="disabled" @click="remove" class="btn btn-danger" :class="{ 'mb-4': breakpoint('sm'), 'mr-4': !breakpoint('sm') }">Delete</button>
                <button type="button" :disabled="disabled" class="btn btn-secondary" data-bs-dismiss="modal">Go back</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            loading: false,
            disabled: false,
            message: {
                type: '',
                text: '',
            }
        };
    },
    methods: {
        remove: function() {
            this.message = { type: '', text: '' };
            if (this.id == null) {
                this.message = { type: 'danger', text: 'Something has occurred, this item could not be deleted.' };
                return false;
            }
            this.disabled = true;
            axios.delete(this.url + this.id).then(({ data }) => {
                this.message = { type: 'success', text: data.message };
            }).catch((error) => {
                if (error.response.data.message) {
                    this.message = { type: 'danger', text: error.response.data.message };
                }
            }).finally(() => {
                this.$emit('redraw');
                setTimeout(() => {
                    this.$emit('close');
                    this.disabled = false;
                }, 2500);
            });
        }
    },
    props: {
        id: { type: [String, Number], required: true },
        url: { type: String, required: true },
        title: { type: String, default: 'Are you sure you want to delete this entry?' },
    }
}
</script>