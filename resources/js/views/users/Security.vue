<template>
    <div id="security-form">
        <fm-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <fm-form v-show="!loading" ref="userSecurityForm" :form="form" @save="save">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="card-title">Roles</h5>
                    <h6 class="card-subtitle mb-2 text-muted">Assign role-based access.</h6>
                    <div class="checkbox-container" v-for="(role, index) in roles">
                        <fm-checkbox
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
                <div class="col-md-6">
                    <h5 class="card-title">Permissions</h5>
                    <h6 class="card-subtitle mb-2 text-muted">Adjust individual rights.</h6>
                    <div class="checkbox-container" v-for="(permission, index) in permissions">
                        <fm-checkbox
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
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="submit"
                        @click="submitAction = 'change'"
                        class="button button--primary button--block mb-0"
                        :disabled="submitting">Change</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="close">Nevermind</button>
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
            roles: [],
            permissions: [],
            form: {
                roles: [],
                permissions: [],
            },
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
            this.loading = false;
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
                setTimeout(() => {
                    this.close();
                }, 500);
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
        close: function() {
            this.$emit('close');
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
        }
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