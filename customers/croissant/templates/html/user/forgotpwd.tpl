{* simple login form *}
<h2>Forgot your password?</h2>
<div class="account-wide">
	<form action="/user/forgotpwd" method="post">
		<fieldset>
			<div>
				<label for="register_email">Email:</label>
				<input type="email" id="register_email" name="mail">
			</div>
			<div class="submit login">
				<input type="submit" value="Recover" name="action">
			</div>
		</fieldset>
	</form>
</div>
