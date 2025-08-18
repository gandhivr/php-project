<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products | Grocery Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    <h1>Our Products</h1>
</header>

<nav>
    <a href="index.html">Home</a>
    <a href="products.html">Products</a>
    <a href="cart.html">Cart</a>
    <a href="contact.html">Contact</a>
    <a href="login.html">Login</a>
</nav>

<main>
    <div class="grid">
        <div class="product-card">
            <h3><a href="product-details.html?product=apples">Fresh Apples</a></h3>
            <p>$2.99 / kg</p>
            <button onclick="addToCart('Fresh Apples')">Add to Cart</button>
        </div>
        <div class="product-card">
            <h3><a href="product-details.html?product=bananas">Organic Bananas</a></h3>
            <p>$1.99 / kg</p>
            <button onclick="addToCart('Organic Bananas')">Add to Cart</button>
        </div>
        <div class="product-card">
            <h3><a href="product-details.html?product=milk">Milk 1L</a></h3>
            <p>$1.20</p>
            <button onclick="addToCart('Milk 1L')">Add to Cart</button>
        </div>
        <div class="product-card">
            <h3><a href="product-details.html?product=bread">Whole Wheat Bread</a></h3>
            <p>$2.50</p>
            <button onclick="addToCart('Whole Wheat Bread')">Add to Cart</button>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2025 Fresh Grocery Shop</p>
</footer>

<script src="script.js"></script>
</body>
</html>
