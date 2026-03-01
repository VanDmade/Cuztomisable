<template>
    <div id="message-page">
        <div class="card ma-2 pa-6 text-center">
            <p class="mb-0">
                <span v-if="message == null">You're welcome to view this page, but it looks like it's not specifically intended for your role or current activity. Feel free to look around, or head back to your dashboard for tools tailored to you.</span>
                <span v-else>{{ message }}</span>
            </p>
        </div>
        <div class="text-center">
            <router-link :to="{ name: redirect.link }" class="links">{{ redirect.name }}</router-link>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            message: this.$route.query.m ?? null,
            redirect: this.$store.state.authenticated ?
                { name: 'Return to the portal', link: 'portal' } :
                { name: 'Return to the login screen', link: 'login' },
        }
    },
    created: function() {
        // Removes everything from the query string only when needed
        if (Object.keys(this.$route.query ?? {}).length > 0) {
            this.$router.replace({ path: this.$route.path, query: {} });
        }
    },
}
</script>