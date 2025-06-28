<template>
    <div class="page" :class="{ 'container': breakpoint('lg'), 'container-fluid': breakpoint('md') || breakpoint('sm') }">
        <tablelify :headers="headers" :url="url" ref="permissionTable">
            <template #header>
                <button
                    @click="form()"
                    class="button button--primary"
                    :class="{ 'button--block mb-4': breakpoint('sm') }">Add Permission</button>
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
                                class="button button--primary"
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
        </tablelify>
        <fm-modal ref="permissionModal" modal-width="425px">
            <permission-form
                :id="id"
                v-on:close="$refs.permissionModal.close()"
                v-on:message="setMessage"
                v-on:redraw="$refs.permissionTable.query()" />
        </fm-modal>
        <fm-modal ref="deletePermissionModal" modal-width="380px">
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
        </fm-modal>
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
            this.id = id;
            this.$refs['permissionModal'].open();
        },
        setMessage: function(message) {
            this.$emit('message', message);
        },
    },
    components: {
        'permission-form': PermissionForm,
    },
}
</script>