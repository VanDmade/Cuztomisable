<template>
    <div id="forgot-page" class="page">
        <div class="card ma-2 pa-6">
            <div class="d-flex">
                <img :src="$url+'cuztomisable/logo.png'" class="cz-authentication-logo">
                <div class="cz-title">
                    <h3 class="card-title">Forgot Password</h3>
                    <h6 class="card-subtitle mb-2 text-muted">Enter the email address associated with your account</h6>
                </div>
            </div>
            <fm-form ref="forgotForm" class="mt-4" :form="form"
                @save="save">
                <fm-input
                    :label="usernameLabel()"
                    v-model="form.username"
                    type="input"
                    :errors="errors.username"
                    :disabled="submitting" />
                <router-link :to="{ name: 'login' }" class="button--link">Remember password?</router-link>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Send</button>
                </div>
            </fm-form>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            form: {
                username: 'michaelvanderwerkerllc@gmail.com',
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
                this.$emit('message', { text: data.message });
                setTimeout(() => {
                    this.$router.push({ name: 'reset', params: { token: data.token }})
                }, 1500);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                }
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
    }
}
</script>