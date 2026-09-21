<div class="modal" id="owner-login-modal" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="owner-login-title">

        <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>

        <span class="modal-badge">Owner</span>

        <h2 class="modal-title" id="owner-login-title">Owner Login</h2>

        <p class="modal-subtitle">
            Welcome back! Sign in to manage your products, customers, debts, payments, and audit logs.
        </p>

        <form method="POST" action="{{ route('owner.login.submit') }}" class="auth-form">

            @csrf

            <div class="form-field">
                <label class="form-label" for="owner-email">Username</label>
                <input
                    class="form-input"
                    id="owner-email"
                    name="username"
                    type="text"
                    autocomplete="username"
                    placeholder="Enter your username"
                    required
                >
            </div>

            <div class="form-field password-field">
                <label class="form-label" for="owner-password">Password</label>
                <input
                    class="form-input"
                    id="owner-password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    required
                >
                <button type="button" class="password-toggle" data-password-toggle="owner-password" aria-label="Toggle password visibility">
                    <span data-password-icon>👁</span>
                </button>
            </div>

            <div class="form-row-between">
                <label class="remember-label">
                    <input type="checkbox" name="remember" class="remember-check">
                    <span>Remember me</span>
                </label>

                <button type="button" class="forgot-link" data-open-modal="forgot-modal">
                    Forgot password?
                </button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Sign In
                </button>
            </div>

            <div class="modal-switch">
                Not an owner? 
                <a href="#" data-open-modal="customer-login-modal">Sign in as Customer</a>
            </div>

        </form>

    </div>
</div>