<template>
    <div class="page table-page container-fluid">
        <cz-table :headers="headers" :url="url" ref="errorLogTable">
            <template #header>
                <h3 class="card-title mb-0">Error Logs</h3>
                <h6 class="card-subtitle mb-0 text-muted">Exceptions caught across the application.</h6>
            </template>
            <template #filters>
                <div class="cz-filter-wrap" :class="{ open: filtersOpen }">
                    <button type="button"
                        class="button button--secondary cz-filter-toggle"
                        :class="{ 'cz-filter-toggle--active': showClosed }"
                        @click.stop="filtersOpen = !filtersOpen">
                        <span class="material-icons">filter_list</span>
                        Filters<span v-if="showClosed" class="cz-filter-badge">1</span>
                    </button>
                    <div class="cz-filter-panel" v-show="filtersOpen" @click.stop>
                        <cz-checkbox
                            label="Show closed"
                            v-model="showClosed"
                            type="checkbox"
                            hide-details />
                    </div>
                </div>
            </template>
            <template #message="{ message, file, line, debug_code, closed_at, closed_by_name, closed_reason }">
                <div class="cz-table-data cz-table-multi-line">
                    {{ message }}
                    <p class="note mb-0">{{ file }}<span v-if="line">:{{ line }}</span></p>
                    <p class="note mb-0">Debug code: {{ debug_code }}</p>
                    <p v-if="closed_at" class="cz-user-badge cz-user-badge--success mt-2">
                        Closed by {{ closed_by_name || 'system' }}<span v-if="closed_reason">: {{ closed_reason }}</span>
                    </p>
                </div>
            </template>
            <template #recipient_name="{ recipient_name }">
                <div class="cz-table-data">{{ recipient_name || '—' }}</div>
            </template>
            <template #created_at="{ created_at }">
                <div class="cz-table-data">{{ formatDate(created_at) }}</div>
            </template>
            <template #actions="{ id, message, closed_at }">
                <div class="cz-table-data">
                    <button type="button"
                        v-if="!closed_at"
                        class="button button--danger button--small"
                        @click="openCloseModal(id, message)">Close</button>
                </div>
            </template>
        </cz-table>
        <cz-modal ref="closeModal" modal-width="450px" @close="clearCloseModal">
            <h3 class="card-title">Close Error</h3>
            <h6 class="card-subtitle mb-6 text-muted">{{ closingMessage }}</h6>
            <cz-textarea
                label="Reason"
                v-model="closeReason"
                :errors="closeErrors"
                rows="3" />
            <cz-checkbox
                label="Also close all matching duplicates"
                v-model="closeDuplicates"
                type="checkbox" />
            <button type="button"
                class="button button--danger button--block"
                :disabled="closing || !closeReason"
                @click="submitClose">Close</button>
        </cz-modal>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            showClosed: false,
            filtersOpen: false,
            closingId: null,
            closingMessage: '',
            closeReason: '',
            closeDuplicates: false,
            closeErrors: [],
            closing: false,
            headers: [
                { name: 'Error', value: 'message' },
                { name: 'User', value: 'recipient_name', width: '200px' },
                { name: 'When', value: 'created_at', width: '260px' },
                { name: '', value: 'actions', sortable: false, width: '100px' },
            ],
        };
    },
    methods: {
        handleDocumentClick: function() {
            this.filtersOpen = false;
        },
        openCloseModal: function(id, message) {
            this.closingId = id;
            this.closingMessage = message;
            this.closeReason = '';
            this.closeDuplicates = false;
            this.closeErrors = [];
            this.$refs.closeModal.open();
        },
        clearCloseModal: function() {
            this.closingId = null;
            this.closingMessage = '';
            this.closeReason = '';
            this.closeDuplicates = false;
            this.closeErrors = [];
        },
        submitClose: function() {
            this.closing = true;
            this.closeErrors = [];
            axios.patch(`/logs/error/${this.closingId}/close`, {
                reason: this.closeReason,
                close_duplicates: this.closeDuplicates,
            }).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$refs.closeModal.close();
                this.$refs.errorLogTable.query();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.closing = false;
                }, 500);
            });
        },
    },
    computed: {
        url: function() {
            return this.showClosed ? '/logs/error?show_closed=1' : '/logs/error';
        },
    },
    mounted: function() {
        document.addEventListener('click', this.handleDocumentClick);
    },
    beforeUnmount: function() {
        document.removeEventListener('click', this.handleDocumentClick);
    },
}
</script>
