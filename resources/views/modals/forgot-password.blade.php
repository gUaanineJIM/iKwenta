<div class="modal" id="forgot-modal" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="forgot-title">

        <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>

        <span class="modal-badge">Reset password</span>

        <h2 class="modal-title" id="forgot-title">Forgot password?</h2>

        <p class="modal-subtitle">
            No worries — enter your username and we'll send you a reset link.
        </p>

        <form method="POST" action="#" class="auth-form">

            @csrf

            <div class="form-field">
                <label class="form-label" for="forgot-username">Username</label>
                <input
                    class="form-input"
                    id="forgot-username"
                    name="username"
                    type="text"
                    autocomplete="username"
                    placeholder="Enter your username"
                    required
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Send Reset Link
                </button>
            </div>

            <div class="modal-switch">
                Remembered it?
                <a href="#" data-open-modal="owner-login-modal">Back to Sign In</a>
            </div>

        </form>

    </div>
</div>