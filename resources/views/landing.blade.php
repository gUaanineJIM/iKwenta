@extends('layouts.app')

@section('title', 'Welcome')

@section('content')

    <section class="landing">

        {{-- =========================
        NAVIGATION
        ========================== --}}

        <nav class="navbar">
            <div class="container navbar-inner">

                <a href="/" class="logo">
                    iKwenta<span>.</span>
                </a>

                <div class="nav-actions">

                    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
                        <span class="icon-sun" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="4"></circle>
                                <path d="M12 2v2"></path>
                                <path d="M12 20v2"></path>
                                <path d="m4.93 4.93 1.41 1.41"></path>
                                <path d="m17.66 17.66 1.41 1.41"></path>
                                <path d="M2 12h2"></path>
                                <path d="M20 12h2"></path>
                                <path d="m6.34 17.66-1.41 1.41"></path>
                                <path d="m19.07 4.93-1.41 1.41"></path>
                            </svg>
                        </span>

                        <span class="icon-moon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3
                         7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </span>
                    </button>

                    <a href="#login" class="nav-link btn-text">
                        Login
                    </a>

                </div>

            </div>
        </nav>


        {{-- =========================
        HERO SECTION
        ========================== --}}

        <section class="hero">

            <div class="hero-background"></div>
            <div class="hero-agora" style="top: 12%; left: -120px;"></div>
            <div class="hero-agora" style="bottom: -90px; right: -110px; animation-direction: reverse;"></div>

            <div class="container hero-content">

                <div class="hero-text">

                    <span class="eyebrow">
                        iKwenta · Debt & Store Management
                    </span>

                    <h1>
                        Run your store,
                        <span>track your debts.</span>
                    </h1>

                    <p>
                        Manage products, customers, debts, payments, and transactions
                        from one centralized platform — with full transparency
                        for you and your customers.
                    </p>

                    <a href="#login" class="btn btn-primary">
                        Get Started
                    </a>

                </div>

                <div class="hero-image-wrapper">

                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1000&q=80"
                        alt="Store counter with receipt and ledger" class="hero-image">

                    <div class="floating-card">
                        <div class="floating-icon">
                            ✓
                        </div>

                        <div>
                            <strong>Tracked & Transparent</strong>
                            <small>Debts, payments & audit logs</small>
                        </div>
                    </div>

                </div>

            </div>

        </section>


        {{-- =========================
        LOGIN SECTION
        ========================== --}}

        <section class="login-section" id="login">

            <div class="container">

                <div class="section-heading">

                    <span class="eyebrow">
                        Secure access
                    </span>

                    <h2>
                        How would you like to sign in?
                    </h2>

                    <p>
                        Owners manage the store, products, debts, and payments —
                        customers check their balances, credited items, and payment history.
                    </p>

                </div>


                <div class="login-grid">

                    {{-- CUSTOMER --}}

                    <x-login-card title="Customer Account"
                        description="View your outstanding debts, credited items, remaining balance, and payment history in real time."
                        button-text="View My Account" modal-target="customer-login-modal"
                        image="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=900&q=80"
                        type="Customer" />


                    {{-- OWNER --}}

                    <x-login-card title="Owner Portal"
                        description="Manage products, customers, debts, payments, and transactions — with complete activity and audit logs."
                        button-text="Manage Store" modal-target="owner-login-modal"
                        image="https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80"
                        type="Owner" />

                </div>

            </div>

        </section>


        {{-- =========================
        FOOTER
        ========================== --}}

        <footer class="footer">

            <div class="container">

                <p>
                    © {{ date('Y') }} iKwenta — Debt & Store Management System. All rights reserved.
                </p>

            </div>

        </footer>

    </section>

@endsection