<template>
    <div class="role-form">
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <cz-form v-show="!loading" ref="roleForm" :form="form" @save="save">
            <h3 class="card-title">{{ form?.id ? 'Manage': 'Create' }} Role</h3>
            <h6 class="card-subtitle mb-6 text-muted">Define the role's identifier, slug, and description.</h6>
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
            <hr class="mt-4 mb-4">
            <h5 class="card-title" :class="{ 'mb-3': permissions.length != 0 }">Permission</h5>
            <h6 v-if="permissions.length == 0" class="card-subtitle mb-6 text-muted">The system doesn't seem to have any permissions yet.</h6>
            <div v-for="(permission, index) in permissions">
                <div class="d-flex">
                    <cz-checkbox
                        :label="permission.name"
                        v-model="form.permissions[permission.id.toString()]"
                        type="checkbox"
                        :disabled="submitting"
                        :input-true-value="permission.id"
                        :input-false-value="false"
                        class="flex-1"
                        hide-details />
                    <i v-if="minimized(permission.id)" @click="minimize[permission.id] = false" class="cursor--pointer material-icons">expand_less</i>
                    <i v-else @click="minimize[permission.id] = true" class="cursor--pointer material-icons">expand_more</i>
                </div>
                <p v-if="minimized(permission.id)" class="note">{{ permission.subtitle }}</p>
            </div>
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
            permissions: [],
            minimize: [],
            form: {
                permissions: {},
            },
        }
    },
    created: function() {
        this.getPermissions();
    },
    methods: {
        get: function() {
            this.loading = true;
            axios.get(`/role/${this.id}`).then(({ data }) => {
                const assignedPermissions = new Set(data.role.permissions.map(p => p.id));
                delete data.role.permissions;
                this.form = data.role;
                const permissions = {};
                for (const { id } of this.permissions) {
                    if (assignedPermissions.has(id)) {
                        permissions[id] = id;
                    }
                }
                this.form.permissions = permissions;
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
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
            let keys = Object.keys(this.form.permissions);
            let counter = 0;
            for (let i = 0; i < keys.length; i++) {
                if (this.form.permissions[keys[i]] !== false) {
                    formData.append(`permissions[${counter++}]`, this.form.permissions[keys[i]]);
                }
            }
            this.submitting = true;
            let id = this.id != null ? `/${this.id}` : '';
            axios.post(`/role${id}`, this.cleanFormData(formData)).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$emit('redraw');
                setTimeout(() => {
                    this.close();
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        getPermissions: function() {
            axios.get('/list/permissions').then(({ data }) => {
                this.permissions = data.list;
            });
        },
        close: function() {
            this.$emit('close');
            setTimeout(() => {
                this.errors = [];
                this.form = { permissions: {} };
            }, 250);
        },
        minimized: function(id) {
            return typeof(this.minimize[id]) != 'undefined' && this.minimize[id] ? true : false;
        }
    },
    watch: {
        id: {
            immediate: true,
            handler: function(id) {
                this.loading = true;
                this.errors = [];
                this.form = { permissions: {} };
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