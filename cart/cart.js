const cartKey = "user-cart";

function loadCart() {
  const cartData = localStorage.getItem(cartKey);
  return cartData ? JSON.parse(cartData) : [];
}

function saveCart(cart) {
  localStorage.setItem(cartKey, JSON.stringify(cart));
}

function addToCart(id, name, price, quantity) {
  const cart = loadCart();
  const existingProduct = cart.find((product) => product.id === id);

  if (existingProduct) {
    existingProduct.quantity += quantity;
  } else {
    cart.push({ id, name, price, quantity });
  }

  saveCart(cart);
  updateCartUI(cart);
}

function removeFromCart(id) {
  let cart = loadCart();
  cart = cart.filter((product) => product.id !== id);
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
  cart.forEach((product) => {
    const productTotal = product.price * product.quantity;
    total += productTotal;

    const listItem = document.createElement("li");
    listItem.classList.add(
      "list-group-item",
      "d-flex",
      "justify-content-between",
      "align-items-center"
    );
    listItem.innerHTML = `
            <div>
                <strong>${product.name}</strong><br>
                Rs ${product.price.toFixed(2)} x ${product.quantity}
            </div>
            <div>
                <span>Rs ${productTotal.toFixed(2)}</span>
                <button class="btn btn-danger btn-sm remove-from-cart" data-id="${
                  product.id
                }">Remove</button>
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
      const productId = button.getAttribute("data-id");
      const productName = button.getAttribute("data-name");
      const productPrice = parseFloat(button.getAttribute("data-price"));
      const productStock = parseInt(button.getAttribute("data-stock"));
      const quantityInput = button
        .closest(".d-flex")
        .querySelector(".quantity-input");
      const quantity = parseInt(quantityInput.value);

      if (quantity > 0 && quantity <= productStock) {
        addToCart(productId, productName, productPrice, quantity);
      } else {
        alert("Invalid quantity!");
      }
    }
  });

  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("remove-from-cart")) {
      const productId = event.target.getAttribute("data-id");
      removeFromCart(productId);
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
