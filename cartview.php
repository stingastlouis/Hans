<div id="cart-container" class="position-fixed bottom-0 end-0 bg-white border shadow-lg p-4 m-3 rounded-4" style="display: none; max-width: 320px; width: 100%; max-height: 420px; z-index: 1050; overflow-y: auto;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Your Cart</h5>
        <button id="close-cart" class="btn-close" aria-label="Close"></button>
    </div>

    <ul id="cart-items" class="list-group list-group-flush mb-3"></ul>

    <div class="d-flex justify-content-between border-top pt-2 mb-3">
        <span class="fw-semibold">Total:</span>
        <span id="cart-total" class="fw-bold text-success">$ 0.00</span>
    </div>

    <button id="checkout-button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
        <i class="bi bi-credit-card"></i> Checkout
    </button>
</div>

<script src="./cart/cart.js"></script>