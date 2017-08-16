{* simple login form *}
<p class="right"><a href="/user/register">Not registered yet?</a></p>
<h2>Log in!</h2>
<div class="account-wide">
	<form action="/user/login" method="post">
		<fieldset>
			<div>
				<label for="register_email">Email:</label>
				<input type="email" id="register_email" name="mail">
			</div>
			<div>
				<label for="register_password">Password:</label>
				<input type="password" id="register_password" name="pass">
			</div>
			<div class="submit login">
				<input type="submit" value="Log in" name="submit">
			</div>
			<p><a href="/user/forgotpwd">Forgot your password?</a></p>
		</fieldset>
	</form>
</div>
{if $message}
<div class="message">
	<p>Login Failed. Please enter a valid email address and password.</p>
</div>
{/if}
