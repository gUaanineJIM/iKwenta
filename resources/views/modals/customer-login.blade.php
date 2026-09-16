<div class="modal" id="customer-login-modal" aria-hidden="true">
    <div class="modal-backdrop" data-modal-close></div>

    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="customer-login-title">

        <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>

        <span class="modal-badge">Customer</span>

        <h2 class="modal-title" id="customer-login-title">Customer Login</h2>

        <p class="modal-subtitle">
            Enter the 6-digit code we sent to your registered contact to view your debts, credited items, and payment history.
        </p>

        <form method="POST" action="{{ route('customer.login') }}" class="auth-form">

            @csrf

            <div class="form-field">
                <div class="code-input-row" data-code-input data-target="customer-code">
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1" required>
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 2" required>
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 3" required>
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 4" required>
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 5" required>
                    <input class="code-input" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 6" required>
                </div>

                <input type="hidden" name="code" id="customer-code">

                <p class="code-hint">
                    Only digits (0-9)
                </p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Verify & Sign In
                </button>
            </div>

            <div class="modal-switch">
                Looking for the owner portal?
                <a href="#" data-open-modal="owner-login-modal">Sign in as Owner</a>
            </div>

        </form>

    </div>
</div>