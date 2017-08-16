{* simple login form *}
<h2>Reset your password</h2>
<div class="account-wide">
	<form method="post" action="/user/resetpwd">
		<fieldset>
			<div>
				<label for="mail"><strong>Email address</strong> </label>
				<input type="text" class="form-text required styled" tabindex="1" value="" size="30" id="mail" name="mail" maxlength="64"/>
			</div>
			<div>
				<label for="passpwd"><strong>New password</strong> </label>
				<input type="password" class="form-text required" tabindex="2" size="60" name="passpwd"/>
			</div>
			<div>
				<label for="confirm-passpwd"><strong>anad again</strong> </label>
				<input type="password" class="form-text required" tabindex="3" size="60" name="confirm_passpwd"/>
			</div>
			<div class="submit login">
				<input type="submit" title="Log in" tabindex="3" value="Reset" name="action" class="btn primary" />
			</div>
			<input type="hidden" value="{$key}" name="key"/>
		</fieldset>
	</form>
</div>
