<template>
    <div class="page container-fluid">
        <div class="card ma-2 pa-6">
            <div class="portal-hero mb-6">
                <h2 class="portal-hero__title">Welcome to <span class="color--primary">Cuztomisable</span></h2>
                <p class="portal-hero__sub">A production-ready Laravel + Vue application framework — engineered to eliminate repetitive scaffolding so you ship what actually matters.</p>
            </div>
            <div class="portal-features mb-6">
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">lock</span>
                    <div>
                        <h5 class="portal-feature__title">Authentication Pipeline</h5>
                        <p class="portal-feature__desc">Full login, logout, registration, password reset, and multi-factor authentication — wired up and secured out of the box. Sessions are managed server-side with cookie-based tokens, with optional mobile agent support baked in.</p>
                    </div>
                </div>
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">admin_panel_settings</span>
                    <div>
                        <h5 class="portal-feature__title">Role-Based Access Control</h5>
                        <p class="portal-feature__desc">Granular permissions let you define exactly who can see and do what. Roles are assigned to users, permissions are assigned to roles — giving you clean, auditable control over every part of your application.</p>
                    </div>
                </div>
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">group</span>
                    <div>
                        <h5 class="portal-feature__title">User & Invite Management</h5>
                        <p class="portal-feature__desc">A full user management suite — create, edit, and manage users without writing a single line of backend code. Invite flows handle onboarding via email, so new users land directly into the right role.</p>
                    </div>
                </div>
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">table_chart</span>
                    <div>
                        <h5 class="portal-feature__title">Dynamic Data Tables</h5>
                        <p class="portal-feature__desc">A configurable table engine that handles sorting, filtering, and pagination at the API level. Drop it into any view, point it at an endpoint, and your data is immediately browsable — no custom table logic required.</p>
                    </div>
                </div>
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">dynamic_form</span>
                    <div>
                        <h5 class="portal-feature__title">Component Form Library</h5>
                        <p class="portal-feature__desc">A suite of pre-built form components — inputs, selects, multi-selects, file uploads, phone fields, and more — all consistently styled and validation-ready. Forms that used to take hours take minutes.</p>
                    </div>
                </div>
                <div class="portal-feature">
                    <span class="material-icons portal-feature__icon">api</span>
                    <div>
                        <h5 class="portal-feature__title">Session-Based API Layer</h5>
                        <p class="portal-feature__desc">Routes are structured under a dedicated API layer with session-aware middleware — meaning your frontend and mobile clients authenticate through the same stack without separate token infrastructure.</p>
                    </div>
                </div>
            </div>
            <div class="portal-footer">
                <p class="portal-footer__text">Built by <strong>Michael VanDerwerker</strong> — designed for agencies, freelancers, and product teams who want a secure, scalable foundation without the setup overhead. The scaffolding is done. Build the thing.</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data: function() {
        return {}
    },
}
</script>
