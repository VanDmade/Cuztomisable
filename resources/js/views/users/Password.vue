<template>
    <div id="change-password-form">
        <h3 class="card-title">Change Password</h3>
        <h6 class="card-subtitle mb-2 text-muted">Set a new password for this account.</h6>
        <fm-form ref="changePasswordForm" class="mt-4" :form="form"
            @save="submit">
            <fm-input
                label="Current Password"
                v-model="form.current"
                type="password"
                autocomplete="current-password"
                :errors="errors.current"
                :disabled="submitting" />
            <fm-input
                label="New Password"
                v-model="form.new"
                type="password"
                autocomplete="new-password"
                :errors="errors.new"
                :disabled="submitting" />
            <requirements :password="form.new" v-on:completed="completed" class="mb-4"></requirements>
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="submit"
                        @click="submitAction = 'change'"
                        class="button button--primary button--block mb-0"
                        :disabled="submitting || !passwordRequirementsMet">Change</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="close">Nevermind</button>
                </div>
            </div>
            <div v-if="admin" id="password-admin-controls">
                <hr class="mb-4 mt-4">
                <h3 class="card-title">Admin Controls</h3>
                <h6 class="card-subtitle mb-4 text-muted">Manage user password options and settings.</h6>
                <button type="submit"
                    @click="submitAction = 'send'"
                    class="button button--accent button--block"
                    :disabled="submitting">Send Password Reset to User</button>
            </div>
        </fm-form>
    </div>
</template>
<script>
import PasswordRequirements from '../../components/PasswordRequirements.vue';
function initialize() {
    return {
        submitting: false,
        submitAction: 'change',
        passwordRequirementsMet: false,
        errors: [],
        form: {
            current: '',
            new: '',
        },
    };
}
export default {
    data: function() {
        return {
            ...initialize(),
        };
    },
    methods: {
        reset: function() {
            Object.assign(this.$data, initialize());
        },
        submit: function() {
            this.submitting = true;
            this.errors = [];
            let formData = new FormData();
            if (this.submitAction === 'change') {
                formData.append('current', this.form.current ?? '');
                formData.append('new', this.form.new ?? '');
            }
            axios.post(`/user/${this.user}/${this.submitAction}/password`, formData).then(({ data }) => {
                this.$emit('message', { text: data.message });
                setTimeout(() => {
                    this.$emit('close');
                }, 1000);
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
                }, 1000);
            });
        },
        completed: function(value) {
            this.passwordRequirementsMet = value;
        },
        close: function() {
            this.$emit('close');
        }
    },
    props: {
        admin: { type: Boolean, default: false },
        user: { type: [Number, String], default: null },
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>