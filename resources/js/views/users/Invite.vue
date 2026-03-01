<template>
    <div id="create-user-form">
        <div class="row">
            <div class="col-lg-4 col-md-5 col-sm-12">
                <fm-form ref="userCreateForm" :form="form" @save="save">
                    <h3 class="card-title">Invite User</h3>
                    <h6 class="card-subtitle mb-6 text-muted">Granting access like a digital bouncer.</h6>
                    <fm-input
                        label="Name"
                        v-model="form.name"
                        type="text"
                        :errors="errors.name"
                        :disabled="submitting" />
                    <fm-phone
                        v-if="form.use_phone == '1'"
                        label="Phone"
                        v-model="form.phone"
                        :errors="errors.phone"
                        :disabled="submitting"
                        is-mobile
                        default />
                    <fm-input
                        v-else
                        label="Email"
                        v-model="form.email"
                        type="email"
                        :errors="errors.email"
                        :disabled="submitting" />
                    <fm-checkbox
                        label="Send invite via phone number"
                        v-model="form.use_phone"
                        @change="errors = [];"
                        type="radio"
                        :disabled="submitting" />
                    <div class="row mt-4">
                        <div class="col col-md-6 col-12">
                            <button type="submit"
                                @click="save"
                                class="button button--primary button--block mb-0"
                                :disabled="submitting">Invite</button>
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
            <div class="col-lg-8 col-md-7 col-sm-12">
                <tablelify :headers="headers" :url="url" ref="inviteTable">
                    <template #name="{ name, email, phone }">
                        <div class="tablelify-data">
                            {{ name }}
                            <p class="note mb-0">
                                <a v-if="email != null" :href="'mailto:'+email">{{ email }}</a>
                                <span v-else>{{ phone }}</span>
                            </p>
                        </div>
                    </template>
                    <template #expires_at="{ expires_at }">
                        <div class="tablelify-data">
                            {{ expires_at == null ? 'Never' : formatDate(expires_at) }}
                        </div>
                    </template>
                    <template #actions="item">
                        <div class="tablelify-data text-center">
                            <div :class="{ 'row': breakpoint('sm') }">
                                <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                                    <button
                                        @click="resend(item.id)"
                                        class="button button--primary"
                                        :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Resend</button>
                                </div>
                                <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                                    <button
                                        @click="id = item.id; $refs.deleteUserModal.open();"
                                        class="button button--danger"
                                        :class="{ 'button--block': breakpoint('sm'), 'button--small ml-1': !breakpoint('sm') }">Delete</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </tablelify>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            form: {},
            url: '/invites',
            headers: [
                { name: 'Name', value: 'name' },
                { name: 'Expires At', value: 'expires_at' },
                { name: '', value: 'actions', sortable: false, width: '150px' },
            ]
        };
    },
    methods: {
        save: function() {
            let usePhone = this.form.use_phone ?? false;
            let formData = new FormData();
            formData.append('name', this.form.name ?? '');
            formData.append('use_phone', usePhone ? '1' : '0');
            // Determmines if the invite will go through phone or email
            if (usePhone) {
                formData.append('phone', this.form.phone?.number ?? '');
                formData.append('country_code', this.form.phone?.country_code ?? '');
            } else {
                formData.append('email', this.form.email ?? '');
            }
            this.submitting = true;
            axios.post('/invite', formData).then(({ data }) => {
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
            this.errors = [];
            this.form = {};
            this.$emit('close');
        },
    }
}
</script>
