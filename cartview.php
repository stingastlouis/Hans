<div id="cart-container" class="position-fixed top-0 end-0 bg-white border shadow p-3 m-3 rounded">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Cart</h5>
        <button id="close-cart" style="background-color:transparent; border: none; width:30px">✕</button>
    </div>

    <ul id="cart-items" class="list-group mb-2"></ul>

    <div class="d-flex justify-content-between mb-2">
        <strong>Total:</strong>
        <span id="cart-total">Rs 0.00</span>
    </div>

    <button id="checkout-button" class="btn btn-success w-100">Checkout</button>
</div>

<style>
#cart-container {
  display: none;
  position: fixed;
  bottom: 10px;
  right: 10px;
  max-width: 300px;
  width: 100%;
  max-height: 400px;
  background-color: white;
  border-radius: 10px;
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  overflow-y: auto;
  padding: 20px;
}
</style>

<script src="./cart/cart.js"></script>
