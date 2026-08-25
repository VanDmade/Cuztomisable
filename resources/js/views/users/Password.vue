<template>
    <div id="change-password-form">
        <cz-form ref="changePasswordForm" :form="form"
            @save="submit">
            <div v-if="admin && user != $store.state.user.id" id="password-admin-controls">
                <h3 class="card-title">Admin Controls</h3>
                <h6 class="card-subtitle mb-4 text-muted">This will send the user an email with a temporary password, which they’ll be required to change upon their next login.</h6>
                <div class="row">
                    <div class="col col-md-6 col-12">
                        <button type="submit"
                            @click="submitAction = 'send'"
                            class="button button--primary button--block mb-0"
                            :disabled="submitting">Send</button>
                    </div>
                    <div class="col col-md-6 col-12">
                        <button type="button"
                            class="button button--secondary button--block mb-0"
                            :disabled="submitting"
                            @click="close">Nevermind</button>
                    </div>
                </div>
            </div>
            <div v-else id="password-controls">
                <h3 class="card-title">Change Password</h3>
                <h6 class="card-subtitle mb-2 text-muted">Set a new password for this account.</h6>
                <cz-input
                    label="Current Password"
                    v-model="form.current"
                    type="password"
                    autocomplete="current-password"
                    :errors="errors.current"
                    :disabled="submitting" />
                <cz-input
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
            </div>
        </cz-form>
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
                this.$message.push({ text: data.message });
                setTimeout(() => {
                    this.$emit('close');
                }, 1000);
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