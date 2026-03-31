<!-- I do wanna state I did get this template from freefrontend.com and will be modifying it to fit the theme of the site, but I will give credit where credit is due -->
<?php require '../includes/header.php'; ?>
<main>

	<body>
		<div class="container" id="container">
			<div class="form-container sign-up-container">
				<form action="#">
					<h1>Create Account</h1>
					<input type="text" placeholder="Name" />
					<input type="email" placeholder="Email" />
					<input type="password" placeholder="Passphrase" />
					<button>Sign Up</button>
				</form>
			</div>
			<div class="form-container sign-in-container">
				<form action="#">
					<h1>Sign in</h1>
					<input type="email" placeholder="Email" />
					<input type="password" placeholder="Passphrase" />
					<a href="#">Forgot your passphrase?</a>
					<button>Sign In</button>
				</form>
			</div>
			<div class="overlay-container">
				<div class="overlay">
					<div class="overlay-panel overlay-left">
						<img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width: 80px; height: 80px;">
						<h1>Welcome Back!</h1>
						<p>To keep connected with us please login with your personal info</p>
						<button class="ghost" id="signIn">Sign In</button>
					</div>
					<div class="overlay-panel overlay-right">
						<img src="../img/gob.png" alt="Goblin Icon" class="mb-3" style="width: 80px; height: 80px;">
						<h1>Greetings, Traveler!</h1>
						<p>Enter your personal details and start your journey with us</p>
						<button class="ghost" id="signUp">Sign Up</button>
					</div>
				</div>
			</div>
		</div>
		</div>
		<script src="../js/SL.js"></script>

		<section class="back">