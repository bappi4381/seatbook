import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('seatMap', (config) => ({
    seats: config.seats || [],
    selectedIds: [],
    holdExpiresAt: config.holdExpiresAt || null,
    serverOffsetMs: new Date(config.serverNow).getTime() - Date.now(),
    message: '',
    error: '',
    holding: false,
    confirming: false,
    holdExpired: false,
    pollTimer: null,
    tickTimer: null,
    nowTs: Date.now(),
    isAttendee: !!config.isAttendee,
    holdUrl: config.holdUrl,
    seatsUrl: config.seatsUrl,
    bookingUrl: config.bookingUrl,
    loginUrl: config.loginUrl,
    csrf: config.csrf,
    maxSeats: config.maxSeats || 6,

    init() {
        this.tickTimer = setInterval(() => {
            this.nowTs = Date.now() + this.serverOffsetMs;
            if (this.holdExpiresAt && this.remainingSeconds() <= 0 && !this.holdExpired) {
                this.onHoldExpired();
            }
        }, 250);

        this.pollTimer = setInterval(() => this.refreshSeats(), 10000);
    },

    destroy() {
        clearInterval(this.tickTimer);
        clearInterval(this.pollTimer);
    },

    rows() {
        const grouped = {};
        this.seats.forEach((seat) => {
            if (!grouped[seat.row]) {
                grouped[seat.row] = [];
            }
            grouped[seat.row].push(seat);
        });

        return Object.keys(grouped)
            .sort()
            .map((row) => ({
                row,
                seats: grouped[row].sort((a, b) => a.number - b.number),
            }));
    },

    seatClass(seat) {
        if (this.selectedIds.includes(seat.id)) {
            return 'seat-btn bg-brand-700 text-white border-brand-800 ring-2 ring-brand-300';
        }

        switch (seat.state) {
            case 'held_by_me':
                return 'seat-btn bg-amber-400 text-amber-950 border-amber-500';
            case 'held_by_others':
                return 'seat-btn bg-orange-100 text-orange-900 border-orange-200 cursor-not-allowed opacity-80';
            case 'booked':
                return 'seat-btn bg-slate-400 text-white border-slate-500 cursor-not-allowed';
            default:
                return 'seat-btn bg-emerald-50 text-emerald-900 border-emerald-200 hover:bg-emerald-100';
        }
    },

    canSelect(seat) {
        return this.isAttendee && seat.state === 'available';
    },

    seatAriaLabel(seat) {
        return `Seat ${seat.label}, ${seat.state.replace(/_/g, ' ')}, ${Number(seat.price).toFixed(2)}`;
    },

    onSeatKeydown(event, seat) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            this.toggleSeat(seat);
        }
    },

    countdownClass() {
        return this.remainingSeconds() < 60
            ? 'text-rose-700'
            : 'text-amber-700';
    },

    toggleSeat(seat) {
        this.error = '';
        this.message = '';

        if (!this.isAttendee) {
            window.location.href = this.loginUrl;
            return;
        }

        if (!this.canSelect(seat) && !this.selectedIds.includes(seat.id)) {
            return;
        }

        if (this.selectedIds.includes(seat.id)) {
            this.selectedIds = this.selectedIds.filter((id) => id !== seat.id);
            return;
        }

        if (this.selectedIds.length >= this.maxSeats) {
            this.error = `You can select at most ${this.maxSeats} seats.`;
            return;
        }

        this.selectedIds.push(seat.id);
    },

    selectedSeats() {
        return this.seats.filter((seat) => this.selectedIds.includes(seat.id));
    },

    heldSeats() {
        return this.seats.filter((seat) => seat.state === 'held_by_me');
    },

    selectedTotal() {
        return this.selectedSeats().reduce((sum, seat) => sum + Number(seat.price), 0);
    },

    heldTotal() {
        return this.heldSeats().reduce((sum, seat) => sum + Number(seat.price), 0);
    },

    remainingSeconds() {
        if (!this.holdExpiresAt) {
            return 0;
        }

        return Math.max(0, Math.floor((new Date(this.holdExpiresAt).getTime() - this.nowTs) / 1000));
    },

    remainingLabel() {
        const total = this.remainingSeconds();
        const minutes = Math.floor(total / 60);
        const seconds = String(total % 60).padStart(2, '0');
        return `${minutes}:${seconds}`;
    },

    async holdSelected() {
        if (!this.isAttendee || this.selectedIds.length === 0 || this.holding) {
            return;
        }

        this.holding = true;
        this.error = '';
        this.message = '';

        try {
            const response = await fetch(this.holdUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ seat_ids: this.selectedIds }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                this.error = data.message || Object.values(data.errors || {}).flat()[0] || 'Could not hold seats.';
                await this.refreshSeats();
                return;
            }

            this.message = data.message || 'Seats held.';
            this.selectedIds = [];
            this.holdExpired = false;
            if (data.server_now) {
                this.serverOffsetMs = new Date(data.server_now).getTime() - Date.now();
            }
            this.holdExpiresAt = data.expires_at;
            await this.refreshSeats();
        } catch (e) {
            this.error = 'Network error while holding seats.';
        } finally {
            this.holding = false;
        }
    },

    async releaseHolds() {
        this.error = '';
        this.message = '';

        const response = await fetch(this.holdUrl, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': this.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            this.error = data.message || 'Could not release holds.';
            return;
        }

        this.message = data.message || 'Holds released.';
        this.holdExpiresAt = null;
        this.holdExpired = false;
        await this.refreshSeats();
    },

    confirmBooking() {
        if (this.holdExpired || this.remainingSeconds() <= 0 || this.confirming) {
            return;
        }

        this.confirming = true;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = this.bookingUrl;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = this.csrf;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    },

    async refreshSeats() {
        try {
            const response = await fetch(this.seatsUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (data.server_now) {
                this.serverOffsetMs = new Date(data.server_now).getTime() - Date.now();
                this.nowTs = Date.now() + this.serverOffsetMs;
            }

            this.seats = data.seats || [];

            const myHolds = this.heldSeats();
            if (myHolds.length > 0) {
                this.holdExpiresAt = myHolds
                    .map((seat) => seat.expires_at)
                    .filter(Boolean)
                    .sort()[0] || this.holdExpiresAt;
                this.holdExpired = false;
            } else if (this.holdExpiresAt && this.remainingSeconds() <= 0) {
                this.holdExpired = true;
                this.holdExpiresAt = null;
            }

            this.selectedIds = this.selectedIds.filter((id) => {
                const seat = this.seats.find((item) => item.id === id);
                return seat && seat.state === 'available';
            });
        } catch (e) {
            // Ignore transient poll failures.
        }
    },

    onHoldExpired() {
        this.holdExpired = true;
        this.holdExpiresAt = null;
        this.message = '';
        this.error = 'Your hold expired';
        this.refreshSeats();
    },
}));

Alpine.start();
