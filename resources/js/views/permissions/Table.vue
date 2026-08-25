<template>
    <div class="page table-page container-fluid">
        <cz-table :headers="headers" :url="url" ref="permissionTable">
            <template #header>
                <button
                    @click="form()"
                    class="button button--primary"
                    :class="{ 'button--block mb-4': breakpoint('sm') }">Add Permission</button>
            </template>
            <template #name="{ name, slug }">
                <div class="cz-table-data">
                    {{ name }}
                    <p class="note mb-0">{{ slug }}</p>
                </div>
            </template>
            <template #description="{ description }">
                <div class="cz-table-data cz-table-multi-line">
                    {{ description }}
                </div>
            </template>
            <template #actions="item">
                <div class="cz-table-data text-center">
                    <div :class="{ 'row': breakpoint('sm') }">
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="form(item.id)"
                                class="button button--secondary"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Edit</button>
                        </div>
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="id = item.id; $refs.deletePermissionModal.open();"
                                class="button button--danger"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small ml-1': !breakpoint('sm') }">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </cz-table>
        <cz-modal ref="permissionModal" modal-width="425px">
            <permission-form
                :id="id"
                v-on:close="$refs.permissionModal.close()"
                v-on:message="setMessage"
                v-on:redraw="$refs.permissionTable.query()" />
        </cz-modal>
        <cz-modal ref="deletePermissionModal" modal-width="380px">
            <h3 class="card-title">Delete Permission?</h3>
            <h6 class="card-subtitle mb-6 text-muted">About to delete this permission. Hope you’re sure…</h6>
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
                        @click="$refs.deletePermissionModal.close()">No</button>
                </div>
            </div>
        </cz-modal>
    </div>
</template>
<script>
import PermissionForm from './Form.vue';
export default {
    data: function() {
        return {
            id: null,
            submitting: false,
            url: '/permissions',
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
            this.$refs['permissionModal'].open();
        },
        remove: function() {
            this.submitting = true;
            axios.delete(`/permission/${this.id}`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$refs.permissionTable.query();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.$refs.deletePermissionModal.close();
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
        'permission-form': PermissionForm,
    },
}
</script>