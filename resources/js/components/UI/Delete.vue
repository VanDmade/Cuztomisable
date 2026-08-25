<template>
    <div class="cz-delete">
        <cz-loading v-if="loading" :loading="loading" :full="false"></cz-loading>
        <div v-else>
            <h4 class="mb-6" :class="{ 'text-center': breakpoint('sm') }">{{ title }}</h4>
            <slot></slot>
            <div :class="{ 'd-grid': breakpoint('sm') }" class="mt-4">
                <button type="button" :disabled="disabled" @click="remove" class="btn btn-danger" :class="{ 'mb-4': breakpoint('sm'), 'mr-4': !breakpoint('sm') }">Delete</button>
                <button type="button" :disabled="disabled" class="btn btn-secondary" @click="$emit('close')">Go back</button>
            </div>
        </div>
    </div>
</template>
<script>
import notify from '../../utils/notify';
export default {
    data: function() {
        return {
            loading: false,
            disabled: false,
        };
    },
    methods: {
        remove: function() {
            if (this.id == null) {
                notify.danger('Something has occurred, this item could not be deleted.');
                return false;
            }
            this.disabled = true;
            axios.delete(this.url+this.id, { params: this.params }).then(({ data }) => {
                notify.success(data.message);
                this.$emit('redraw');
                this.$emit('close');
            }).catch((error) => {
                notify.danger(error.response?.data?.message ?? 'Something went wrong.');
                this.disabled = false;
            }).finally(() => {
                this.disabled = false;
            });
        }
    },
    props: {
        id: { type: [String, Number], required: true },
        url: { type: String, required: true },
        title: { type: String, default: 'Are you sure you want to delete this entry?' },
        params: { type: Object, default: () => ({}) },
    }
}
</script>