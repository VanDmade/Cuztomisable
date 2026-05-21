<template>
    <fm-form ref="forceChangePasswordForm" :form="form" @save="save">
        <input
            type="text"
            autocomplete="username"
            tabindex="-1"
            aria-hidden="true"
            class="autocomplete-hidden">
        <input
            type="password"
            autocomplete="current-password"
            tabindex="-1"
            aria-hidden="true"
            class="autocomplete-hidden">
        <h3 class="card-title">Password Change Required</h3>
        <h6 class="card-subtitle mb-4 text-muted">You must update your password before you can continue.</h6>
        <fm-input
            label="Password"
            v-model="form.new"
            type="password"
            autocomplete="new-password"
            :errors="errors.new"
            :disabled="submitting" />
        <requirements :password="form.new" v-on:completed="completed"></requirements>
        <div class="form-buttons">
            <button type="submit"
                :disabled="!passwordRequirementsMet || submitting"
                class="button button--primary button--block">Save</button>
        </div>
    </fm-form>
</template>
<script>
import PasswordRequirements from './PasswordRequirements.vue';
export default {
    data: function() {
        return {
            submitting: false,
            passwordRequirementsMet: false,
            errors: [],
            form: {
                new: '',
            }, 
        }
    },
    methods: {
        save: function() {
            this.submitting = true;
            let formData = new FormData();
            formData.append('new', this.form.new);
            axios.post('/user/change/password?force', this.cleanFormData(formData)).then(({ data }) => {
                this.$message.success(data.message);
                this.$store.commit('SET_CHANGE_PASSWORD', false);
                setTimeout(() => {
                    this.$emit('close');
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$message.error(response.data.message);
                }
                if (response?.status == 403) {
                    // They are unauthorized to make this change, so the system will close out of the modal
                    this.$emit('close');
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        completed: function(value) {
            this.passwordRequirementsMet = value;
        },
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>