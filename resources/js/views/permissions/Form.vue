<template>
    <div class="permission-form">
        <fm-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <fm-form v-show="!loading" ref="permissionForm" :form="form" @save="save">
            <h3 class="card-title">Permissions</h3>
            <h6 class="card-subtitle mb-6 text-muted">Specify the permission key, slug, and purpose.</h6>
            <fm-input
                label="Name"
                v-model="form.name"
                type="text"
                :errors="errors.name"
                :disabled="submitting" />
            <fm-input
                label="Slug"
                v-model="form.slug"
                type="text"
                :errors="errors.slug"
                :disabled="submitting" />
            <fm-textarea
                label="Description"
                v-model="form.description"
                :errors="errors.description"
                :disabled="submitting"
                rows="5" />
            <div class="row mt-4">
                <div class="col col-md-6 col-12">
                    <button type="submit"
                        @click="save"
                        class="button button--primary button--block mb-0"
                        :class="{ 'mb-2': breakpoint('sm') }"
                        :disabled="submitting">Save</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        @click="close"
                        class="button button--danger button--block mb-0"
                        :disabled="submitting">Cancel</button>
                </div>
            </div>
        </fm-form>
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
                    this.$emit('message', { text: response.data.message, error: true });
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
                this.$emit('message', { text: data.message });
                this.$emit('redraw');
                setTimeout(() => {
                    this.close();
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
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
                console.log(id);
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