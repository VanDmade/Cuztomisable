<template>
    <div id="mfa-form">
        <h3 class="card-title">Multi-Factor Auth</h3>
        <h6 class="card-subtitle mb-6 text-muted">Add two-step verification for extra security.</h6>
        <button v-if="!value"
            type="button"
            class="button button--primary button--block mb-0"
            :disabled="submitting"
            @click="toggle(true)">Enable</button>
        <button v-else
            type="button"
            class="button button--danger button--block mb-0"
            :disabled="submitting"
            @click="toggle(false)">Disable</button>
    </div>
</template>
<script>
function initialize() {
    return {
        submitting: false,
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
        toggle: function(value) {
            this.submitting = true;
            axios.patch(`/user/${this.user}/mfa`).then(({ data }) => {
                this.$message.success(data?.message || 'Multi-factor authentication updated successfully.');
                setTimeout(() => {
                    this.$emit('close');
                    setTimeout(() => {
                        this.value = !this.value;
                    }, 250);
                }, 1000);
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.danger(response.data.message);
                    return;
                }
                this.$message.danger('Unable to update multi-factor authentication right now.');
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1000);
            });
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    props: {
        modelValue: { type: Boolean, default: false },
        user: { type: [String, Number] }
    }
}
</script>