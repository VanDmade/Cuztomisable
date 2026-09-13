<template>
    <div id="security-form">
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <cz-form v-show="!loading" ref="userSecurityForm" :form="form" @save="save">
            <div class="row mb-4">
                <div class="col-md-5">
                    <h5 class="card-title">Roles</h5>
                    <h6 class="card-subtitle mb-2 text-muted">Assign role-based access.</h6>
                    <div class="cz-checkbox-columns" :class="{ 'cz-checkbox-columns--2': roles.length > rolesColumnThreshold }">
                        <div class="checkbox-container" v-for="(role, index) in roles">
                            <cz-checkbox
                                :label="role.name"
                                v-model="form.roles[role.id.toString()]"
                                type="checkbox"
                                :disabled="submitting"
                                :input-true-value="role.id"
                                :input-false-value="false"
                                class="flex-1"
                                hide-details />
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <h5 class="card-title">Permissions</h5>
                    <h6 class="card-subtitle mb-2 text-muted">Adjust individual rights.</h6>
                    <div class="cz-checkbox-columns cz-checkbox-columns--2">
                        <div class="checkbox-container" v-for="(permission, index) in permissions">
                            <cz-checkbox
                                :label="permission.name"
                                v-model="form.permissions[permission.id.toString()]"
                                type="checkbox"
                                :disabled="submitting || partOfRole(permission.id)"
                                :input-true-value="permission.id"
                                :input-false-value="false"
                                class="flex-1"
                                hide-details />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit"
                class="button button--primary"
                :disabled="submitting">Save Access</button>
        </cz-form>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            roles: [],
            permissions: [],
            form: {
                roles: [],
                permissions: [],
            },
            snapshot: null,
            // Below this many roles, a single column is short enough to scan at a glance -
            // splitting it into two only pays off once the list gets long.
            rolesColumnThreshold: 6,
        };
    },
    created: function() {
        this.loading = true;
        axios.all([
            this.getRoles(),
            this.getPermissions(),
        ]).then(axios.spread((roleResponse, permissionResponse) => {
            this.roles = roleResponse.data.list;
            this.permissions = permissionResponse.data.list;
            this.get();
        }));
    },
    methods: {
        reset: function() {
            this.loading = true;
            this.get();
        },
        get: function() {
            axios.get(`/user/${this.user}/access`).then(({ data }) => {
                this.form = data.access;
                this.snapshot = JSON.stringify(this.form);
            }).catch((error) => {

            }).finally(() => {
                setTimeout(() => {
                    this.loading = false;
                }, 1000);
            });
        },
        save: function() {
            this.submitting = true;
            let formData = new FormData();
            formData = this.appendIndexedValues(formData, this.form.roles, 'roles');
            formData = this.appendIndexedValues(formData, this.form.permissions, 'permissions');
            axios.post(`/user/${this.user}/access`, this.cleanFormData(formData)).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.snapshot = JSON.stringify(this.form);
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
        getRoles: function() {
            return axios.get('/list/roles?include_permissions');
        },
        getPermissions: function() {
            return axios.get('/list/permissions');
        },
        partOfRole: function(permission) {
            return this.roles.some(role => {
                const isSelected = typeof(this.form.roles[role.id]) != 'undefined' && this.form.roles[role.id] !== false;
                return isSelected && role.permission_list.includes(permission);
            });
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                this.$emit('update:modelValue', value);
            }
        },
        dirty: function() {
            return this.snapshot !== null && JSON.stringify(this.form) !== this.snapshot;
        },
    },
    watch: {
        'form.roles': {
            handler: function(roles) {
                this.form.permissions = [];
                for (let i = 0; i < this.roles.length; i++) {
                    if (typeof(roles[this.roles[i].id]) != 'undefined' && roles[this.roles[i].id] !== false) {
                        for (let j = 0; j < this.roles[i].permission_list.length; j++) {
                            this.form.permissions[this.roles[i].permission_list[j]] = this.roles[i].permission_list[j];
                        }
                    }
                }
            },
            deep: true,
        }
    },
    props: {
        modelValue: { type: Boolean, default: false },
        user: { type: [String, Number] }
    }
}
</script>