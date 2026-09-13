<template>
    <div id="user-logs">
        <div class="cz-tabs mb-4">
            <button type="button"
                class="cz-tab"
                :class="{ active: subTab === 'user' }"
                @click="subTab = 'user'">Activity Log</button>
            <button type="button"
                class="cz-tab"
                :class="{ active: subTab === 'email' }"
                @click="subTab = 'email'">Email Log</button>
            <button type="button"
                class="cz-tab"
                :class="{ active: subTab === 'text' }"
                @click="subTab = 'text'">Text Log</button>
        </div>
        <cz-table v-show="subTab === 'user'" :headers="userLogHeaders" :url="userLogUrl" ref="userLogTable" disable-search>
            <template #description="{ description, created_by_name }">
                <div class="cz-table-data">
                    {{ description }}
                    <p v-if="created_by_name" class="note mb-0">By: {{ created_by_name }}</p>
                </div>
            </template>
            <template #created_at="{ created_at }">
                <div class="cz-table-data">{{ formatDate(created_at) }}</div>
            </template>
        </cz-table>
        <cz-table v-show="subTab === 'email'" :headers="emailLogHeaders" :url="emailLogUrl" ref="emailLogTable" disable-search>
            <template #subject="{ subject }">
                <div class="cz-table-data">{{ subject }}</div>
            </template>
            <template #created_at="{ created_at }">
                <div class="cz-table-data">{{ formatDate(created_at) }}</div>
            </template>
        </cz-table>
        <cz-table v-show="subTab === 'text'" :headers="textLogHeaders" :url="textLogUrl" ref="textLogTable" disable-search>
            <template #message="{ message, number, country_code }">
                <div class="cz-table-data">
                    {{ message }}
                    <p class="note mb-0">To: (+{{ country_code }}) {{ number }}</p>
                </div>
            </template>
            <template #created_at="{ created_at }">
                <div class="cz-table-data">{{ formatDate(created_at) }}</div>
            </template>
        </cz-table>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            subTab: 'user',
            userLogHeaders: [
                { name: 'Description', value: 'description' },
                { name: 'When', value: 'created_at', width: '260px' },
            ],
            emailLogHeaders: [
                { name: 'Subject', value: 'subject' },
                { name: 'When', value: 'created_at', width: '260px' },
            ],
            textLogHeaders: [
                { name: 'Message', value: 'message' },
                { name: 'When', value: 'created_at', width: '260px' },
            ],
        };
    },
    methods: {
        refresh: function(tab) {
            const refs = { user: 'userLogTable', email: 'emailLogTable', text: 'textLogTable' };
            this.$refs[refs[tab]]?.query();
        },
    },
    watch: {
        subTab: function(tab) {
            this.refresh(tab);
        },
    },
    computed: {
        userLogUrl: function() {
            return `/user/${this.user}/logs/user`;
        },
        emailLogUrl: function() {
            return `/user/${this.user}/logs/email`;
        },
        textLogUrl: function() {
            return `/user/${this.user}/logs/text`;
        },
    },
    props: {
        user: { type: [String, Number] },
    },
}
</script>
