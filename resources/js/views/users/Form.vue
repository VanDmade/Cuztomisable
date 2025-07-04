<template>
    <div class="page" :class="{ 'container': breakpoint('lg'), 'container-fluid': breakpoint('md') || breakpoint('sm') }">
        <fm-loading :loading="loading || $store.state.loading"></fm-loading>
        <fm-form v-if="!loading" ref="userForm" :form="form" @save="save">
            <div class="row mb-4">
                <div class="col-lg-9 col-md-8 col-sm-12" :class="{ 'mb-6': breakpoint('sm') }">
                    <div class="card pa-6">
                        <fm-input
                            label="Name"
                            v-model="form.name"
                            type="text"
                            :errors="errors.name"
                            :disabled="submitting" />
                        <fm-input
                            v-if="!$cuztomisable.login_with.email && !$cuztomisable.login_with.phone"
                            label="Username"
                            v-model="form.username"
                            type="text"
                            :errors="errors.username"
                            :disabled="submitting" />
                        <fm-input
                            label="Email"
                            v-model="form.email"
                            type="email"
                            :errors="errors.email"
                            :disabled="submitting" />
                        <fm-phone
                            label="Phone"
                            v-model="form.phone"
                            :errors="errors.phone"
                            :disabled="submitting"
                            is-mobile
                            default />
                        <fm-address
                            v-if="$cuztomisable.registration.address !== false"
                            label="Address"
                            v-model="form.address"
                            :errors="errors.address"
                            :disabled="submitting"
                            :hasAddressTwo="$cuztomisable.registration.address.address_two"
                            :hasAddressThree="$cuztomisable.registration.address.address_three" />
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-12">
                    <div class="card pa-6">
                        <fm-image
                            :src="imageUrl"
                            alt="User Image"
                            img-class="fm-image img-fluid w-100"
                            v-model="form.image"
                            uploader />
                    </div>
                    <div class="form-buttons">
                        <button
                            type="button"
                            class="button button--accent button--block mb-2"
                            @click="$refs.mfaModal.open();"
                            :disabled="submitting">Multi-Factor Auth</button>
                        <button
                            type="button"
                            class="button button--accent button--block mb-2"
                            @click="$refs.passwordModal.open();"
                            :disabled="submitting">Change Password</button>
                        <button
                            type="button"
                            class="button button--accent button--block mb-0"
                            @click="$refs.recentLoginModal.open();"
                            :disabled="submitting">Recent Logins</button>
                    </div>
                </div>
            </div>
            <hr v-if="breakpoint('sm')" class="mb-6">
            <div class="form-buttons" :class="{ 'mb-6': !breakpoint('sm'), 'mb-2': breakpoint('sm') }">
                <button type="submit" class="button button--primary" :class="{ 'button--block': breakpoint('sm'), 'mr-4': !breakpoint('sm') }" :disabled="submitting">Save Changes</button>
                <button type="button" class="button button--secondary" :class="{ 'button--block': breakpoint('sm')}" @click="goBack()" :disabled="submitting">Go Back</button>
            </div>
        </fm-form>
        <fm-modal ref="mfaModal" v-on:open="$refs['user-mfa-form'].reset()" modal-width="340px">
            <component
                is="user-mfa-form"
                ref="user-mfa-form"
                v-model="form.mfa"
                v-on:close="$refs.mfaModal.close();"
                v-on:message="message"
                :user="form?.id"></component>
        </fm-modal>
        <fm-modal ref="passwordModal" v-on:open="$refs['user-password-form'].reset()" modal-width="425px">
            <component
                is="user-password-form"
                ref="user-password-form"
                v-on:close="$refs.passwordModal.close();"
                v-on:message="message"
                :user="form?.id"
                :admin="$store.state.user?.admin"></component>
        </fm-modal>
        <fm-modal ref="recentLoginModal" v-on:open="$refs['user-login-form']?.reset()" modal-width="575px">
            <component
                v-if="form.id"
                is="user-login-form"
                ref="user-login-form"
                v-on:close="$refs.recentLoginModal.close();"
                v-on:message="message"
                :user="form?.id"
                :admin="$store.state.user?.admin"></component>
        </fm-modal>
    </div>
</template>
<script>
import { ref, computed } from 'vue';
import Password from './Password.vue';
import MFA from './MFA.vue';
import RecentLogin from './RecentLogin.vue';
export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            errors: [],
            imageUrl: this.$url+'cuztomisable/profile.png',
            form: {}
        };
    },
    methods: {
        get: function() {
            let id = this.$route.params.id;
            axios.get(`/user/${id}`).then(({ data }) => {
                this.form = this.clone(data.user);
            }).catch((error) => {

            }).finally(() => {
                setTimeout(() => {
                    this.loading = false;
                }, 1000);
            });
        },
        save: function() {
            let id = this.$route.params.id;
            let formData = new FormData();
            formData.append('name', this.form.name ?? '');
            formData.append('image', this.form.image ?? '');
            if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
                formData.append('username', this.form.username ?? '');
            }
            formData.append('email', this.form.email ?? '');
            formData.append('phone', this.form.phone?.number ?? '');
            formData.append('country_code', this.form.phone?.country_code ?? '');
            if (this.$cuztomisable.registration.address !== false) {
                formData.append('address', this.form.address?.address);
                formData.append('address_two', this.form.address?.address_two);
                formData.append('address_three', this.form.address?.address_three);
                formData.append('city', this.form.address?.city);
                formData.append('state_or_province', this.form.address?.state_or_province);
                formData.append('zip_or_postal_code', this.form.address?.zip_or_postal_code);
                formData.append('country', this.form.address?.country);
            }
            this.submitting = true;
            formData = this.cleanFormData(formData);
            axios.post(`/user/${id}`, formData).then(({ data }) => {
                this.$emit('message', { text: data.message });
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                    this.errors['address'] = {
                        address: this.errors['address'] ?? '',
                        address_two: this.errors['address_two'] ?? '',
                        address_three: this.errors['address_three'] ?? '',
                        city: this.errors['city'] ?? '',
                        state_or_province: this.errors['state_or_province'] ?? '',
                        zip_or_postal_code: this.errors['zip_or_postal_code'] ?? '',
                        country: this.errors['country'] ?? '',
                    };
                }
                if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        message: function(message) {
            this.$emit('message', message);
        },
        handleUserChange: function(user) {
            if (typeof(this.form.id) == 'undefined') {
                let id = this.$route.params.id;
                if (id == '' || typeof(id) == 'undefined') {
                    this.form = user ?? {};
                    setTimeout(() => {
                        this.loading = false;
                    }, 1000);
                } else {
                    this.get();
                }
            }
        }
    },
    watch: {
        '$store.state.user': {
            handler: function(user) {
                this.handleUserChange(user);
            }
        },
        '$route.params.id': {
            immediate: true,
            handler: function(id) {
                this.errors = [];
                this.loading = true;
                let user = {};
                if (this.form.id != id) {
                    this.form = {};
                }
                if (typeof(id) == 'undefined') {
                    user = this.$store.state.user;
                } else {
                    this.form = {};
                }
                this.handleUserChange(user);
            }
        }
    },
    props: {
        id: { type: [String, Number], default: null },
    },
    components: {
        'user-login-form': RecentLogin,
        'user-mfa-form': MFA,
        'user-password-form': Password,
    }
};
</script>
<style>

</style>