<template>
    <div id="invite-form">
        <fm-form ref="inviteForm" :form="form" @save="save">
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
                <div class="col col-md-8 col-12">
                    <div class="button-group">
                        <button type="submit"
                            @click="save"
                            class="button button--primary button--block mb-0"
                            :class="{ 'mb-2': breakpoint('sm') }"
                            :disabled="submitting">Invite</button>
                        <button type="submit"
                            @click="save(true)"
                            class="button button--ternary button--block mb-0"
                            :class="{ 'mb-2': breakpoint('sm') }"
                            :disabled="submitting">Another</button>
                    </div>
                </div>
                <div class="col col-md-4 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="close">Nevermind</button>
                </div>
            </div>
        </fm-form>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            form: {},
        };
    },
    methods: {
        save: function(another = false) {
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
                this.$emit('redraw');
                if (another) {
                    this.form = {};
                    this.errors = [];
                } else {
                    setTimeout(() => {
                        this.close();
                    }, 1400);
                }
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
    },
}
</script>