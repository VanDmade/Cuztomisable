<template>
    <div class="page table-page container-fluid">
        <tablelify :headers="headers" :url="url" ref="roleTable">
            <template #header>
                <button
                    @click="form()"
                    class="button button--primary"
                    :class="{ 'button--block mb-4': breakpoint('sm') }">Add Role</button>
            </template>
            <template #name="{ name, slug }">
                <div class="tablelify-data">
                    {{ name }}
                    <p class="note mb-0">{{ slug }}</p>
                </div>
            </template>
            <template #description="{ description }">
                <div class="tablelify-data tablelify-multi-line">
                    {{ description }}
                </div>
            </template>
            <template #actions="item">
                <div class="tablelify-data text-center">
                    <div :class="{ 'row': breakpoint('sm') }">
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="form(item.id)"
                                class="button button--secondary"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Edit</button>
                        </div>
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="id = item.id; $refs.deleteRoleModal.open();"
                                class="button button--danger"
                                :disabled="submitting"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small ml-1': !breakpoint('sm') }">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </tablelify>
        <fm-modal ref="roleModal" modal-width="425px">
            <role-form
                :id="id"
                v-on:close="$refs.roleModal.close()"
                v-on:message="setMessage"
                v-on:redraw="$refs.roleTable.query()" />
        </fm-modal>
        <fm-modal ref="deleteRoleModal" modal-width="380px">
            <h3 class="card-title">Delete Role?</h3>
            <h6 class="card-subtitle mb-6 text-muted">About to delete this role. Any users still assigned to it are about to feel very unauthorized.</h6>
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--primary button--block mb-0"
                        :disabled="submitting"
                        @click="remove">Yes</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="$refs.deleteRoleModal.close()">No</button>
                </div>
            </div>
        </fm-modal>
    </div>
</template>
<script>
import RoleForm from './Form.vue';
export default {
    data: function() {
        return {
            id: null,
            submitting: false,
            url: '/roles',
            headers: [
                { name: 'Name', value: 'name', width: '220px' },
                { name: 'Description', value: 'description', sortable: false },
                { name: '', value: 'actions', sortable: false, width: '150px' },
            ]
        }
    },
    methods: {
        form: function(id = null) {
            // Fixes the issue where the form doesn't reload after closing
            if (id == this.id) {
                this.id = null;
            }
            setTimeout(() => { this.id = id; }, 50);
            this.$refs['roleModal'].open();
        },
        remove: function() {
            this.submitting = true;
            axios.delete(`/role/${this.id}`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$refs.roleTable.query();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.$refs.deleteRoleModal.close();
                }, 250);
                setTimeout(() => {
                    this.submitting = false;
                }, 500);
            });
        },
        setMessage: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
    },
    components: {
        'role-form': RoleForm,
    },
}
</script>