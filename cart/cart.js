const cartKey = "user-cart";

function loadCart() {
  const cartData = localStorage.getItem(cartKey);
  return cartData ? JSON.parse(cartData) : [];
}

function saveCart(cart) {
  localStorage.setItem(cartKey, JSON.stringify(cart));
}

function addToCart(id, name, price, quantity, type) {
  const cart = loadCart();
  const existingItem = cart.find(
    (item) => item.id === id && item.type === type
  );

  if (existingItem) {
    existingItem.quantity += quantity;
  } else {
    cart.push({ id, name, price, quantity, type });
  }

  saveCart(cart);
  updateCartUI(cart);
}

function removeFromCart(id, type) {
  let cart = loadCart();
  cart = cart.filter((item) => !(item.id === id && item.type === type));
  saveCart(cart);
  updateCartUI(cart);
}

function updateCartUI(cart) {
  const cartContainer = document.getElementById("cart-container");
  const cartItems = document.getElementById("cart-items");
  const cartTotal = document.getElementById("cart-total");

  if (!cart) cart = loadCart();

  cartItems.innerHTML = "";

  let total = 0;
  cart.forEach((item) => {
    const itemTotal = item.price * item.quantity;
    total += itemTotal;

    const listItem = document.createElement("li");
    listItem.classList.add(
      "list-group-item",
      "d-flex",
      "justify-content-between",
      "align-items-center"
    );
    listItem.innerHTML = `
            <div>
                <strong>${item.name} (${item.type})</strong><br>
                Rs ${item.price.toFixed(2)} x ${item.quantity}
            </div>
            <div>
                <span>Rs ${itemTotal.toFixed(2)}</span>
                <button class="btn btn-danger btn-sm remove-from-cart" data-id="${
                  item.id
                }" data-type="${item.type}">Remove</button>
            </div>
        `;
    cartItems.appendChild(listItem);
  });

  cartTotal.textContent = `Rs ${total.toFixed(2)}`;

  cartContainer.style.display = cart.length > 0 ? "block" : "none";
}

function initCart() {
  updateCartUI();

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("add-to-cart")) {
      const button = event.target;
      const id = button.getAttribute("data-id");
      const name = button.getAttribute("data-name");
      const price = parseFloat(button.getAttribute("data-price"));
      const type = button.getAttribute("data-type");
      const stock = parseInt(button.getAttribute("data-stock"));
      const quantityInput = button
        .closest(".d-flex")
        .querySelector(".quantity-input");
      const quantity = parseInt(quantityInput.value);

      if (quantity > 0 && quantity <= stock) {
        addToCart(id, name, price, quantity, type);
      } else {
        alert("Invalid quantity!");
      }
    }
  });

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("remove-from-cart")) {
      const id = event.target.getAttribute("data-id");
      const type = event.target.getAttribute("data-type");
      removeFromCart(id, type);
    }
  });
}

document
  .getElementById("checkout-button")
  .addEventListener("click", async () => {
    const cart = loadCart();

    if (cart.length === 0) {
      alert("Your cart is empty!");
      return;
    }

    const response = await fetch("saveCart.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ cart }),
    });

    const result = await response.json();

    if (result.success) {
      window.location.href = "checkout.php";
    } else {
      alert("Error: " + result.message);
    }
  });

document.addEventListener("DOMContentLoaded", initCart);
