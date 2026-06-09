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

    announceStep() {
        // Move focus to the new step's heading so screen-reader and keyboard
        // users are taken to the right place. Deferred so the step is visible
        // (x-show) before we focus it.
        setTimeout(() => {
            const heading = this.$root.querySelector('[data-step="' + this.currentStep + '"] [data-step-heading]');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus({ preventScroll: false });
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

Alpine.start();
