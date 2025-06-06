<template>
    <form :id="id" @submit.prevent="$emit('save')">
        <slot></slot>
        <fm-modal ref="fmFormModal">
            <h3 class="mb-8 mt-4 text-center">{{ leaveMessage }}</h3>
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--primary button--block"
                        :disabled="submitting"
                        @click="leave()">{{ yesMessage }}</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block"
                        :disabled="submitting"
                        @click="$refs.fmFormModal.close()">{{ noMessage }}</button>
                </div>
            </div>
        </fm-modal>
    </form>
</template>
<script>
export default {
    data: function() {
        return {
            id: 'fm-form_'+Math.random().toString(16).slice(2),
            dialog: true,
            submitting: false,
            next: null,
            to: null,
            from: null,
            // By setting it to true, it will ignore the leaving haults and will continue not matter what
            ignore: false,
        }
    },
    mounted: function() {
        if (this.loadProgressOnEntry) {
            this.get();
        }
    },
    methods: {
        get: function() {
            axios.get('/formora/'+this.$route.name).then(({ data }) => {
                let form = this.clone(this.form);
                if (data.form != null) {
                    let keys = Object.keys(data.form);
                    for (let i = 0; i < keys.length; i++) {
                        form[keys[i]] = data.form[keys[i]];
                    }
                    this.$emit('initialize', form);
                }
            });
        },
        // Saves the progress the user has made on the form
        save: function() {
            if (!this.$store.state.authenticated) {
                let formData = new FormData();
                formData.append('to', this.to.name);
                formData.append('to_params', JSON.stringify(this.to.params ?? []));
                formData.append('current', this.from.name);
                formData.append('current_params', JSON.stringify(this.from.params ?? []));
                let data = {};
                let keys = Object.keys(this.form);
                for (let i = 0; i < keys.length; i++) {
                    // Removes the password key
                    if (!this.doNotSave.includes(keys[i])) {
                        data[keys[i]] = this.form[keys[i]];
                    }
                }
                formData.append('form', JSON.stringify(data));
                axios.post('/formora/'+this.from.name, formData).then(({ data }) => {

                }).finally(() => {
                    this.redirect();
                });
            } else {
                // The user is not logged in, so no reason to save
                this.redirect();
            }
        },
        leave: function() {
            this.submitting = true;
            this.ignore = true;
            setTimeout(() => {
                this.submitting = false
            }, 500);
            this.$refs.fmFormModal.close();
            // Saves the form's progress in the formora database
            this.saveProgressBeforeLeaving ? this.save() : this.redirect();
        },
        redirect: function() {
            setTimeout(() => {
                // Redirects to the new location
                this.$router.push({ name: this.to.name, params: this.to.params ?? {} });
            }, 250);
        },
        leaving: function(to, from, next) {
            /* Add this function to the component that youd like to use the leaving method on
             * Add ref="nameOfForm" to the form in order to call the leaving method
             *
             * beforeRouteLeave: function(to, from, next) {
             *     this.$refs.nameOfForm.leaving(to, from, next);
             * }, */
            // Checks to see if the form is not going redirect without saving or confirmation
            if (this.ignore || (!this.askBeforeLeaving && !this.saveProgressBeforeLeaving)) {
                next();
            } else {
                // Creates the variables for use later on
                this.next = next;
                this.to = to;
                this.from = from;
                // Asks the user if they really want to leave the form
                if (this.askBeforeLeaving) {
                    // Stops the redirection
                    next(false);
                    this.$refs.fmFormModal.open();
                } else if (this.saveProgressBeforeLeaving) {
                    // Saves the progress
                    this.save();
                }
            }
        },
    },
    props: {
        form: { type: [Object, Array], default: {} },
        askBeforeLeaving: { type: Boolean, default: false },
        saveProgressBeforeLeaving: { type: Boolean, default: false },
        loadProgressOnEntry: { type: Boolean, default: false },
        leaveMessage: { type: String, default: 'Are you sure you want to leave?' },
        yesMessage: { type: String, default: 'Yes' },
        noMessage: { type: String, default: 'No' },
        doNotSave: { type: Array, default: ['password'] },
    }
}
</script>