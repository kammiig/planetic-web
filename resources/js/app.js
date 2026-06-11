import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

window.Alpine = Alpine;

// focus → accessible modal focus-trapping (x-trap)
// collapse → smooth, accessible accordion sections (x-collapse)
Alpine.plugin(focus);
Alpine.plugin(collapse);

/*
|--------------------------------------------------------------------------
| On-site checkout (Stripe Payment Element)
|--------------------------------------------------------------------------
| Drives the multi-step checkout and takes payment on the page itself — the
| customer is never redirected to a hosted Stripe page. The backend creates a
| PaymentIntent and returns only its client_secret; the secret key stays server
| side. Registered before Alpine.start() so the component is always available.
*/
Alpine.data('checkout', (config = {}) => ({
    steps: config.steps || ['review', 'billing', 'payment'],
    step: 0,
    intentUrl: config.intentUrl,
    successUrl: config.successUrl,
    publishableKey: config.publishableKey || '',
    stripe: null,
    elements: null,
    clientSecret: null,
    orderNumber: null,
    initialising: false,
    paying: false,
    paymentReady: false,
    formError: '',
    fieldErrors: {},

    get currentStep() {
        return this.steps[this.step];
    },
    isActive(name) {
        return this.currentStep === name;
    },
    isDone(name) {
        return this.steps.indexOf(name) < this.step;
    },
    stepNumber(name) {
        return this.steps.indexOf(name) + 1;
    },
    fieldError(name) {
        return (this.fieldErrors[name] && this.fieldErrors[name][0]) || '';
    },

    next() {
        if (this.step < this.steps.length - 1) {
            this.step++;
            this.announceStep();
        }
    },
    back() {
        if (this.step > 0) {
            this.step--;
            this.formError = '';
            this.announceStep();
        }
    },
    goReview() {
        this.step = 0;
        this.announceStep();
    },
    goTo(index) {
        // Allow jumping back to an already-completed step (never skipping ahead).
        if (index >= 0 && index <= this.step) {
            this.step = index;
            this.formError = '';
            this.announceStep();
        }
    },

    announceStep() {
        // Move focus to the newly-revealed step panel (labelled via aria-label)
        // so screen-reader and keyboard users are taken to the right place and
        // focus never gets stranded on a now-hidden element. Deferred so the
        // panel is visible (x-show) before we focus it.
        setTimeout(() => {
            const panel = this.$root.querySelector('[data-step="' + this.currentStep + '"]');
            if (panel) {
                panel.focus({ preventScroll: false });
            }
        }, 60);
    },

    csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    /** Billing → Payment: validate server-side, create the intent, mount the element. */
    async continueToPayment() {
        this.formError = '';
        this.fieldErrors = {};
        this.initialising = true;

        try {
            const response = await fetch(this.intentUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(this.$refs.billingForm),
            });

            if (response.status === 422) {
                const body = await response.json();
                this.fieldErrors = body.errors || {};
                this.formError = body.message || body.error || 'Please check the highlighted fields and try again.';
                this.focusFirstError();
                return;
            }

            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                this.formError = body.error || 'We could not start secure payment. Please try again in a moment.';
                return;
            }

            const body = await response.json();
            this.clientSecret = body.client_secret;
            this.orderNumber = body.order_number;
            if (body.publishable_key) {
                this.publishableKey = body.publishable_key;
            }

            await this.mountPaymentElement();
            this.next();
        } catch (error) {
            this.formError = 'Network error — please check your connection and try again.';
        } finally {
            this.initialising = false;
        }
    },

    async mountPaymentElement() {
        if (!window.Stripe) {
            this.formError = 'The secure payment library failed to load. Please refresh the page and try again.';
            throw new Error('Stripe.js not available');
        }
        if (!this.publishableKey) {
            this.formError = 'Payment is not configured. Please contact support.';
            throw new Error('Missing Stripe publishable key');
        }

        if (!this.stripe) {
            this.stripe = window.Stripe(this.publishableKey);
        }

        this.elements = this.stripe.elements({
            clientSecret: this.clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#2563eb',
                    colorText: '#0f172a',
                    borderRadius: '10px',
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
                },
            },
        });

        const paymentElement = this.elements.create('payment', { layout: 'tabs' });
        this.$refs.paymentElement.innerHTML = '';
        paymentElement.mount(this.$refs.paymentElement);
        this.paymentReady = true;
    },

    /** Confirm payment on-site. redirect:'if_required' keeps simple cards on-page. */
    async pay() {
        if (!this.stripe || !this.elements) {
            return;
        }
        this.paying = true;
        this.formError = '';

        const { error, paymentIntent } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: { return_url: this.successUrl },
            redirect: 'if_required',
        });

        if (error) {
            this.formError = error.message || 'Your payment could not be completed. Please check your details and try again.';
            this.paying = false;
            return;
        }

        if (paymentIntent && (paymentIntent.status === 'succeeded' || paymentIntent.status === 'processing')) {
            const separator = this.successUrl.includes('?') ? '&' : '?';
            window.location.assign(
                this.successUrl + separator + 'payment_intent=' + encodeURIComponent(paymentIntent.id)
                + '&redirect_status=' + paymentIntent.status
            );
            return;
        }

        this.formError = 'Your payment is pending confirmation. If you are not redirected shortly, please check your dashboard.';
        this.paying = false;
    },

    focusFirstError() {
        this.$nextTick(() => {
            const first = Object.keys(this.fieldErrors)[0];
            if (first && this.$refs.billingForm) {
                const el = this.$refs.billingForm.querySelector('[name="' + first + '"]');
                if (el) el.focus();
            }
        });
    },
}));

/*
|--------------------------------------------------------------------------
| Inline checkout authentication
|--------------------------------------------------------------------------
| Lets a guest create an account or sign in WITHOUT leaving the checkout.
| On success the page reloads: the session cart survives authentication
| (cart_id is kept through session regeneration and claimed by the user),
| so the customer lands back on checkout at the next step, signed in.
*/
Alpine.data('checkoutAuth', (config = {}) => ({
    mode: config.mode || 'register', // 'register' | 'login'
    registerUrl: config.registerUrl,
    loginUrl: config.loginUrl,
    submitting: false,
    formError: '',
    fieldErrors: {},

    switchTo(mode) {
        this.mode = mode;
        this.formError = '';
        this.fieldErrors = {};
    },
    fieldError(name) {
        return (this.fieldErrors[name] && this.fieldErrors[name][0]) || '';
    },
    csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    async submit(form, url) {
        this.formError = '';
        this.fieldErrors = {};
        this.submitting = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            if (response.status === 422) {
                const body = await response.json();
                this.fieldErrors = body.errors || {};
                this.formError = body.message || 'Please check the highlighted fields and try again.';
                this.focusFirstError(form);
                return;
            }

            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                this.formError = body.message || body.error || 'Something went wrong. Please try again in a moment.';
                return;
            }

            // Signed in — reload checkout. The session cart is preserved, the
            // server now renders the authenticated steps (plan → billing → pay).
            window.location.reload();
        } catch (error) {
            this.formError = 'Network error — please check your connection and try again.';
        } finally {
            this.submitting = false;
        }
    },

    submitRegister() {
        this.submit(this.$refs.registerForm, this.registerUrl);
    },
    submitLogin() {
        this.submit(this.$refs.loginForm, this.loginUrl);
    },

    focusFirstError(form) {
        this.$nextTick(() => {
            const first = Object.keys(this.fieldErrors)[0];
            if (first && form) {
                const el = form.querySelector('[name="' + first + '"]');
                if (el) el.focus();
            }
        });
    },
}));

/*
|--------------------------------------------------------------------------
| Domain search page
|--------------------------------------------------------------------------
| Drives the full domain-search experience (exact match + bundle cards +
| "more options" list). Calls only the Laravel search endpoint (never the
| registrar directly) and adds results to the cart server-side.
*/
Alpine.data('domainSearch', (config = {}) => ({
    query: config.initialQuery || '',
    searchUrl: config.searchUrl,
    cartUrl: config.cartUrl,
    cartIndexUrl: config.cartIndexUrl,
    websitePackagePrice: config.websitePackagePrice || 200,
    loading: false,
    error: '',
    result: null,
    alternatives: [],
    sort: 'popularity',
    adding: null,

    get bundleDomain() {
        if (this.result && this.result.available) return this.result.domain;
        if (this.alternatives.length) return this.alternatives[0].domain;
        return this.result ? this.result.domain : '';
    },
    get sortedAlternatives() {
        const list = [...this.alternatives];
        if (this.sort === 'price-asc') return list.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
        if (this.sort === 'price-desc') return list.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
        return list;
    },
    csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    },

    async search() {
        const domain = (this.query || '').trim().toLowerCase();
        this.error = '';
        if (!domain) { this.error = 'Please enter a domain name to search.'; return; }

        this.loading = true;
        this.result = null;
        this.alternatives = [];
        try {
            const res = await fetch(this.searchUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify({ domain, full: true }),
            });
            const data = await res.json();
            if (!res.ok || data.success === false) {
                this.error = data.message || 'We could not check this domain right now. Please try again in a few moments.';
            } else {
                this.result = data;
                this.alternatives = data.alternatives && data.alternatives.length ? data.alternatives : (data.suggestions || []);
            }
        } catch (e) {
            this.error = 'We could not check this domain right now. Please try again in a few moments.';
        } finally {
            this.loading = false;
        }
    },

    async add(itemType, domain, key) {
        this.adding = key;
        this.error = '';
        try {
            const res = await fetch(this.cartUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                body: JSON.stringify({ item_type: itemType, domain_name: domain }),
            });
            if (res.ok) {
                window.location = this.cartIndexUrl;
                return;
            }
            const body = await res.json().catch(() => ({}));
            const firstError = body.errors ? Object.values(body.errors)[0] : null;
            this.error = (firstError && firstError[0]) || body.message || 'We could not add that to your cart. Please try again.';
        } catch (e) {
            this.error = 'We could not add that to your cart. Please try again.';
        } finally {
            this.adding = null;
        }
    },
}));

Alpine.start();
