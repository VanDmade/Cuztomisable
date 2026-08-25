<template>
    <div class="permission-form">
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <cz-form v-show="!loading" ref="permissionForm" :form="form" @save="save">
            <h3 class="card-title">{{ form?.id ? 'Manage': 'Create' }} Permission</h3>
            <h6 class="card-subtitle mb-6 text-muted">Specify the permission key, slug, and purpose.</h6>
            <cz-input
                label="Name"
                v-model="form.name"
                type="text"
                :errors="errors.name"
                :disabled="submitting" />
            <cz-input
                label="Slug"
                v-model="form.slug"
                type="text"
                :errors="errors.slug"
                :disabled="submitting" />
            <cz-textarea
                label="Description"
                v-model="form.description"
                :errors="errors.description"
                :disabled="submitting"
                rows="5" />
            <div class="mt-4">
                <button type="submit"
                    @click="save"
                    class="button button--primary"
                    :class="{ 'mb-2 button--block': breakpoint('sm'), 'mb-0 button-width': !breakpoint('sm') }"
                    :disabled="submitting">Save</button>
                <button type="button"
                    class="button button--secondary"
                    :class="{ 'mb-2 button--block': breakpoint('sm'), 'ml-4 mb-0 button-width': !breakpoint('sm') }"
                    :disabled="submitting"
                    @click="close">Nevermind</button>
            </div>
        </cz-form>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            errors: [],
            form: {},
        }
    },
    methods: {
        get: function() {
            this.loading = true;
            axios.get(`/permission/${this.id}`).then(({ data }) => {
                this.form = data.permission;
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.error(response.data.message);
                }
            }).finally(() => {
                setTimeout(() => {
                    this.loading = false;
                }, 1500);
            });
        },
        save: function() {
            let formData = new FormData();
            formData.append('name', this.form.name ?? '');
            formData.append('slug', this.form.slug ?? '');
            formData.append('description', this.form.description ?? '');
            this.submitting = true;
            let id = this.id != null ? `/${this.id}` : '';
            axios.post(`/permission${id}`, formData).then(({ data }) => {
                this.$message.success(data.message);
                this.$emit('redraw');
                setTimeout(() => {
                    this.close();
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$message.error(response.data.message);
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        close: function() {
            this.$emit('close');
            setTimeout(() => {
                this.errors = [];
                this.form = {};
            }, 250);
        },
    },
    watch: {
        id: {
            immediate: true,
            handler: function(id) {
                this.loading = true;
                this.errors = [];
                this.form = {};
                if (id != '' && id != null) {
                    this.get();
                } else {
                    this.loading = false;
                }
            },
            deep: true,
        }
    },
    props: {
        id: { type: [String, Number], default: null },
    }
}
</script>