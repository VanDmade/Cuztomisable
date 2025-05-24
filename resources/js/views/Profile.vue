<template>
    <div id="profile-page">
        <div class="card pa-6" style="max-width: 600px; margin: auto;">
            <div class="d-flex align-items-center position-relative">
                <div class="position-relative me-3 fm-image-input" style="height: 100px; width: 100px;">
                    <label for="fm-image-upload" class="position-relative me-3 fm-image-input" style="cursor: pointer; height: inherit; width: inherit;">
                        <fm-image :src="preview || user?.image"
                            alt="User Photo"
                            img-class="rounded-circle fm-image" />
                        <span class="position-absolute fm-image-toggle translate-middle">✎</span>
                        <input
                            type="file"
                            id="fm-image-upload"
                            class="d-none"
                            accept="image/*"
                            @change="handleChange" />
                    </label>
                </div>
                <div>
                    <h4 class="mb-0">{{ user?.name }}</h4>
                    <small class="text-muted">{{ user?.email }}</small>
                </div>
            </div>
            <fm-form ref="profileForm" :form="form"
                @save="save"
                ask-before-leaving
                save-progress-before-leaving
                load-progress-on-entry>
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
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Save Changes</button>
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
            preview: this.$url+'cuztomisable/profile.png',
            user: this.$store.state.user,
            form: {},
        }
    },
    methods: {
        save: function() {
            let formData = new FormData();
            formData.append('name', this.form.name ?? '');
            formData.append('image', this.form.image ?? '');
            if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
                formData.append('username', this.form.username ?? '');
            }
            formData.append('email', this.form.email ?? '');
            formData.append('phone', this.form.phone.number ?? '');
            formData.append('country_code', this.form.phone.country_code ?? '');
            formData.append('password', this.form.password ?? '');
            if (this.$cuztomisable.registration.address !== false) {
                formData.append('address', this.form.address.address);
                formData.append('address_two', this.form.address.address_two);
                formData.append('address_three', this.form.address.address_three);
                formData.append('city', this.form.address.city);
                formData.append('state_or_province', this.form.address.state_or_province);
                formData.append('zip_or_postal_code', this.form.address.zip_or_postal_code);
                formData.append('country', this.form.address.country);
            }
            this.submitting = true;
            axios.post('/user', formData).then(({ data }) => {
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
        handleChange: function(event) {
            const file = event.target.files[0]
            if (file) {
                this.form.image = file;
                this.preview = URL.createObjectURL(file);
            }
        }
    },
    watch: {
        '$store.state.user': {
            immediate: true,
            handler: function(user) {
                this.user = user;
                if (user == null) {
                    return;
                }
                if (user.address == null) {
                    user.address = this.form.address;
                }
                this.form = this.clone(user);
            },
            deep: true,
        }
    }
}
</script>
<style lang="scss">
    #profile-page {
        .card {
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #30D5C8;
            box-shadow: 0 0 0 0.2rem rgba(48, 213, 200, 0.25);
        }
        .btn-primary {
            background-color: #30D5C8;
            border-color: #30D5C8;
            &:hover {
                background-color: darken(#30D5C8, 5%);
                border-color: darken(#30D5C8, 5%);
            }
        }
    }
</style>