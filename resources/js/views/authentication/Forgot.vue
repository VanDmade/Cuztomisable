<template>
    <div id="forgot-page" class="page auth-page">
        <div class="auth-card">
            <div class="auth-card__header">
                <img :src="$url+'logo.png'" class="auth-card__logo">
                <h1 class="auth-card__title">Forgot Password</h1>
                <p class="auth-card__subtitle">Enter the email address associated with your account</p>
            </div>
            <cz-form ref="forgotForm" class="auth-card__form" :form="form"
                @save="save">
                <cz-input
                    :label="usernameLabel()"
                    v-model="form.username"
                    type="input"
                    :errors="errors.username"
                    :disabled="submitting" />
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Send</button>
                </div>
            </cz-form>
        </div>
        <p class="auth-card__footer">
            <router-link :to="{ name: 'login' }" class="button--link">Remember password?</router-link>
        </p>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            form: {
                username: '',
            },
        }
    },
    methods: {
        save: function() {
            this.submitting = true;
            this.errors = [];
            var formData = new FormData();
            formData.append('username', this.form.username ?? '');
            axios.post('/password/forgot', formData).then(({ data }) => {
                const payload = data?.data ?? data ?? {};
                const token = payload.token ?? payload.reset_token ?? payload.password_reset_token ?? null;
                const message = payload.message ?? data?.message ?? 'Reset code sent successfully.';
                this.$notify.success(message);
                if (!token) {
                    this.$notify.error('Unable to continue to password reset. Please try again.');
                    this.submitting = false;
                    return;
                }
                setTimeout(() => {
                    this.$router.push({ path: `/reset/${encodeURIComponent(String(token))}` });
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$notify.error(response.data.message);
                }
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
    }
}
</script>